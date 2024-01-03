<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clinic;

class ClinicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clinics = Clinic::all();
        return view('user.clinics.index', compact('clinics'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user.clinics.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $formFields = $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'url' => 'nullable|url',
        ]);

        // Accessing user_id from the request
        $formFields['user_id'] = auth()->id();

        // Assigning default values
        $formFields["plan_id"] = 0;
        $formFields["latitude"] = 0;
        $formFields["longitude"] = 0;
        $formFields["status"] = "active";
        // dd($request);
        Clinic::create($formFields);

        return redirect()->route('clinic.index')->with('success', 'Clinic created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Clinic  $clinic
     * @return \Illuminate\Http\Response
     */
    public function show(Clinic $clinic)
    {
        return view('user.clinics.show', compact('clinic'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Clinic  $clinic
     * @return \Illuminate\Http\Response
     */
    public function edit(Clinic $clinic)
    {
        return view('user.clinics.edit', compact('clinic'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clinic  $clinic
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Clinic $clinic)
    {
//        dd($request);
        $request->validate([
            'name' => 'required',
//            'user_id' => 'required|exists:users,id',
//            'plan_id' => 'required|exists:plans,id',
            'address' => 'required',
            'phone' => 'required',
            'url' => 'nullable|url',
//            'latitude' => 'required|numeric',
//            'longitude' => 'required|numeric',
//            'status' => 'required',
        ]);


        $clinic->update($request->all());

        return redirect()->route('clinic.index')
            ->with('success', 'Clinic updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Clinic  $clinic
     * @return \Illuminate\Http\Response
     */
    public function destroy(Clinic $clinic)
    {
        $clinic->delete();

        return redirect()->route('clinic.index')->with('success', 'Clinic deleted successfully');
    }
}
