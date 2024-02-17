<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Group;
use App\Models\Level;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups=Group::where('id','!=',1)->orderby('name') ->get();
        return view('user.group.index',compact('groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $level=Level::all();

        return view('user.group.create',compact('level'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'finish_time' => 'required',
            'monthly_payment' => 'required',
        ]);

        Group::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'finish_time' => $request->finish_time,
            'monthly_payment' => $request->monthly_payment,
            'level' => $request->level,
        ]);
        return redirect()->route('group.index')->with('success', 'Information has been added');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group)
    {

        $id=$group->name;
        $groups=Group::orderby('name')->get();
        $assessments=Assessment::where('Group',$group->name)->orderby('created_at')->get();
        return view('user.group.show',compact('assessments','groups','id'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        $level=Level::all();
        return view('user.group.edit',compact('group','level'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'finish_time' => 'required',
            'monthly_payment' => 'required',
        ]);

        $group->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'finish_time' => $request->finish_time,
            'monthly_payment' => $request->monthly_payment,
            'level' => $request->level,
        ]);

        return redirect()->route('group.index')->with('success', 'Information has been updated');


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        $group->delete();
        User::where('group_id', $group->id)->update(['group_id' => 1]);// Assuming 1 is the default group ID
        return redirect()->back()->with('success','Information deleted');
    }
}
