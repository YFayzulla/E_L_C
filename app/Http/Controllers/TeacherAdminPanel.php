<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class TeacherAdminPanel extends Controller
{

    public function group(){

        return view('teacher.show') ;

    }

}
