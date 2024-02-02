<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\StudentInformation;
use Illuminate\Http\Request;

class TeacherAdminPanel extends Controller
{

    public function group()
    {
        $id = auth()->id();
        $groups = GroupTeacher::where('teacher_id', $id)->get();
        return view('teacher.group', compact('groups'));
    }

    public function attendance($id)
    {
        $students = StudentInformation::where('group_id', $id)->get();
        return view('teacher.attendance', compact('students', 'id'));
    }

    public function attendance_submit(Request $request, $id)
    {
//        dd($id);

        foreach ($request->status as $name => $status) {
            $user_id = auth()->id();
            Attendance::create([
                'user_id' => $name,
                'group_id' => $id,
                'who_checked' => $user_id
            ]);
        }

        return redirect()->route('attendance')->with('success' , 'Saved');
    }
}
