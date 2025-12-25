<?php

namespace App\Observers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GroupObserver
{
    /**
     * Handle the Group "created" event.
     *
     * @param  \App\Models\Group  $group
     * @return void
     */
    public function created(Group $group)
    {
        try {
            // Find the teacher assigned to the same room as the new group.
            // Assuming one room has one teacher. If multiple, it will take the first one.
            $teacher = User::role('user')->where('room_id', $group->room_id)->first();

            if ($teacher) {
                // Assign the new group to the teacher.
                GroupTeacher::create([
                    'group_id' => $group->id,
                    'teacher_id' => $teacher->id,
                ]);

                Log::info("GroupObserver: Automatically assigned new group '{$group->name}' (ID: {$group->id}) to teacher '{$teacher->name}' (ID: {$teacher->id}).");
            } else {
                Log::warning("GroupObserver: No teacher found for room ID {$group->room_id} when trying to assign new group '{$group->name}'.");
            }
        } catch (\Exception $e) {
            Log::error("Error in GroupObserver@created for Group ID {$group->id}: " . $e->getMessage());
        }
    }
}
