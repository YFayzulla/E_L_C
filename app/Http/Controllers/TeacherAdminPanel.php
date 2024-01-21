<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\StudentInformation;
use Illuminate\Http\Request;

class TeacherAdminPanel extends Controller
{

    public function group(){
        $id=auth()->id();
        $groups=GroupTeacher::where('teacher_id',$id)->get();
        return view('teacher.group',compact('groups')) ;
    }

    public function attendance($id){
        $students = StudentInformation::where('group_id',$id)->get();
        return view('teacher.attendance',compact('students'));
    }
    public function attendance_submit(){
        dd('salom');
    }

}
