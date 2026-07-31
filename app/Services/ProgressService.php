<?php

namespace App\Services;

use App\Models\User;
use App\Services\EnrollmentHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "O‘zlashtirish ko‘rsatkichi" — a 0-100 mastery figure per student per
 * CALENDAR MONTH, computed on demand. There is deliberately no snapshot table:
 * every component is derived from rows that already exist, so a corrected
 * attendance or a re-graded homework is reflected immediately.
 *
 * Four components, each normalised to 0-100:
 *
 *   attendance  100 * (L - absences - late_penalty * lates) / L
 *               L = lesson_and_histories (data = 1) rows in the month for the
 *               groups the student belongs to. Present is implicit — only
 *               status 0 (absent) and 2 (late) rows exist.
 *   tests       mean of assessments.get_mark in the month. READ ONLY.
 *   homework    100 * SUM(score) / SUM(homeworks.max_score) over submissions
 *               whose homework's COALESCE(due_date, created_at) falls in the
 *               month. status = 0 counts as score 0; an ungraded row
 *               (score NULL, status <> 0) is excluded entirely.
 *   skills      mean of the non-null lesson_skill_grades.score in the month.
 *
 * The weights in config('grading.progress_weights') are RENORMALISED over the
 * components that actually carry data in that month, so a centre that has not
 * started using homework yet is not dragged toward zero. A month where nothing
 * at all happened is null — a genuine gap in the chart and a dash in the
 * tables, never a zero.
 *
 * PERFORMANCE: every public entry point runs a constant number of grouped
 * queries (~8) no matter how many students are involved. Nothing in here may
 * ever loop over students issuing queries.
 */
class ProgressService
{
    public const COMPONENTS = ['attendance', 'tests', 'homework', 'skills'];

    /** Uzbek month names — the app does not ship a uz locale for Carbon. */
    private const MONTHS = [
        1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
        5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
        9 => 'Sentabr', 10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr',
    ];

    /**
     * 'Y-m' key -> "Iyul 2026". Used by the chart partial and every table.
     */
    public static function monthLabel(string $key): string
    {
        [$year, $month] = array_pad(explode('-', $key), 2, null);
        $month = (int) $month;

        return (self::MONTHS[$month] ?? $key) . ' ' . $year;
    }

    /**
     * Human label for the `basis` flag.
     */
    public static function basisLabel(?string $basis): ?string
    {
        return match ($basis) {
            'attendance_only' => 'faqat davomat asosida',
            'partial'         => 'to‘liq bo‘lmagan ma’lumot asosida',
            default           => null,
        };
    }

    /* ======================================================================
     | Public API
     ====================================================================== */

    /**
     * One student's trend.
     *
     * @return array{
     *     buckets: array<string, int|null>,
     *     components: array<string, int|null>,
     *     current: int|null,
     *     previous: int|null,
     *     delta: int|null,
     *     basis: string|null,
     *     current_month: string|null,
     *     previous_month: string|null
     * }
     */
    public function forStudent(int $userId, ?int $months = null): array
    {
        $computed = $this->compute([$userId], $months);
        $row = $computed['users'][$userId] ?? null;

        if (! $row) {
            return $this->emptyStudent($computed['keys']);
        }

        $summary = $this->summarise($row['buckets']);

        return [
            'buckets'        => $row['buckets'],
            'components'     => $summary['current_month'] !== null
                ? ($row['components'][$summary['current_month']] ?? $this->blankComponents())
                : $this->blankComponents(),
            'current'        => $summary['current'],
            'previous'       => $summary['previous'],
            'delta'          => $summary['delta'],
            'basis'          => $summary['current_month'] !== null
                ? ($row['basis'][$summary['current_month']] ?? null)
                : null,
            'current_month'  => $summary['current_month'],
            'previous_month' => $summary['previous_month'],
        ];
    }

    /**
     * One group: the monthly average plus every student's headline figure.
     *
     * @return array{
     *     buckets: array<string, int|null>,
     *     students: array<int, array{name: string, current: int|null, previous: int|null, delta: int|null, basis: string|null}>,
     *     current: int|null,
     *     previous: int|null,
     *     delta: int|null
     * }
     */
    public function forGroup(int $groupId, ?int $months = null): array
    {
        $all = $this->forGroups([$groupId], $months);

        return $all[$groupId] ?? [
            'buckets'  => array_fill_keys($this->window($months)['keys'], null),
            'students' => [],
            'current'  => null,
            'previous' => null,
            'delta'    => null,
        ];
    }

