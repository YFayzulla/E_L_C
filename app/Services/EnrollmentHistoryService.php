<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reconstructs WHEN a student belonged to WHICH group.
 *
 * Why this exists
 * ---------------
 * Every attendance percentage needs a denominator: "how many lessons was this
 * student expected at?". The obvious answer — count lessons in the groups the
 * student is in — is wrong the moment a student changes group, because the old
 * group's lessons silently drop out of the denominator while the absences the
 * student collected there stay in the numerator. A student who missed 2 of 10
 * lessons in group A reads as a flawless 100% the day they move to group B.
 *
 * So the denominator has to be historical: a lesson counts only if the student
 * was enrolled in that group ON THE DAY it was held.
 *
 * Where the intervals come from
 * -----------------------------
 * `student_information` is the enrolment ledger — action 0 = qo'shildi,
 * 1 = chiqdi (see StudentGroupService). Each join opens an interval, the next
 * leave closes it.
 *
 * Legacy rows need care. Before the `action` column existed the table only ever
 * recorded joins, and the old change_group wrote a fresh row on every save, so
 * duplicates are normal and departures were never recorded at all. Two
 * fallbacks keep those students honest:
 *
 *   - still in `group_user`  -> the last interval stays open (they are there now)
 *   - not in `group_user`    -> close the interval at their last observed
 *                               activity in that group, so the group's later
 *                               lessons are not charged to someone who had
 *                               already left
 *
 * Nothing here writes. It only reads history that is already on disk, which is
 * why a transfer cannot destroy it.
 */
class EnrollmentHistoryService
{
    /**
     * Enrolment intervals per student per group.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, array<int, array<int, array{start: Carbon, end: Carbon|null}>>>
     *         [userId][groupId][] = ['start' => ..., 'end' => null|Carbon]
     */
    public function intervals(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if (empty($userIds)) {
            return [];
        }

        $ledger  = $this->ledgerRows($userIds);
        $current = $this->currentMembership($userIds);
        $lastSeen = $this->lastActivity($userIds);

        $out = [];

        foreach ($ledger as $userId => $groups) {
            foreach ($groups as $groupId => $events) {
                // Chronological, and a join always wins a tie with a leave on
                // the same timestamp — re-joining is the more recent intent.
                usort($events, function ($a, $b) {
                    return [$a['at']->getTimestamp(), $a['action']]
                        <=> [$b['at']->getTimestamp(), $b['action']];
                });

                $open = null;

                foreach ($events as $event) {
                    if ($event['action'] === StudentGroupService::ACTION_LEFT) {
                        // A leave with no matching join still proves membership:
                        // you cannot leave a group you were never in. The join
                        // simply predates the ledger (attached directly by
                        // StudentController, or seeded), so the interval is left
                        // open at the start rather than thrown away — throwing it
                        // away is exactly how the old code lost the history.
                        $out[$userId][$groupId][] = ['start' => $open, 'end' => $event['at']];
                        $open = null;
                        continue;
                    }

                    // A second join with no leave in between is a duplicate row
                    // from the old code — keep the earliest.
                    if ($open === null) {
                        $open = $event['at'];
                    }
                }

                if ($open !== null) {
                    $out[$userId][$groupId][] = [
                        'start' => $open,
                        'end'   => $this->closeOpenInterval($userId, $groupId, $current, $lastSeen),
                    ];
                }
            }
        }

        // A student can sit in a group with no ledger row at all (attached
        // directly by StudentController, or seeded). Treat them as enrolled for
        // the whole window rather than losing them.
        foreach ($current as $userId => $groupIds) {
            foreach ($groupIds as $groupId) {
                if (! isset($out[$userId][$groupId])) {
                    $out[$userId][$groupId][] = ['start' => null, 'end' => null];
                }
            }
        }

        return $out;
    }

    /**
     * Lessons each student was enrolled for, bucketed by month.
     *
     * @param  array<int, int>     $userIds
     * @param  array<int, string>  $monthKeys  'Y-m', the window to report on
     * @return array<int, array<string, int>>  [userId][monthKey] = lessons
     */
    public function lessonsByMonth(array $userIds, Carbon $start, Carbon $end, array $monthKeys): array
    {
        $intervals = $this->intervals($userIds);

        if (empty($intervals)) {
            return [];
        }

        $groupIds = [];
        foreach ($intervals as $groups) {
            foreach (array_keys($groups) as $groupId) {
                $groupIds[$groupId] = true;
            }
        }

        $lessons = DB::table('lesson_and_histories')
            ->whereIn('group', array_keys($groupIds))
            ->where('data', 1)
            ->whereBetween('created_at', [$start, $end])
            ->get(['group', 'created_at']);

        $inWindow = array_fill_keys($monthKeys, true);
        $out = [];

        foreach ($lessons as $lesson) {
            $held  = $this->toCarbon($lesson->created_at);
            $month = $held?->format('Y-m');

            if ($month === null || ! isset($inWindow[$month])) {
                continue;
            }

            $groupId = (int) $lesson->group;

            foreach ($intervals as $userId => $groups) {
                if (! isset($groups[$groupId])) {
                    continue;
                }

                if ($this->covers($groups[$groupId], $held)) {
                    $out[$userId][$month] = ($out[$userId][$month] ?? 0) + 1;
                }
            }
        }

        return $out;
    }

