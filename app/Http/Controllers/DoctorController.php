<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $doctors = Doctor::all();
        return view('user.doctors.index',compact('doctors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $rooms=Room::all();
        return view('user.doctors.create',compact('rooms'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        dd($request->all());
        $request->validate([
            'name' => 'required',
            'surname' => 'required',
            'passport' => 'required',
            'phone' => 'required',
            'password' => 'required',
            'room_id' => 'required',
        ]);
        if ($request->hasFile('file')){
            $name = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('Photo', $name);
        }

        $doctor=Doctor::create([
            'name'=>$request->name,
            'surname'=>$request->surname,
            'passport'=>$request->passport,
            'phone' => $request->phone,
            'image' => $path ?? null,
            'password' => bcrypt($request->password),
            'status' => 1,
            'room_id' => $request->room_id,
        ])->save();

        return redirect()->route('doctor.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(User $user)
    {
//        $date = date('Y-m-d',strtotime(now()));
//        $customers = Customer::orderby('time')->where('date',$date)->where('doctor_id',$user->id)->get();
//        return view('doctors.show',['customers'=>$customers,'doctor'=>$user]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $doctor=Doctor::find($id);
        $rooms=Room::all();
        return view('user.doctors.edit',compact('doctor','rooms'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'surname' => 'required',
            'passport' => 'required',
            'phone' => 'required',
            'password' => 'required',
            'room_id' => 'required'
            ]);

        $doctor=Doctor::find($id);

        if ($request->hasFile('file')){
            if (isset($doctor->image)){
                Storage::delete($doctor->image);
            }
            $name = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('Photo',$name);
            $doctor->image = $path;
            $doctor->save();

        }

        $doctor->updateOrFail($request->all());



    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user=Doctor::find($id);
        $user->delete();
        return redirect()->back()->with('success','Удалено!');
    }
}
