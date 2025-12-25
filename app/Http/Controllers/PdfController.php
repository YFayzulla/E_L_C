<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\HistoryPayments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PDF; // Assuming Barryvdh\DomPDF\Facade\Pdf is used

class PdfController extends Controller
{
    public function __construct()
    {
        // Increase execution time and memory limit for PDF generation
        set_time_limit(300);
        ini_set('memory_limit', '512M'); // Increased to 512M
    }

    /**
     * Download payment history as PDF.
     */
    public function RoomListPDF(Request $request)
    {
        $request->validate([
            'startDate' => 'nullable|date',
            'endDate'   => 'nullable|date',
        ]);

        try {
            $startDate = $request->input('startDate') ? Carbon::parse($request->input('startDate')) : null;
            $endDate   = $request->input('endDate') ? Carbon::parse($request->input('endDate')) : null;

            $query = HistoryPayments::select('id', 'name', 'payment', 'date', 'group', 'type_of_money');

            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate->startOfDay(), $endDate->endOfDay()]);
            } elseif ($startDate) {
                $query->whereDate('date', $startDate);
            } elseif ($endDate) {
                $query->whereDate('date', $endDate);
            }

            $payments = $query->latest('date')->get();

            $pdf = PDF::loadView('admin.pdf.payments', ['users' => $payments]);
            $fileName = 'payments_report_' . now()->format('Y-m-d_H-i') . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('PdfController@RoomListPDF error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'PDF hisobotini yaratishda xatolik yuz berdi.');
        }
    }

    /**
     * Student's personal history and attendance.
     */
    public function history($id)
    {
        try {
            $student = User::with('groups') // Eager load groups without column constraints
                ->findOrFail($id);

            // Fetch attendances with necessary relationships
            $attendances = Attendance::where('user_id', $id)
                ->with(['group', 'user']) // Load group and user info
                ->latest()
                ->get();

            $pdf = PDF::loadView('admin.pdf.student_show', [
                'student'     => $student,
                'attendances' => $attendances
            ]);

            $fileName = 'student_history_' . $student->id . '_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Talaba topilmadi.');
        } catch (\Exception $e) {
            Log::error('PdfController@history error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talaba tarixini yuklashda xatolik.');
        }
    }

    /**
     * Teachers list PDF.
     */
    public function teacher()
    {
        try {
            $teachers = User::role('user')
                ->select('id', 'name', 'phone')
                ->orderBy('name')
                ->get();

            $pdf = PDF::loadView('admin.pdf.teacher', ['teacher' => $teachers]);
            $fileName = 'teachers_list_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('PdfController@teacher error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'qituvchilar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Groups list PDF.
     */
    public function group()
    {
        try {
            $groups = Group::where('id', '!=', 1)
                ->select('id', 'name', 'start_time', 'finish_time', 'monthly_payment')
                ->orderBy('name')
                ->get();

            $pdf = PDF::loadView('admin.pdf.group', ['group' => $groups]);
            $fileName = 'groups_list_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('PdfController@group error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Students list PDF.
     */
    public function student()
    {
        try {
            // Select only necessary columns to reduce memory usage
            $students = User::role('student')
                ->select('id', 'name', 'phone', 'parents_tel', 'parents_name', 'should_pay')
                ->with('groups:id,name') // Load only id and name of groups
                ->orderBy('name')
                ->get();

            $pdf = PDF::loadView('admin.pdf.student', ['student' => $students]);
            $fileName = 'students_list_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('PdfController@student error: ' . $e->getMessage());
            // Return the actual error message to the user for now (for debugging purposes)
            return redirect()->back()->with('error', 'Xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Group assessment PDF.
     */
    public function Assessment($id)
    {
        try {
            $group = Group::findOrFail($id);
            
            // Assessment jadvalida 'group' ustuni string bo'lgani uchun, nom bo'yicha qidiramiz
            $assessments = Assessment::where('group', $group->name)
                ->with('user:id,name') // Eager load student name
                ->get();

            if ($assessments->isEmpty()) {
                 return redirect()->back()->with('warning', 'Ushbu guruh uchun baholash natijalari topilmadi.');
            }
            
            $groupName = $group->name;

            $pdf = PDF::loadView('admin.pdf.group_assessment', ['groups' => $assessments, 'groupName' => $groupName]);
            $fileName = 'group_assessment_' . str_replace(' ', '_', $groupName) . '_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error('PdfController@Assessment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Baho hisobotini yuklashda xatolik.');
        }
    }
}
