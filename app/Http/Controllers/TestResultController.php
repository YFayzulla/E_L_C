<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestResultController extends Controller
{
    public function index()
    {

        return view('testresult.index');

    }
}
