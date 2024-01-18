<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use App\Models\HistoryPayments;
use App\Models\User;
use Illuminate\Http\Request;
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

        if ($dept == $payment) {
            $student->status_month++;
            dd('1');
        } elseif ($dept - $payment > 0) {
            $student->payed = $request->payment;
            $student->dept = $student->dept - $request->payment;
        } else {
            $item = ($payment / $dept);
            if ((int)$item == $item) {
                $student->status_month = $item;
            } else {
                $student->status_month = (int)$item;
                $item = $item - (int)$item;
                $student->payed = $item * $student->dept;
            }

        }
        $student->save();

        HistoryPayments::create([
            'user_id' => $student->user_id,
            'payment' => $request->payment,
            'date_paid' => $request->date_paid ?? now(),
        ]);

        return redirect()->back();
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
