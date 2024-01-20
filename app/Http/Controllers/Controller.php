<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
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

    public function index()
    {

    }

    public function auth(){
        return view('dashboard');
    }

    public function search(Request $request)
    {
        $users=HistoryPayments::where('date',$request->date_paid)->get();
        $date=$request->date_paid;
        return view('user.index',compact('users','date'));
    }

}
