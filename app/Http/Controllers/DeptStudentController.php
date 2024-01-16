<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use Illuminate\Http\Request;

class DeptStudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('user.dept.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DeptStudent  $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function show(DeptStudent $deptStudent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DeptStudent  $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function edit(DeptStudent $deptStudent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeptStudent  $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DeptStudent $deptStudent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DeptStudent  $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeptStudent $deptStudent)
    {
        //
    }
}
