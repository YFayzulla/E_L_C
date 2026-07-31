<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Finishing and reopening a group.
 *
 * Finishing a group graduates its students — but only those who are not still
 * studying somewhere else. A student in both "General English" and "IELTS" who
 * finishes the first is still an active student, and must keep appearing on the
 * billing screens.
 */
class GroupLifecycleService
{
    /**
     * Mark a group finished and graduate the students it was the last group for.
     *
     * @return array{graduated: array<int, string>, kept: array<int, string>}
     */
    public function finish(Group $group, ?string $finishedAt = null): array
    {
        $graduated = [];
        $kept = [];

        DB::transaction(function () use ($group, $finishedAt, &$graduated, &$kept) {
            $group->status = Group::STATUS_FINISHED;
            $group->finished_at = $finishedAt ?: now()->toDateString();
            $group->save();

            $students = User::role('student')
                ->whereHas('groups', fn($q) => $q->where('groups.id', $group->id))
                ->with('groups')
                ->get();

            foreach ($students as $student) {
                if ($this->stillStudyingElsewhere($student, $group->id)) {
                    $kept[] = $student->name;
                    continue;
                }

                $student->study_status = User::STUDY_GRADUATED;
                $student->graduated_at = $group->finished_at;
                $student->save();

                $graduated[] = $student->name;
            }
        });

        return ['graduated' => $graduated, 'kept' => $kept];
    }

    /**
     * Reopen a group. Students who were graduated BY this group become active
     * again; anyone graduated for another reason is left alone.
     *
     * @return int  how many students were reactivated
     */
    public function reopen(Group $group): int
    {
        $reactivated = 0;

        DB::transaction(function () use ($group, &$reactivated) {
            $finishedAt = $group->finished_at;

            $group->status = Group::STATUS_ACTIVE;
            $group->finished_at = null;
            $group->save();

            if (! $finishedAt) {
                return;
            }

            $reactivated = User::role('student')
                ->where('study_status', User::STUDY_GRADUATED)
                ->whereDate('graduated_at', $finishedAt)
                ->whereHas('groups', fn($q) => $q->where('groups.id', $group->id))
                ->update([
                    'study_status' => User::STUDY_ACTIVE,
                    'graduated_at' => null,
                ]);
        });

        return $reactivated;
    }

    /**
     * Is this student still enrolled in another group that is running?
     * The Kutish zali does not count as studying.
     */
    private function stillStudyingElsewhere(User $student, int $excludingGroupId): bool
    {
        return $student->groups
            ->reject(fn(Group $g) => (int) $g->id === $excludingGroupId
                || (int) $g->id === Group::WAITING_ROOM_ID
                || $g->isFinished())
            ->isNotEmpty();
    }
}
