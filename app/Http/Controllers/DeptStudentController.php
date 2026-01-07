<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use App\Models\HistoryPayments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeptStudentController extends Controller
{
    public function index()
    {
        $students = User::role('student')
            ->with('deptStudent', 'groups')
            ->leftJoin('dept_students', 'users.id', '=', 'dept_students.user_id')
            ->select('users.*') // Select only user columns to avoid conflicts, dept_students data is loaded via with() or we can select specific columns if needed for sorting
            // Order groups explicitly:
            // 0) Partially Paid (payed > 0)
            // 1) Debtor (status <= 0)
            // 2) Paid (status > 0)
            // 3) Disabled (status IS NULL)
            ->orderByRaw("
                CASE 
                    WHEN users.status IS NULL THEN 3 
                    WHEN COALESCE(dept_students.payed, 0) > 0 THEN 0 
                    WHEN users.status <= 0 THEN 1 
                    ELSE 2 
                END
            ")
            // Inside groups, sort by status then by name
            ->orderBy('users.status')
            ->orderBy('users.name')
            ->paginate(20); // Added pagination

        return view('admin.dept.index', compact('students'));
    }

    public function create()
    {
        // This action is not implemented.
    }

    public function store(Request $request)
    {
        // This action is not implemented.
    }

    public function show(DeptStudent $deptStudent)
    {
        // This action is not implemented.
    }

    public function edit(DeptStudent $deptStudent)
    {
        // This action is not implemented.
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'payment' => 'required|string',
            'date_paid' => 'nullable|date',
            'money_type' => 'required|string|max:255',
        ]);

        $cleanPayment = (float) str_replace([' ', ','], '', $request->payment);

        if ($cleanPayment <= 0) {
            return redirect()->back()->with('error', 'To\'lov miqdori noto\'g\'ri kiritildi.');
        }

        $user = User::with('deptStudent', 'groups')->findOrFail($id);
        $deptStudent = $user->deptStudent;

        if (!$deptStudent) {
            return redirect()->back()->with('error', 'Talabaning to\'lov ma\'lumotlari topilmadi.');
        }

        $monthlyDept = $deptStudent->dept;
        $paidDate = $request->date_paid ? Carbon::parse($request->date_paid) : Carbon::now();
        
        $paymentHistoryId = null;

        DB::transaction(function () use ($deptStudent, $user, $cleanPayment, $monthlyDept, $paidDate, $request, &$paymentHistoryId) {
            $remainingPayment = $cleanPayment;

            if ($deptStudent->payed > 0) {
                $neededToCompleteMonth = $monthlyDept - $deptStudent->payed;
                if ($remainingPayment >= $neededToCompleteMonth) {
                    $remainingPayment -= $neededToCompleteMonth;
                    $deptStudent->payed = 0;
                    $deptStudent->status_month++;
                    $user->status++;
                } else {
                    $deptStudent->payed += $remainingPayment;
                    $remainingPayment = 0;
                }
            }

            if ($monthlyDept > 0 && $remainingPayment >= $monthlyDept) {
                $fullMonthsPaid = floor($remainingPayment / $monthlyDept);
                $deptStudent->status_month += $fullMonthsPaid;
                $user->status += $fullMonthsPaid;
                $remainingPayment -= ($fullMonthsPaid * $monthlyDept);
            }

            if ($remainingPayment > 0) {
                $deptStudent->payed += $remainingPayment;
            }

            $deptStudent->date = $paidDate->format('Y-m-d');
            $deptStudent->save();
            $user->save();

            // Handle group name for history. If multiple groups, maybe join names or pick first.
            // For now, picking the first group name or 'No Group'
            $groupName = $user->groups->isNotEmpty() ? $user->groups->first()->name : 'No Group';

            $paymentHistory = HistoryPayments::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'payment' => $cleanPayment,
                'group' => $groupName,
                'date' => $paidDate->format('Y-m-d'),
                'type_of_money' => $request->money_type,
            ]);
            
            $paymentHistoryId = $paymentHistory->id;
        });

        // Redirect back with a success message and the ID of the payment history for the receipt
        return redirect()->back()
            ->with('success', 'To\'lov muvaffaqiyatli amalga oshirildi.');
//            ->with('payment_receipt_id', $paymentHistoryId);
    }

    /**
     * Generate and show a payment receipt (check).
     *
     * @param int $paymentId
     * @return \Illuminate\View\View
     */
    public function showReceipt(int $paymentId)
    {
        // Eager load groups instead of group
        $payment = HistoryPayments::with(['user.groups.room'])->findOrFail($paymentId);
        $student = $payment->user;
        $monthlyDept = $student->deptStudent->dept ?? $student->should_pay;

        return view('admin.pdf.chek', [
            'payment' => $payment,
            'student' => $student,
            'dept' => $monthlyDept,
        ]);
    }

    public function destroy(DeptStudent $deptStudent)
    {
        // This action is not implemented.
    }
}
