<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        return view('user.room.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $clinics=Clinic::all();
        return view('user.room.create',compact('clinics'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        dd($request);
        $formFields = $request->validate([
            'name' => 'required',
            'clinic_id' => 'required'
        ]);

//        dd($formFields);

        Room::create($formFields);

        return redirect()->route('clinic.index')->with('success', 'Yangi xona yaratildi');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function show(Room $room)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function edit(Room $room)
    {
        $clinics=Clinic::all();
        return view('user.room.edit',compact('room','clinics'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required'
            ]);

        $room->update($request->all());

        return redirect()->route('clinic.index')
            ->with('success', 'Xona malumotlari yangilandi');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */

    public function destroy(Room $room)
    {
        //
    }
}
