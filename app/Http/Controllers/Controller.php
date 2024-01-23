<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use App\Models\GroupTeacher;
use App\Models\HistoryPayments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

//    public function index()
//    {
//    }

    public function auth(){
        $id=auth()->id();
        $groups=GroupTeacher::where('teacher_id',$id)->get();
        return view('dashboard',compact('groups'));
    }

    public function search(Request $request)
    {

        $users=HistoryPayments::where('date',$request->date_paid)->get();
        $date=$request->date_paid;
        return view('user.index',compact('users','date'));

    }

}
