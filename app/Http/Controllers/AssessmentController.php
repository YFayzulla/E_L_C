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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Assessment $assessment
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $students = User::where('group_id', $id)->get();
        $groups = Group::OrderBy('name')->get();
        return view('teacher.assessment.make_markes', compact('students', 'id', 'groups'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Assessment $assessment
     * @return \Illuminate\Http\Response
     */
    public function edit(Assessment $assessment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Assessment $assessment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $end_mark = $request->end_mark;
        $rec_group = $request->recommended;
        $reason = $request->reason;
        $student = $request->student;
        $count = count($reason);
        $group = Group::find($id);

        for ($i = 0; $i < $count; $i++) {
            $data = new Assessment();
            if ($end_mark[$i] != null || $end_mark[$i] != 0) {
                $data->get_mark = $end_mark[$i];
                $data->user_id = $student[$i];
                $data->for_what = $reason[$i];
                $data->rec_group = $rec_group[$i];
                $data->group = $group->name;
                $data->save();
            }
        }

        return redirect()->route('assessment.index')->with('success', 'Grades saved');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Assessment $assessment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Assessment $assessment)
    {
        //
    }
}
