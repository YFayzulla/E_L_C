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
        // Logic for assigning teacher based on room is removed.
        // If there's any other logic needed for group creation, add it here.
    }
}
