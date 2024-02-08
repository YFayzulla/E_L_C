<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\DeptStudent;
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
        $students = User::orderBy("name")->role('student')->get();


//        foreach ($students as $user)
//        {
////            var_dump($user->name);
//        }
        return view('user.student.index', compact('students'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $groups = Group::where('id','!=',1)->get();
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
            'phone' => ['required'],
            'group_id' => 'required'
        ]);

        if ($request->hasFile('photo')) {
            $name = $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('Photo', $name);
        }

        $group = Group::where('id', $request->group_id)->first();

        $user = User::create([
            'name' => $request->name,
            'password' => bcrypt($request->password),
            'passport' => $request->passport,
            'phone' => $request->phone,
            'parents_name' => $request->parents_name,
            'parents_tel' => $request->parents_tel,
            'group_id' => $group->id,
            'location' => $request->location,
            'photo' => $path ?? null,
            'should_pay' => $request->should_pay ?? $group->monthly_payment,
            'description' => $request->description,
        ])->assignRole('student');


        StudentInformation::create([
            'user_id' => $user->id,
            'group_id' => $request->group_id,
        ]);


        DeptStudent::create([
            'user_id' => $user->id,
            'payed' => 0,
            'dept' => $request->should_pay,
            'status_month' =>  0
        ]);

        return redirect()->route('student.index')->with('success', 'Information has been added');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attendances=Attendance::where('user_id',$id)->get();
        $student = User::find($id);
        $groups = Group::all();
        return view('user.student.show', compact('student','attendances','groups'));
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
        $groups = Group::where('id','!=',1)->get();



        //        dd($id,$student);
        if ($student !== null)
            return view('user.student.edit', compact('student','groups'));
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
        $student = DeptStudent::where('user_id', $id)->first();
        $payment = $request->payment;
        $dept = $student->dept;

//        dd($student->student->should_pay, $request);

        if ($dept == $payment) {
            $student->status_month += 1;
            $student->date = $request->date_paid;
        } elseif ($dept - $payment > 0) {
            if ($student->payed == 0) {
                $student->payed = $payment;
                $student->date = Carbon::now()->format('Y-m-d');
            } else {
                $student->payed = 0;
                $student->status_month++;
            }

        } else {
            $item = ($payment / $dept);
            if ((int)$item == $item) {
                $student->status_month += $item;
//                $student->date = Carbon::now()->addMonths($item)->format('Y-m-d');
                $student->date = $request->date_paid ?? Carbon::now()->format('Y-m-d');

            } else {
                $student->status_month += (int)$item;
                $item = $item - (int)$item;
                $student->payed = $item * $student->dept;
                $student->date = Carbon::now()->addMonths((int)$item)->format('Y-m-d');
            }
        }

        $student->save();

        HistoryPayments::create([
            'user_id' => $student->user_id,
            'payment' => $request->payment,
            'date' => $request->date_paid ?? Carbon::now()->format('Y-m-d'),
            'type_of_money' => $request->money_type,
        ]);
        return redirect()->back()->with('success', 'The paid money was received');
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
        return redirect()->back()->with('success', 'Information deleted');
    }
}
