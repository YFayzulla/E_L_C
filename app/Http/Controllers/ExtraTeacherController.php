<?php

namespace App\Http\Controllers;

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

        GroupTeacher::create([
            'teacher_id' => $id,
            'group_id' => $request->group_id
        ]);

        return redirect()->back()->with('success', 'Yangi guruh qo`shildi');
    }

    public function group_delete($id)
    {
        $group = GroupTeacher::find($id);
        $group->delete();
        return redirect()->back()->with('success', ' malumot o`chieildi');
    }

}