    /**
     * Several groups at once, still in a constant number of queries. This is
     * what the group picker uses — calling forGroup() in a loop would fire
     * eight queries per group.
     *
     * @param  array<int, int|string>  $groupIds
     * @return array<int, array{buckets: array<string, int|null>, students: array<int, array<string, mixed>>, current: int|null, previous: int|null, delta: int|null}>
     */
    public function forGroups(array $groupIds, ?int $months = null): array
    {
        $groupIds = array_values(array_unique(array_map('intval', array_filter($groupIds))));
        $window   = $this->window($months);

        $out = [];
        foreach ($groupIds as $id) {
            $out[$id] = [
                'buckets'  => array_fill_keys($window['keys'], null),
                'students' => [],
                'current'  => null,
                'previous' => null,
                'delta'    => null,
            ];
        }

        if (empty($groupIds)) {
            return $out;
        }

        // Which students sit in which of the requested groups (1 query).
        $rosters = [];
        $userIds = [];
        foreach (DB::table('group_user')->whereIn('group_id', $groupIds)->get(['user_id', 'group_id']) as $row) {
            $rosters[(int) $row->group_id][] = (int) $row->user_id;
            $userIds[(int) $row->user_id] = true;
        }
        $userIds = array_keys($userIds);

        if (empty($userIds)) {
            return $out;
        }

        // Keep only real students, and pick up their names (1 query).
        $names = User::role('student')
            ->whereIn('users.id', $userIds)
            ->orderBy('users.name')
            ->pluck('users.name', 'users.id');

        if ($names->isEmpty()) {
            return $out;
        }

        $computed = $this->compute($names->keys()->all(), $months);

        foreach ($groupIds as $groupId) {
            $members = array_values(array_filter(
                $rosters[$groupId] ?? [],
                fn(int $id) => $names->has($id)
            ));

            if (empty($members)) {
                continue;
            }

            // Keep the alphabetical order pluck() produced.
            $members = array_values(array_intersect($names->keys()->all(), $members));

            $students = [];
            $sums     = array_fill_keys($window['keys'], ['sum' => 0, 'n' => 0]);

            foreach ($members as $userId) {
                $row = $computed['users'][$userId] ?? null;

                if (! $row) {
                    continue;
                }

                foreach ($row['buckets'] as $key => $value) {
                    if ($value === null) {
                        continue;
                    }
                    $sums[$key]['sum'] += $value;
                    $sums[$key]['n']++;
                }

                $summary = $this->summarise($row['buckets']);

                $students[$userId] = [
                    'name'     => (string) $names->get($userId),
                    'current'  => $summary['current'],
                    'previous' => $summary['previous'],
                    'delta'    => $summary['delta'],
                    'basis'    => $summary['current_month'] !== null
                        ? ($row['basis'][$summary['current_month']] ?? null)
                        : null,
                    // Latest month that actually has data, per component.
                    'components' => $summary['current_month'] !== null
                        ? ($row['components'][$summary['current_month']] ?? $this->blankComponents())
                        : $this->blankComponents(),
                    // Mean of each component across the whole window — the
                    // "o'rtacha" the reports show next to the current figure.
                    'averages' => $this->componentAverages($row['components']),
                    // Mean overall rating across the window.
                    'average'  => $this->meanOf($row['buckets']),
                ];
            }

            // Rank by current rating, best first. Students with no rating yet
            // are unranked rather than being pushed to an arbitrary position.
            $ranked = collect($students)
                ->filter(fn(array $r) => $r['current'] !== null)
                ->sortByDesc('current')
                ->keys()
                ->values();

            foreach ($students as $userId => $_) {
                $position = $ranked->search($userId);
                $students[$userId]['rank'] = $position === false ? null : $position + 1;
            }

            $buckets = [];
            foreach ($window['keys'] as $key) {
                $buckets[$key] = $sums[$key]['n'] > 0
                    ? (int) round($sums[$key]['sum'] / $sums[$key]['n'])
                    : null;
            }

            $summary = $this->summarise($buckets);

            $out[$groupId] = [
                'buckets'  => $buckets,
                'students' => $students,
                'current'  => $summary['current'],
                'previous' => $summary['previous'],
                'delta'    => $summary['delta'],
                'average'  => $this->meanOf($buckets),
                // Group-wide mean per component, over every student who has a
                // figure for it — the bottom "o'rtacha" row of the report.
                'averages' => $this->meanOfComponentSets(
                    array_map(fn(array $r) => $r['averages'], $students)
                ),
            ];
        }

        return $out;
    }

