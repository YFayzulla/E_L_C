<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GroupTeacher;
use App\Models\User;

/**
 * Relationship-level authorisation for group-scoped actions.
 *
 * `role:user` middleware only proves the caller is *a* teacher. It says nothing
 * about whether this particular group is theirs — which is why, today, any
 * teacher can POST attendance for any group id in the URL. Every new
 * group-scoped controller action must call one of these.
 *
 * Admins pass everything.
 */
trait AuthorizesGroupAccess
{
    protected function assertTeachesGroup(?int $groupId): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);

        if ($user->hasRole('admin')) {
            return;
        }

        abort_if(! $groupId, 404);

        $teaches = GroupTeacher::where('teacher_id', $user->id)
            ->where('group_id', $groupId)
            ->exists();

        abort_unless($teaches, 403, 'Bu guruh sizga biriktirilmagan.');
    }

    /**
     * @param  array<int, int|string>  $groupIds
     */
    protected function assertTeachesGroups(array $groupIds): void
    {
        foreach (array_unique(array_filter($groupIds)) as $id) {
            $this->assertTeachesGroup((int) $id);
        }
    }

    protected function assertTeachesStudent(?int $studentId): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);

        if ($user->hasRole('admin')) {
            return;
        }

        abort_if(! $studentId, 404);

        $mine = $user->teacherGroups()->pluck('groups.id');

        $shared = User::where('id', $studentId)
            ->whereHas('groups', fn($q) => $q->whereIn('groups.id', $mine))
            ->exists();

        abort_unless($shared, 403, 'Bu talaba sizning guruhlaringizda emas.');
    }

    /**
     * Group ids the caller may act on: all of them for an admin, only their own
     * for a teacher. Use this to scope every list and every <select>.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function accessibleGroupIds()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return \App\Models\Group::query()->pluck('id');
        }

        return $user->teacherGroups()->pluck('groups.id');
    }
}
