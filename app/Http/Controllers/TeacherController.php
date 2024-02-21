<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teachers = User::role('user')->orderBy('name')->get();
        return view('user.teacher.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        return view('user.teacher.create');
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
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/','unique:'.User::class],
            'password' => 'required',
            'location' => 'required',
            'date_born' => 'required',

        ]);

        if ($request->hasFile('photo')) {

            $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
            $path = $request->file('photo')->storeAs('Photo', $fileName);

        }

        User::create([
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'passport' => $request->passport,
            'date_born' => $request->date_born,
            'location' => $request->location,
            'phone' => $request->phone,
            'photo' => $path ?? null,
        ])->assignRole('user');

        return redirect()->route('teacher.index')->with('success', 'Information has been added');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $groups=Group::where('id','!=',1)->get();

        $teachers=GroupTeacher::where('teacher_id','=',$id)->get();

        return view('user.teacher.show',compact('teachers','groups','id'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $teacher = User::find($id);
//        dd($id,$teacher);
        if ($teacher !== null)
            return view('user.teacher.edit', compact('teacher'));
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
            'date_born' => 'required',
            'phone' => ['required', 'string', 'regex:/^\+998\d{9}$/'],
            'password' => 'required',
            'location'=>'required',
        ]);


        $teacher = User::find($id);

        if ($request->hasFile('photo')) {
            if (isset($teacher->photo)) {
                Storage::delete($teacher->photo);
            }
            $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
            $path = $request->file('photo')->storeAs('Photo', $fileName);
        }

;        $teacher->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'date_born' => $request->date_born,
            'location' => $request->location,
            'passport' => $request->passport,
            'photo' => $path ?? $teacher->photo ?? null,
        ]);


        return redirect()->route('teacher.index')->with('success','Information has been updated');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $teacher=User::find($id);
        $teacher->delete();
        return redirect()->back()->with('success','Information deleted');
    }
}