    /* ======================================================================
     | The one and only aggregation pass
     ====================================================================== */

    /**
     * Monthly buckets + per-month component values for a set of students.
     *
     * @param  array<int, int>  $userIds
     * @return array{keys: array<int, string>, users: array<int, array{buckets: array<string, int|null>, components: array<string, array<string, int|null>>, basis: array<string, string|null>}>}
     */
    private function compute(array $userIds, ?int $months = null): array
    {
        $window = $this->window($months);
        $keys   = $window['keys'];
        $start  = $window['start'];
        $end    = $window['end'];

        $userIds = array_values(array_unique(array_map('intval', array_filter($userIds))));

        $result = ['keys' => $keys, 'users' => []];

        if (empty($userIds)) {
            return $result;
        }

        $inWindow = array_fill_keys($keys, true);

        // --- 1+2. lessons the student was ENROLLED FOR, per month -----------
        //
        // Deliberately NOT derived from current group_user membership. Doing
        // that erases history the instant a student changes group: the old
        // group's lessons vanish from the denominator while the absences they
        // collected there stay in the numerator, and a student who missed 2 of
        // 10 lessons reads as a flawless 100%.
        //
        // EnrollmentHistoryService replays student_information (join/leave) so a
        // lesson only counts if the student was in that group on the day it was
        // held — which makes the figure stable across transfers.
        $lessonsByUser = app(EnrollmentHistoryService::class)
            ->lessonsByMonth($userIds, $start, $end, $keys);

        // --- 3. absences and lates ------------------------------------------
        $absent = [];
        $late   = [];

        $rows = DB::table('attendances')
            ->whereIn('user_id', $userIds)
            ->whereIn('status', [0, 2])
            ->whereBetween('created_at', [$start, $end])
            ->get(['user_id', 'status', 'created_at']);

        foreach ($rows as $row) {
            $key = $this->monthKey($row->created_at);
            if ($key === null || ! isset($inWindow[$key])) {
                continue;
            }
            $uid = (int) $row->user_id;

            if ((int) $row->status === 2) {
                $late[$uid][$key] = ($late[$uid][$key] ?? 0) + 1;
            } else {
                $absent[$uid][$key] = ($absent[$uid][$key] ?? 0) + 1;
            }
        }

        // --- 4. tests (assessments — read only) -------------------------------
        $tests = [];

        $rows = DB::table('assessments')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('get_mark')
            ->whereBetween('created_at', [$start, $end])
            ->get(['user_id', 'get_mark', 'created_at']);

        foreach ($rows as $row) {
            $key = $this->monthKey($row->created_at);
            if ($key === null || ! isset($inWindow[$key])) {
                continue;
            }
            $uid = (int) $row->user_id;
            $tests[$uid][$key]['sum'] = ($tests[$uid][$key]['sum'] ?? 0) + (int) $row->get_mark;
            $tests[$uid][$key]['n']   = ($tests[$uid][$key]['n'] ?? 0) + 1;
        }

        // --- 5. homework -------------------------------------------------------
        $homework = [];

        $rows = DB::table('homework_submissions')
            ->join('homeworks', 'homeworks.id', '=', 'homework_submissions.homework_id')
            ->whereIn('homework_submissions.user_id', $userIds)
            ->whereRaw('COALESCE(homeworks.due_date, homeworks.created_at) BETWEEN ? AND ?', [
                $start->toDateTimeString(),
                $end->toDateTimeString(),
            ])
            ->get([
                'homework_submissions.user_id as user_id',
                'homework_submissions.status as status',
                'homework_submissions.score as score',
                'homeworks.max_score as max_score',
                'homeworks.due_date as due_date',
                'homeworks.created_at as set_at',
            ]);

        foreach ($rows as $row) {
            $key = $this->monthKey($row->due_date ?: $row->set_at);
            if ($key === null || ! isset($inWindow[$key])) {
                continue;
            }

            $missing = (int) $row->status === 0;

            // Handed in but not graded yet — no signal either way.
            if (! $missing && $row->score === null) {
                continue;
            }

            $max = (int) $row->max_score;
            if ($max <= 0) {
                continue;
            }

            $uid = (int) $row->user_id;
            $homework[$uid][$key]['score'] = ($homework[$uid][$key]['score'] ?? 0) + ($missing ? 0 : (int) $row->score);
            $homework[$uid][$key]['max']   = ($homework[$uid][$key]['max'] ?? 0) + $max;
        }

        // --- 6. skill grades -----------------------------------------------------
        $skills = [];

        $rows = DB::table('lesson_skill_grades')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('score')
            ->whereBetween('created_at', [$start, $end])
            ->get(['user_id', 'score', 'created_at']);

        foreach ($rows as $row) {
            $key = $this->monthKey($row->created_at);
            if ($key === null || ! isset($inWindow[$key])) {
                continue;
            }
            $uid = (int) $row->user_id;
            $skills[$uid][$key]['sum'] = ($skills[$uid][$key]['sum'] ?? 0) + (int) $row->score;
            $skills[$uid][$key]['n']   = ($skills[$uid][$key]['n'] ?? 0) + 1;
        }

        // --- 7. blend ---------------------------------------------------------------
        $penalty = (float) config('grading.late_penalty', 0.5);
        $weights = (array) config('grading.progress_weights', []);

        foreach ($userIds as $uid) {
            $buckets    = [];
            $components = [];
            $basis      = [];

            foreach ($keys as $key) {
                // Lessons this student was enrolled for that month, across every
                // group they were in AT THE TIME — not just their current one.
                $held = $lessonsByUser[$uid][$key] ?? 0;

                $attendance = null;
                if ($held > 0) {
                    $missed     = ($absent[$uid][$key] ?? 0) + $penalty * ($late[$uid][$key] ?? 0);
                    $attendance = $this->clamp(100 * ($held - $missed) / $held);
                }

                $testCount = $tests[$uid][$key]['n'] ?? 0;
                $testValue = $testCount > 0 ? $this->clamp($tests[$uid][$key]['sum'] / $testCount) : null;

                $homeworkMax   = $homework[$uid][$key]['max'] ?? 0;
                $homeworkValue = $homeworkMax > 0
                    ? $this->clamp(100 * ($homework[$uid][$key]['score'] ?? 0) / $homeworkMax)
                    : null;

                $skillCount = $skills[$uid][$key]['n'] ?? 0;
                $skillValue = $skillCount > 0 ? $this->clamp($skills[$uid][$key]['sum'] / $skillCount) : null;

                $parts = [
                    'attendance' => $attendance,
                    'tests'      => $testValue,
                    'homework'   => $homeworkValue,
                    'skills'     => $skillValue,
                ];

                $components[$key] = $parts;

                $present         = [];
                $weightedSum     = 0.0;
                $weightTotal     = 0.0;
                $unweightedTotal = 0;

                foreach (self::COMPONENTS as $name) {
                    if ($parts[$name] === null) {
                        continue;
                    }

                    $present[]        = $name;
                    $unweightedTotal += $parts[$name];

                    $weight       = (float) ($weights[$name] ?? 0);
                    $weightedSum += $weight * $parts[$name];
                    $weightTotal += $weight;
                }

                if (empty($present)) {
                    $buckets[$key] = null;
                    $basis[$key]   = null;
                    continue;
                }

                $buckets[$key] = $weightTotal > 0
                    ? (int) round($weightedSum / $weightTotal)
                    : (int) round($unweightedTotal / count($present));

                $basis[$key] = match (true) {
                    $present === ['attendance']           => 'attendance_only',
                    count($present) === count(self::COMPONENTS) => 'full',
                    default                               => 'partial',
                };
            }

            $result['users'][$uid] = [
                'buckets'    => $buckets,
                'components' => $components,
                'basis'      => $basis,
            ];
        }

        return $result;
    }

