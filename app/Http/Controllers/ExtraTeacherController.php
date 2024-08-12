<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;

class ExtraTeacherController extends Controller
{
    public function add_group(Request $request, $id)
    {
        $request->validate([
//            'group_id'=>['required', 'unique:'.User::class]
        ]);

        GroupTeacher::query()->create([
            'teacher_id' => $id,
            'group_id' => $request->group_id
        ]);

        return redirect()->back()->with('success', 'A new group has been added');
    }

    public function group_delete($id)
    {
        $group = GroupTeacher::query()->find($id);
        $group->delete();
        return redirect()->back()->with('success', 'Information deleted');
    }


    public function attendanceDelete($id)
    {
        $attendance = Attendance::query()->find($id);
        $attendance->delete();
        return redirect()->back()->with('success', 'Information deleted');
    }
}
