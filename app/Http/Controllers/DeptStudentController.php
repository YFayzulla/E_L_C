<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use App\Models\HistoryPayments;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use function PHPUnit\Framework\lessThanOrEqual;

class DeptStudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $students = User::role('student')->get();
        return view('user.dept.index', compact('students'));
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\DeptStudent $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function show(DeptStudent $deptStudent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\DeptStudent $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function edit(DeptStudent $deptStudent)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\DeptStudent $deptStudent
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
        }

        if ($dept - $payment > 0) {
//            if ($payment +  )
        }
//        else {
//            $item = ($payment / $dept);
//            if ((int)$item == $item) {
//                $student->status_month += $item;
////                $student->date = Carbon::now()->addMonths($item)->format('Y-m-d');
//                $student->date = $request->date_paid ?? Carbon::now()->format('Y-m-d');;
//
//            } else {
//                $student->status_month += (int)$item;
//                $item = $item - (int)$item;
//                $student->payed = $item * $student->dept;
//                $student->date = Carbon::now()->addMonths((int)$item)->format('Y-m-d');
//            }
//        }
//        $student->save();

        HistoryPayments::create([
            'user_id' => $student->user_id,
            'payment' => $request->payment,
            'date' => $request->date_paid ?? Carbon::now()->format('Y-m-d'),
        ]);

        return redirect()->back()->with('success', 'to`langan pul qabul qilindi');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\DeptStudent $deptStudent
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeptStudent $deptStudent)
    {

    }
}