    /* ======================================================================
     | Helpers
     ====================================================================== */

    /**
     * The reporting window: N calendar months ending with the current one.
     *
     * @return array{keys: array<int, string>, start: Carbon, end: Carbon, months: int}
     */
    public function window(?int $months = null): array
    {
        $months = (int) ($months ?: config('grading.progress_months', 6));
        $months = max(1, min(24, $months));

        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $end   = Carbon::now()->endOfMonth();

        $keys   = [];
        $cursor = $start->copy();

        for ($i = 0; $i < $months; $i++) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return ['keys' => $keys, 'start' => $start, 'end' => $end, 'months' => $months];
    }

    /**
     * Latest known value, the one before it, and the difference. "Latest" is
     * the most recent month that actually has data — a quiet August must not
     * wipe out a trend that has been running since March.
     *
     * @param  array<string, int|null>  $buckets
     * @return array{current: int|null, previous: int|null, delta: int|null, current_month: string|null, previous_month: string|null}
     */
    private function summarise(array $buckets): array
    {
        $filled = array_filter($buckets, fn($value) => $value !== null);

        if (empty($filled)) {
            return [
                'current'        => null,
                'previous'       => null,
                'delta'          => null,
                'current_month'  => null,
                'previous_month' => null,
            ];
        }

        $months       = array_keys($filled);
        $currentMonth = end($months);
        $current      = $filled[$currentMonth];

        array_pop($months);
        $previousMonth = empty($months) ? null : end($months);
        $previous      = $previousMonth === null ? null : $filled[$previousMonth];

        return [
            'current'        => $current,
            'previous'       => $previous,
            'delta'          => $previous === null ? null : $current - $previous,
            'current_month'  => $currentMonth,
            'previous_month' => $previousMonth,
        ];
    }

