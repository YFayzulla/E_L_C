<?php

namespace App\Http\Controllers;

use App\Models\ClinicDoctor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function index()
    {
        return view('user.index');
    }
    public function test()
    {
        $test=ClinicDoctor::find(1);
        return(view('user.test',compact('test')));
    }

}