    /**
     * Lessons a single student was enrolled for since a given moment.
     * Used by User::attendanceRate().
     */
    public function lessonCountSince(int $userId, Carbon $since): int
    {
        $intervals = $this->intervals([$userId])[$userId] ?? [];

        if (empty($intervals)) {
            return 0;
        }

        $lessons = DB::table('lesson_and_histories')
            ->whereIn('group', array_keys($intervals))
            ->where('data', 1)
            ->where('created_at', '>=', $since)
            ->get(['group', 'created_at']);

        $count = 0;

        foreach ($lessons as $lesson) {
            $held = $this->toCarbon($lesson->created_at);

            if ($held !== null && $this->covers($intervals[(int) $lesson->group] ?? [], $held)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Every group a student has EVER been in, newest first — including ones
     * they have since left. This is what the history screens iterate.
     *
     * @return array<int, int>
     */
    public function groupsEverJoined(int $userId): array
    {
        $ids = array_keys($this->intervals([$userId])[$userId] ?? []);

        return array_values(array_map('intval', $ids));
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param  array<int, array{start: Carbon|null, end: Carbon|null}>  $intervals
     */
    private function covers(array $intervals, Carbon $moment): bool
    {
        foreach ($intervals as $interval) {
            $afterStart = $interval['start'] === null || $moment->greaterThanOrEqualTo($interval['start']);
            $beforeEnd  = $interval['end'] === null || $moment->lessThan($interval['end']);

            if ($afterStart && $beforeEnd) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<int, array<int, array{at: Carbon, action: int}>>>
     */
    private function ledgerRows(array $userIds): array
    {
        $rows = DB::table('student_information')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('group_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['user_id', 'group_id', 'action', 'created_at']);

        $out = [];

        foreach ($rows as $row) {
            $at = $this->toCarbon($row->created_at);

            if ($at === null) {
                continue;
            }

            $out[(int) $row->user_id][(int) $row->group_id][] = [
                'at'     => $at,
                'action' => (int) ($row->action ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function currentMembership(array $userIds): array
    {
        $out = [];

        foreach (DB::table('group_user')->whereIn('user_id', $userIds)->get(['user_id', 'group_id']) as $row) {
            $out[(int) $row->user_id][] = (int) $row->group_id;
        }

        return $out;
    }

    /**
     * Last moment each student was observably active in each group — the only
     * evidence available for a departure the old code never recorded.
     *
     * @return array<int, array<int, Carbon>>
     */
    private function lastActivity(array $userIds): array
    {
        $out = [];

        $stamp = function (int $userId, int $groupId, $raw) use (&$out) {
            $at = $this->toCarbon($raw);

            if ($at === null) {
                return;
            }

            if (! isset($out[$userId][$groupId]) || $at->greaterThan($out[$userId][$groupId])) {
                $out[$userId][$groupId] = $at;
            }
        };

        foreach (
            DB::table('attendances')
                ->whereIn('user_id', $userIds)
                ->get(['user_id', 'group_id', 'created_at']) as $row
        ) {
            $stamp((int) $row->user_id, (int) $row->group_id, $row->created_at);
        }

        foreach (
            DB::table('lesson_skill_grades')
                ->whereIn('user_id', $userIds)
                ->get(['user_id', 'group_id', 'created_at']) as $row
        ) {
            $stamp((int) $row->user_id, (int) $row->group_id, $row->created_at);
        }

        return $out;
    }

    /**
     * @param  array<int, array<int, int>>     $current
     * @param  array<int, array<int, Carbon>>  $lastSeen
     */
    private function closeOpenInterval(int $userId, int $groupId, array $current, array $lastSeen): ?Carbon
    {
        // Still enrolled — the interval genuinely has no end.
        if (in_array($groupId, $current[$userId] ?? [], true)) {
            return null;
        }

        // Gone, but no leave row was ever written (legacy change_group). Close
        // at the last thing we saw them do there, so the group's later lessons
        // are not charged to them. +1 second so that final lesson still counts.
        if (isset($lastSeen[$userId][$groupId])) {
            return $lastSeen[$userId][$groupId]->copy()->addSecond();
        }

        // Gone with no trace at all: the interval contributes nothing.
        return $lastSeen[$userId][$groupId] ?? Carbon::createFromTimestamp(0);
    }

    private function toCarbon($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