    /**
     * 'Y-m' from either a driver string ("2026-07-14 10:00:00") or a date
     * object, without paying for a Carbon parse per row.
     */
    private function monthKey($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m');
        }

        $value = trim((string) $value);

        return strlen($value) >= 7 ? substr($value, 0, 7) : null;
    }

    private function clamp(float $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    /**
     * @return array<string, null>
     */
    private function blankComponents(): array
    {
        return array_fill_keys(self::COMPONENTS, null);
    }

    /**
     * Mean of the non-null values in a month => value map. Null when the whole
     * window is empty — never 0, which would read as "scored zero".
     *
     * @param  array<string, int|null>  $values
     */
    private function meanOf(array $values): ?int
    {
        $present = array_filter($values, fn($v) => $v !== null);

        return empty($present) ? null : (int) round(array_sum($present) / count($present));
    }

    /**
     * Per-component mean across every month in the window.
     *
     * @param  array<string, array<string, int|null>>  $byMonth  [monthKey][component] = value
     * @return array<string, int|null>
     */
    private function componentAverages(array $byMonth): array
    {
        $sums = array_fill_keys(self::COMPONENTS, ['sum' => 0, 'n' => 0]);

        foreach ($byMonth as $components) {
            foreach (self::COMPONENTS as $component) {
                $value = $components[$component] ?? null;

                if ($value === null) {
                    continue;
                }

                $sums[$component]['sum'] += $value;
                $sums[$component]['n']++;
            }
        }

        $out = [];

        foreach (self::COMPONENTS as $component) {
            $out[$component] = $sums[$component]['n'] > 0
                ? (int) round($sums[$component]['sum'] / $sums[$component]['n'])
                : null;
        }

        return $out;
    }

    /**
     * Mean of several already-averaged component sets (one per student).
     *
     * @param  array<int, array<string, int|null>>  $sets
     * @return array<string, int|null>
     */
    private function meanOfComponentSets(array $sets): array
    {
        $sums = array_fill_keys(self::COMPONENTS, ['sum' => 0, 'n' => 0]);

        foreach ($sets as $set) {
            foreach (self::COMPONENTS as $component) {
                $value = $set[$component] ?? null;

                if ($value === null) {
                    continue;
                }

                $sums[$component]['sum'] += $value;
                $sums[$component]['n']++;
            }
        }

        $out = [];

        foreach (self::COMPONENTS as $component) {
            $out[$component] = $sums[$component]['n'] > 0
                ? (int) round($sums[$component]['sum'] / $sums[$component]['n'])
                : null;
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function emptyStudent(array $keys): array
    {
        return [
            'buckets'        => array_fill_keys($keys, null),
            'components'     => $this->blankComponents(),
            'current'        => null,
            'previous'       => null,
            'delta'          => null,
            'basis'          => null,
            'current_month'  => null,
            'previous_month' => null,
        ];
    }
}
