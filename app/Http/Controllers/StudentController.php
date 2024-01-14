<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Level;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $students = User::role('student')->get();
        return view('user.student.index', compact('students'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $groups = Group::all();
        return view('user.student.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/', 'unique:' . User::class],
            'group_id' => 'required'
        ]);

        if ($request->hasFile('photo')) {
            $name = $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('Photo', $name);
        }



        $user=User::create([
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'passport' => $request->passport,
            'phone' => $request->phone,
            'parents_name' => $request->parents_name,
            'parents_tel' => $request->parents_tel,
            'location' => $request->location,
            'photo' => $path ?? null,
        ])->assignRole('student');

        StudentInformation::create([
            'user_id' => $user ->id,
            'group_id' => $request->group_id,
            'level' =>  $request->name,
            'description' => $request->description
        ]);


        return redirect()->route('student.index')->with('success', 'malumot qo`lshildi');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $student=User::find($id);
        return view('user.student.show',compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $student = User::find($id);

//        dd($id,$student);
        if ($student !== null)
            return view('user.student.edit', compact('student'));
        else
            return abort('403');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
        ]);


        $student = User::find($id);

        if ($request->hasFile('photo')) {
            if (isset($student->photo)) {
                Storage::delete($student->photo);
            }
            $name = $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('Photo', $name);
        }

        $student->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'passport' => $request->passport,
            'location' => $request->location,
//            'group_id' => $request->group_id,
            'parents_name' => $request->parents_name,
            'parents_tel' => $request->parents_tel,
//            'money' => $request->money,
//            'status' => $request->status,
            'photo' => $path ?? $student->photo ?? null,
        ]);


        return redirect()->route('student.index')->with('success', 'malumot yangilandi');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $student = User::find($id);
        if (isset($student->photo)) {
            Storage::delete($student->photo);
        }
        $student->delete();
        return redirect()->back()->with('success', 'malumot o`chirildi');
    }
}
