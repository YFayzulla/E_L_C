<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;
use function Symfony\Component\String\u;

class WaitersController extends Controller
{
    public function index()
    {
        $groups = Group::where('id', '!=', 1)->get();
        $students = User::role('student')->orderby('name') ->where('group_id', 1)->get();
        return view('user.waiters.index', compact('students', 'groups'));
    }
}
