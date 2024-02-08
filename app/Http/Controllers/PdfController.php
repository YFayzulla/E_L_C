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
    public function RoomListPDF($date)
    {
        $today = now()->toDateString();

        // Fetch your table data here (e.g., from a database)
        $tableData = HistoryPayments::where('date', $date)->get();

        // Generate the PDF
        $pdf = PDF::loadView('user.pdf.payments', ['users' => $tableData]);

        // Optionally, you can customize the PDF settings here, e.g., set paper size, orientation, etc.

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

    public function Assessment($id)
    {

        $pdf = PDF::loadView('user.pdf.student_show', ['student' => $student, 'attendances' => $attendances]);
        $groups = Assessment::where('group', $id)->get();

        $groups = Assessment::where('group_id', $id)->orderby('created_at')->get();
        $pdf = PDF::loadView('user.pdf.group_assessment', ['groups' => $groups]);

        return $pdf->download('orders.pdf');
    }
}
