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
        $user = User::find($id);
        $payment = $request->payment;
        $dept = $student->dept;
//        dd($student->student->should_pay, $request);

        if ($dept == $payment) {
            $student->status_month += 1;
            $user->status +=1 ;
            $student->date = $request->date_paid;
        } elseif ($dept - $payment > 0) {
            if ($student->payed == 0) {
                $student->payed = $payment;
                $student->date = Carbon::now()->format('Y-m-d');
            } else {
                $student->payed = 0;
                $student->status_month++;
                $user->status +=1 ;
            }

        } else {
            $item = ($payment / $dept);
            if ((int)$item == $item) {
                $student->status_month += $item;
                $user->status += $item;
                $student->date = $request->date_paid ?? Carbon::now()->format('Y-m-d');

            } else {
                $student->status_month += (int)$item;
                $user->status += (int)$item;
                $item = $item - (int)$item;
                $student->payed = $item * $student->dept;
                $student->date = Carbon::now()->addMonths((int)$item)->format('Y-m-d');
            }
        }

        $student->save();
        $user->save();

        HistoryPayments::create([
            'user_id' => $student->user_id,
            'payment' => $request->payment,
            'date' => $request->date_paid ?? Carbon::now()->format('Y-m-d'),
            'type_of_money' => $request->money_type,
        ]);

        return redirect()->back()->with('success', 'Successful');
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
