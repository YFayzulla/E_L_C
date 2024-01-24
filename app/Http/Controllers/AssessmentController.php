<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $id = auth()->id();
        $groups = GroupTeacher::where('teacher_id', $id)->get();
        return view('teacher.assessment.index', compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Assessment  $assessment
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $students= StudentInformation::where('group_id',$id)->get();
        return view('teacher.assessment.make_markes',compact('students','id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Assessment  $assessment
     * @return \Illuminate\Http\Response
     */
    public function edit(Assessment $assessment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Assessment  $assessment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $group=Group::where('id',$id)->first();

        foreach ($request->end_mark as $id => $end_mark) {
            $user_id = auth()->id();
            Assessment::create([
                'user_id' => $id,
                'get_mark' => $end_mark,
                'who_checked' => $user_id,
                'group'=>$group->name,
//              'overall_result'=>$
            ]);
        }
        $id = auth()->id();
        $groups = GroupTeacher::where('teacher_id', $id)->get();
        return view('teacher.assessment.index', compact('groups'))->with('success','baholar saqlandi');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Assessment  $assessment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Assessment $assessment)
    {
        //
    }
}
