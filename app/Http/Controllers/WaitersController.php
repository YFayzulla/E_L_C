<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class WaitersController extends Controller
{
    public function index(){
        $students = User::role('student')->where('group_id',1)->get();
        return view('user.waiters.index' ,compact('students'));
    }
}
