<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\HistoryPayments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDF;

class PdfController extends Controller
{
    public function RoomListPDF(Request $request)
    {
        // Fetch table data based on flexible date range
        $tableData = HistoryPayments::query()
            ->when($request->startDate && $request->endDate, function ($query) use ($request) {
                // If both startDate and endDate are provided, filter between these dates
                $query->whereBetween('date', [$request->startDate, $request->endDate]);
            })
            ->when($request->startDate && !$request->endDate, function ($query) use ($request) {
                // If only startDate is provided, treat it as a single day filter
                $query->whereDate('date', $request->startDate);
            })
            ->when(!$request->startDate && $request->endDate, function ($query) use ($request) {
                // If only endDate is provided, treat it as a single day filter
                $query->whereDate('date', $request->endDate);
            })
            ->get();

        // Generate the PDF
        $pdf = PDF::loadView('user.pdf.payments', ['users' => $tableData]);

        // Download the PDF or display it in the browser
        return $pdf->download('orders.pdf');
    }

    public function history($id)
    {
        set_time_limit(300); // Set to a value greater than 60 seconds
        $today = now()->toDateString();
        $student = User::find($id);
        $attendances = Attendance::where('user_id', $id)->get();
        $pdf = PDF::loadView('user.pdf.student_show', ['student' => $student, 'attendances' => $attendances]);
        return $pdf->download('orders.pdf');
        GeneratePdfJob::dispatch($id);
        return "PDF generation job dispatched successfully!";
    }

    public function teacher()
    {
        set_time_limit(300); // Set to a value greater than 60 seconds
        $today = now()->toDateString();
        $teacher = User::role('user')->get();
        $pdf = PDF::loadView('user.pdf.teacher', ['teacher' => $teacher]);
        return $pdf->download('orders.pdf');

        return "PDF generation job dispatched successfully!";
    }

    public function group()
    {
        set_time_limit(300); // Set to a value greater than 60 seconds
        $today = now()->toDateString();
        $group = Group::where('id','!=',1)->get();
        $pdf = PDF::loadView('user.pdf.group', ['group' => $group]);
        return $pdf->download('orders.pdf');

        return "PDF generation job dispatched successfully!";
    }


    public function student()
    {
        set_time_limit(300); // Set to a value greater than 60 seconds
        $today = now()->toDateString();
        $student = User::role('student')->get();
        $pdf = PDF::loadView('user.pdf.student', ['student' => $student]);
        return $pdf->download('orders.pdf');

        return "PDF generation job dispatched successfully!";
    }

    public function Assessment($id)
    {

        $groups = Assessment::where('group', $id)->get();

        $pdf = PDF::loadView('user.pdf.group_assessment', ['groups' => $groups]);

        return $pdf->download('orders.pdf');

    }
}
