<?php

namespace App\Services;

use App\Models\DeptStudent;
use App\Models\User;

class StudentPaymentSummaryService
{
    /**
     * Change the student's monthly base without wiping already-paid credit.
     *
     * `dept_students.dept` is the current monthly price, while `users.status`
     * and `dept_students.payed/status_month` hold paid months or partial credit.
     * Group transfers must update only the monthly price and preserve credit.
     */
    public function changeMonthlyPayment(User $student, int $monthlyPayment): DeptStudent
    {
        $monthlyPayment = max(0, $monthlyPayment);

        $dept = $student->deptStudent()->firstOrCreate(
            ['user_id' => $student->id],
            ['dept' => 0, 'payed' => 0, 'status_month' => 0]
        );

        $partial = max(0, (int) $dept->payed);
        $paidMonths = max(0, (int) $dept->status_month);
        $userStatus = $student->status;

        if ($monthlyPayment <= 0) {
            $dept->dept = 0;
            $dept->payed = 0;
            $dept->status_month = 0;
            $dept->save();

            $student->should_pay = 0;
            $student->status = 0;
            $student->save();

            return $dept;
        }

        while ($partial >= $monthlyPayment) {
            $partial -= $monthlyPayment;
            $paidMonths++;
            $userStatus = (int) ($userStatus ?? 0) + 1;
        }

        $dept->dept = $monthlyPayment;
        $dept->payed = $partial;
        $dept->status_month = $paidMonths;
        $dept->save();

        $student->should_pay = $monthlyPayment;
        $student->status = $userStatus;
        $student->save();

        return $dept;
    }
}
