<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\HistoryPayments;
use App\Models\LessonAndHistory;
use App\Models\StudentDiscount;
use App\Models\StudentInformation;
use App\Models\User;
use App\Services\ParentAccountService;
use App\Services\ProgressService;
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
                $query->where('date', '>=', $startDate->startOfDay());
            } elseif ($endDate) {
                $query->whereDate('date', $endDate);
            }

            $payments = $query->orderByDesc('date')->orderByDesc('id')->get();

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
            // inCurrentCentre(): `users` da centre_id yo'q, ya'ni global scope
            // bu yerga tegmaydi va id URL'dan keladi.
            $student = User::inCurrentCentre()
                ->with(['groups' => fn ($q) => $q->orderBy('groups.name')->orderBy('groups.id')])
                ->findOrFail($id);

            // Talaba sahifasidagi barcha bo'limlar — PDF ham sahifa tartibida.
            // Davomatda faqat kelmagan / kechikkan kunlar yoziladi (kelganlar
            // uchun qator yo'q).
            $attendances = Attendance::where('user_id', $id)
                ->with('group:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $groupHistory = StudentInformation::where('user_id', $id)
                ->with('actor:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $discounts = StudentDiscount::where('user_id', $student->id)
                ->with('group:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            $pdf = PDF::loadView('admin.pdf.student_show', [
                'student'      => $student,
                'attendances'  => $attendances,
                'groupHistory' => $groupHistory,
                'testResults'  => Assessment::testsFor((int) $id),
                'progress'     => app(ProgressService::class)->forStudent((int) $id),
                'guardians'    => app(ParentAccountService::class)->guardiansOf($student),
                'discounts'    => $discounts,
                'payments'     => $student->studenthistory()->orderByDesc('date')->orderByDesc('id')->get(),
                'photo'        => $this->embeddedPhoto($student->photo),
            ])->setOption('isFontSubsettingEnabled', true); // shriftdan faqat ishlatilgan harflar — fayl kichik

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
     * Talaba rasmi PDF ichiga data URI sifatida (dompdf fayl yo'liga
     * ishonmasin). Rasm yo'q yoki juda katta bo'lsa — null.
     */
    private function embeddedPhoto(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $file = storage_path('app/public/' . ltrim($path, '/'));

        if (! is_file($file) || filesize($file) > 3 * 1024 * 1024) {
            return null;
        }

        $mime = mime_content_type($file) ?: '';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }

    /**
     * Teachers list PDF.
     */
    public function teacher()
    {
        try {
            // Blade `location` va `date_born` ni ham chop etadi - ular select'da
            // bo'lmagani uchun ustunlar doim bo'sh chiqardi.
            $teachers = User::role('user')
                ->select('id', 'name', 'phone', 'location', 'date_born', 'percent')
                ->with('teacherGroups:id,name')
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
            $groups = Group::teaching()
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
    /**
     * Bitta test bo'yicha hisobot: bitta talaba — bitta qator, ko'nikmalar
     * ustunlarda, o'rtacha va yig'indi bilan (Test natijalari sahifasi kabi).
     */
    public function testReport(int $historyId)
    {
        $history = LessonAndHistory::findOrFail($historyId);

        $assessments = Assessment::where('history_id', $history->id)
            ->with('student:id,name')
            ->orderBy('id')
            ->get();

        if ($assessments->isEmpty()) {
            return redirect()->back()->with('warning', 'Bu test uchun natija topilmadi.');
        }

        $results = Assessment::byStudent($assessments);
        $groupName = (string) $assessments->first()->group;

        $pdf = PDF::loadView('admin.pdf.test_results', [
            'skills'    => $results['skills'],
            'maxes'     => $results['maxes'],
            'students'  => $results['rows'],
            'groupName' => $groupName,
            'testName'  => $history->name,
            'date'      => $history->created_at,
        ]);

        return $pdf->download('test_' . str_replace(' ', '_', $groupName) . '_' . $history->id . '.pdf');
    }

    public function Assessment($id)
    {
        try {
            $group = Group::findOrFail($id);
            
            // Assessment jadvalida 'group' ustuni string bo'lgani uchun, nom bo'yicha qidiramiz
            // Assessment da `user` munosabati yo'q — `student`. Ilgari bu
            // yerda with('user') bo'lgani uchun hisobot har safar yiqilardi.
            $assessments = Assessment::where('group', $group->name)
                ->with('student:id,name')
                ->orderBy('user_id')
                ->orderBy('id')
                ->get()
                ->sortBy(fn ($assessment) => mb_strtolower($assessment->student->name ?? ''), SORT_NATURAL)
                ->values();

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
