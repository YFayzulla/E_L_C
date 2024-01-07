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
        $user=auth()->user();
        if($user->hasRole('admin'))

            return view('user.index');
        else
            return abort('403');
    }
    public function test()
    {

    }

}
