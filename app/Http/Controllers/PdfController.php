<?php

namespace App\Http\Controllers;

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
        $pdf = PDF::loadView('user.pdf.student_show', ['student' => $student]);
        return $pdf->download('orders.pdf');
        GeneratePdfJob::dispatch($id);
        return "PDF generation job dispatched successfully!";
    }
}
