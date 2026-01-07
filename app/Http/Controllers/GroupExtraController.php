<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class GroupExtraController extends Controller
{
    public function __construct(protected AttendanceService $serviceAttendance)
    {
        // Service avtomatik inject qilinadi
    }

    /**
     * Ko'plab baholarni birdaniga o'chirish.
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate(['selectedItems' => 'required|array']);

        try {
            $selectedItems = $request->input('selectedItems');

            // Xavfsizlik: Faqat raqamli ID larni ajratib olish
            $validatedItems = array_filter($selectedItems, 'is_numeric');

            if (!empty($validatedItems)) {
                // Tranzaksiya shart emas (bitta query), lekin try-catch muhim
                Assessment::whereIn('id', $validatedItems)->delete();
            }

            return redirect()->back()->with('success', 'Tanlangan elementlar muvaffaqiyatli o\'chirildi.');

        } catch (\Exception $e) {
            Log::error('GroupExtraController@deleteMultiple error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirish jarayonida xatolik yuz berdi.');
        }
    }

    /**
     * Talabaning guruhini o'zgartirish.
     */
    public function change_group(Request $request, $id)
    {
        $request->validate(['group_id' => 'required|array']);

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $groupIds = $request->group_id;

            // 1. User guruhini yangilash (Eski guruhlardan chiqarib, yangisiga qo'shish)
            // Agar faqat qo'shish kerak bo'lsa attach() ishlatilardi, lekin "change" bo'lgani uchun sync()
            $user->groups()->sync($groupIds);

            // 2. Tarix (StudentInformation) yaratish
            $groups = Group::whereIn('id', $groupIds)->get();
            foreach($groups as $group){
                StudentInformation::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'group' => $group->name,
                ]);
            }

            // 3. Eski davomatlarni yangi guruhga o'tkazish
            // (Mantiqan to'g'riligini loyiha talabidan kelib chiqib tekshiring.
            // Odatda eski davomat eski guruhda qolishi kerak, lekin sizning kodingizda o'zgartirilmoqda)
            // Agar ko'p guruh tanlansa, qaysi biriga o'tkazish noaniq bo'ladi.
            // Shuning uchun birinchi tanlangan guruhga o'tkazamiz yoki bu qismni o'chirib tashlaymiz.
            if (!empty($groupIds)) {
                Attendance::where('user_id', $user->id)->update(['group_id' => $groupIds[0]]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Guruh muvaffaqiyatli o\'zgartirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GroupExtraController@change_group error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhni o\'zgartirishda xatolik yuz berdi.');
        }
    }

    /**
     * Davomatni filtrlash yoki PDF hisobot chiqarish.
     */
    public function filter(Request $request, $id)
    {
        try {
            $selectedDate = $request->filled('filter_date') ? Carbon::parse($request->input('filter_date')) : Carbon::today();
            $group = Group::findOrFail($id);
            $task = $request->input('task');

            // Umumiy query
            $items = Attendance::whereDate('created_at', $selectedDate)
                ->where('group_id', $id)
                ->with('user:id,name') // Optimizatsiya: N+1 oldini olish
                ->get();

            if ($task === 'show') {
                // Endi bu metod ishlatilmasligi mumkin, chunki biz attendance metodini o'zgartirdik.
                // Lekin agar filter alohida ishlatilsa, uni ham teacher view ga yo'naltirish kerak.
                // Hozircha qoldiramiz, lekin attendance metodi asosiy hisoblanadi.
                return view('admin.group.attendance', compact('items', 'group', 'selectedDate'));
            }

            if ($task === 'report') {
                try {
                    $pdf = PDF::loadView('admin.pdf.attendance_in_group', [
                        'items' => $items,
                        'group' => $group,
                        'date' => $selectedDate
                    ]);

                    $fileName = 'attendance_report_' . $group->name . '_' . $selectedDate->format('Y-m-d') . '.pdf';
                    return $pdf->download($fileName);
                } catch (\Exception $pdfEx) {
                    Log::error('PDF Generation error: ' . $pdfEx->getMessage());
                    return redirect()->back()->with('error', 'PDF hujjatini yaratishda xatolik.');
                }
            }

            return redirect()->back()->with('error', 'Noto\'g\'ri amal tanlandi.');

        } catch (\Exception $e) {
            Log::error('GroupExtraController@filter error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ma\'lumotlarni yuklashda xatolik.');
        }
    }

    /**
     * Guruhdagi talabalar ro'yxatini ko'rsatish.
     */
    public function show($id)
    {
        try {
            // Guruhga tegishli talabalarni pivot jadval orqali olish
            $students = User::whereHas('groups', function ($query) use ($id) {
                    $query->where('groups.id', $id);
                })
                ->role('student')
                // Guruh nomini olish uchun relationshipni yuklaymiz
                ->with('groups:id,name')
                ->orderBy('name')
                ->select('id', 'name', 'phone', 'status') // group_id olib tashlandi
                ->get();
                
            return view('admin.group.student', compact('students'));
        } catch (\Exception $e) {
            Log::error('GroupExtraController@show error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talabalar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Oylik davomat jadvalini shakllantirish (Matritsa).
     */
    public function attendance($id)
    {
        try {
            // Use the shared AttendanceService to get data
            $serviceData = $this->serviceAttendance->attendance($id);

            // Return the TEACHER view instead of the admin view
            return view('teacher.attendance.attendance', [
                'id' => $id,
                'today' => $serviceData['today'] ?? now(),
                'data' => $serviceData['data'] ?? [],
                'year' => $serviceData['year'] ?? date('Y'),
                'month' => $serviceData['month'] ?? date('m'),
                'lessonDays' => $serviceData['lessonDays'] ?? [],
                'attendances' => $serviceData['attendances'] ?? [],
                'group' => $serviceData['group'] ?? null,
                'students' => $serviceData['students'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('GroupExtraController@attendance error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Davomat jadvalini yuklashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Excelga eksport qilish.
     */
    public function export($id)
    {
        try {
            $date = request('date', now()->format('Y-m'));
            // Sana formatini tekshirish uchun oddiy parsing
            $parts = explode('-', $date);


            if (count($parts) !== 2) {
                return redirect()->back()->with('error', 'Noto\'g\'ri sana formati.');
            }

            list($year, $month) = $parts;


            $group = Group::findOrFail($id);
            $fileName = 'attendance_' . $group->name . '_' . $year . '_' . $month . '.xlsx';

            return Excel::download(new AttendanceExport($id, $year, $month), $fileName);

        } catch (\Exception $e) {
            Log::error('GroupExtraController@export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Excel faylini yuklashda xatolik yuz berdi.');
        }
    }
}
