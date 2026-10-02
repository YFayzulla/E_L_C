<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Centre;
use App\Models\Finance;
use App\Models\Group;
use App\Models\HistoryPayments;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Tenancy\TenantQuery;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Asosiy Dashboard sahifasi
     */
    public function index()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return redirect()->route('login');
            }

            // Apex domen: markaz yo'q. Bu yerda "boshqaruv paneli" degan
            // narsa yo'q — Spatie teams'siz rol so'rovlari bo'sh qaytadi va
            // sahifa jimgina bo'm-bo'sh chiqardi.
            if (Centre::current() === null) {
                return redirect()->route(
                    $user->is_super_admin ? 'super.centres.index' : 'centres.choose'
                );
            }

            // 1. ADMIN UCHUN DASHBOARD
            if ($user->hasRole('admin')) {
                $today = Carbon::today();

                // Hisob-kitoblar (Bularni keshlasa (Cache) yanada tezroq bo'ladi, hozircha oddiy optimizatsiya)
                $totalIncome = HistoryPayments::sum('payment');
                $totalConsumption = Finance::sum('payment');
                $profit = $totalIncome - $totalConsumption;

                // Optimallashtirilgan so'rovlar:
                // Select() orqali faqat kerakli ustunlarni olamiz (xotirani tejash uchun)
                $teachers = User::role('user') // Changed from 'user' to 'teacher'
                ->get();

                // get()->count() emas, to'g'ridan-to'g'ri count() ishlatamiz
                $numberOfStudents = User::role('student')->count();

                $dailyIncome = HistoryPayments::whereDate('created_at', $today)->sum('payment');

                $dailyTransactions = HistoryPayments::whereDate('created_at', $today)
                    ->select('payment', 'name', 'created_at') // Kerakli ustunlar
                    ->get();

                $debtorStudents = User::role('student')
                    ->where('status', '<', 0)
                    ->select('id', 'name', 'status') // Kerakli ustunlar
                    ->get();

                // Faqat kelmagan/kechikkanlar yoziladi; guruh ham yuklanadi (N+1 oldini olish)
                $todayAttendances = Attendance::whereDate('created_at', $today)
                    ->whereIn('status', [0, 2])
                    ->with(['user:id,name', 'group:id,name'])
                    ->get();

                return view('dashboard', array_merge([
                    'teachers' => $teachers,
                    'number_of_students' => $numberOfStudents,
                    'number_of_parents' => User::role('parent')->count(),
                    'daily_income' => $dailyIncome,
                    'daily_transactions' => $dailyTransactions,
                    'debtor_students' => $debtorStudents,
                    'today_attendances' => $todayAttendances,
                    'profit' => $profit,
                    'total_income' => $totalIncome,
                    'total_consumption' => $totalConsumption,
                    'pie_chart' => [$totalIncome, $totalConsumption]
                ], $this->adminWeeklyPanels()));
            }

            // 2. OTA-ONA UCHUN PORTAL
            if ($user->hasRole('parent')) {
                return redirect()->route('parent.index');
            }

            // 3. STUDENT UCHUN PAGE
            if ($user->hasRole('student')) {
                return view('studentPage', $this->studentDashboardData($user));
            }

            // 4. RECEPTION UCHUN ISH STOLI
            if ($user->hasRole('reception')) {
                return redirect()->route('reception.students.index');
            }

            // 5. HOMEWORK ASSISTANT UCHUN ISH STOLI
            if ($user->hasRole('assistant')) {
                return redirect()->route('assistant.homework.index');
            }

            // 6. O'QITUVCHI VA SUPPORT TEACHER UCHUN DASHBOARD
            //
            // Bir xil ma'lumot: ikkalasi ham `group_teachers` orqali
            // biriktiriladi, ya'ni "mening guruhlarim" ikkovi uchun ham
            // bir xil ma'noni beradi. Farq sidebarda — support faqat
            // baholash bo'limlarini ko'radi.
            if ($user->hasRole('user') || $user->hasRole('support')) {
                return view('dashboard', $this->teacherDashboardData($user));
            }


            return view('dashboard', [
                'teachers' => [],
                'number_of_students' => 0,
                'daily_income' => 0,
                'daily_transactions' => [],
                'debtor_students' => [],
                'today_attendances' => [],
                'profit' => 0,
                'pie_chart' => [0, 0]
            ]);

        } catch (\Exception $e) {
            // Xatolikni logga yozamiz
            Log::error('Dashboard index error: ' . $e->getMessage());

            // Foydalanuvchiga xatolik haqida xabar beramiz yoki bo'sh sahifa ochamiz
            return abort(500, 'Serverda xatolik yuz berdi. Iltimos keyinroq urining.');
        }
    }

    /**
     * Weekly attendance, income and teacher figures for the admin dashboard.
     *
     * Everything is a grouped aggregate — no per-teacher or per-student query in
     * a loop, so the panel cost does not grow with the size of the centre.
     */
    private function adminWeeklyPanels(): array
    {
        $weekStart = Carbon::today()->subDays(6);   // last 7 days inclusive
        $days = [];

        for ($d = 0; $d < 7; $d++) {
            $days[] = $weekStart->copy()->addDays($d);
        }

        $from = $weekStart->copy()->startOfDay();
        $to   = Carbon::today()->endOfDay();

        // --- lessons held per day (the attendance denominator) ---------------
        $lessonsPerDay = LessonAndHistory::where('data', 1)
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'group'])
            ->groupBy(fn($row) => $row->created_at->toDateString())
            ->map(fn($rows) => $rows->count());

        // --- absences / lates per day ---------------------------------------
        $marksPerDay = Attendance::whereIn('status', [0, 2])
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'status'])
            ->groupBy(fn($row) => $row->created_at->toDateString());

        // --- income per day --------------------------------------------------
        $incomePerDay = HistoryPayments::whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'payment'])
            ->groupBy(fn($row) => $row->created_at->toDateString())
            ->map(fn($rows) => (int) $rows->sum('payment'));

        $week = [];
        $weekIncome = 0;
        $weekAbsent = 0;
        $weekLate   = 0;
        $weekLessons = 0;

        foreach ($days as $day) {
            $key = $day->toDateString();
            $marks = $marksPerDay->get($key, collect());

            $absent = $marks->where('status', 0)->count();
            $late   = $marks->where('status', 2)->count();
            $lessons = (int) $lessonsPerDay->get($key, 0);
            $income = (int) $incomePerDay->get($key, 0);

            $week[] = [
                'date'    => $day,
                'label'   => $day->translatedFormat('D'),
                'short'   => $day->format('d.m'),
                'lessons' => $lessons,
                'absent'  => $absent,
                'late'    => $late,
                'income'  => $income,
            ];

            $weekLessons += $lessons;
            $weekAbsent  += $absent;
            $weekLate    += $late;
            $weekIncome  += $income;
        }

        // Include every current-centre teacher, even if no group is attached yet.
        $teacherRows = User::role(['user', 'support'])
            ->inCurrentCentre()
            ->orderBy('name')
            ->get(['id', 'name', 'photo', 'percent']);
        $teacherIds = $teacherRows->pluck('id')->all();

        $teacherGroups = TenantQuery::table('group_teachers')
            ->whereIn('teacher_id', $teacherIds ?: [0])
            ->get(['teacher_id', 'group_id'])
            ->groupBy('teacher_id');

        $teacherStats = TenantQuery::table('group_teachers')
            ->leftJoin('group_user', function (JoinClause $join) {
                $join->on('group_user.group_id', '=', 'group_teachers.group_id');
                TenantQuery::constrain($join, 'group_user');
            })
            ->whereIn('group_teachers.teacher_id', $teacherIds ?: [0])
            ->select('group_teachers.teacher_id')
            ->selectRaw('COUNT(DISTINCT group_teachers.group_id) as groups_count')
            ->selectRaw('COUNT(DISTINCT group_user.user_id) as students_count')
            ->groupBy('group_teachers.teacher_id')
            ->get()
            ->keyBy('teacher_id');

        $groupTotals = TenantQuery::table('group_user')
            ->select('group_id', DB::raw('SUM(payment) as total'))
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $centrePercentages = collect();
        $centreId = Centre::currentId();
        if ($centreId !== null && $teacherIds !== []) {
            $centrePercentages = DB::table('centre_user')
                ->where('centre_id', $centreId)
                ->where('status', Centre::MEMBER_ACTIVE)
                ->whereIn('user_id', $teacherIds)
                ->pluck('percent', 'user_id');
        }

        $teacherPanel = $teacherRows->map(function ($row) use ($groupTotals, $teacherGroups, $teacherStats, $centrePercentages) {
            $gross = 0;

            foreach ($teacherGroups->get($row->id, collect()) as $link) {
                $gross += (int) ($groupTotals[$link->group_id] ?? 0);
            }

            $membershipPercent = $centrePercentages->get($row->id);
            $percent = $membershipPercent !== null ? (int) $membershipPercent : (int) $row->percent;
            $stats = $teacherStats->get($row->id);

            return [
                'id'       => (int) $row->id,
                'name'     => $row->name,
                'photo'    => $row->photo,
                'percent'  => $percent,
                'groups'   => (int) ($stats->groups_count ?? 0),
                'students' => (int) ($stats->students_count ?? 0),
                'salary'   => (int) round($gross * $percent / 100),
            ];
        })->values();

        $slots = $weekLessons > 0 ? $weekLessons : 0;

        return [
            'week'            => $week,
            'week_income'     => $weekIncome,
            'week_absent'     => $weekAbsent,
            'week_late'       => $weekLate,
            'week_lessons'    => $weekLessons,
            'week_max_income' => collect($week)->max('income') ?: 0,
            'week_max_marks'  => collect($week)->max(fn($d) => $d['absent'] + $d['late']) ?: 0,
            'teacher_panel'   => $teacherPanel,
            'active_groups'   => Group::active()->teaching()->count(),
            'finished_groups' => Group::finished()->count(),
            'graduates'       => User::role('student')->where('study_status', User::STUDY_GRADUATED)->count(),
        ];
    }

    /**
     * Talaba paneli uchun ma'lumotlar.
     */
    private function studentDashboardData(User $student): array
    {
        $student->loadMissing(['groups.teachers', 'groups.room', 'deptStudent']);

        $absences = Attendance::where('user_id', $student->id)
            ->whereIn('status', [0, 2])
            ->with(['group', 'lesson'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $testResults = Assessment::where('assessments.user_id', $student->id)
            ->leftJoin('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
            ->select('assessments.*', 'lesson_and_histories.name as test_name')
            ->orderByDesc('assessments.created_at')
            ->limit(8)
            ->get();

        return [
            'student' => $student,
            'attendance_rate' => $student->attendanceRate(),
            // O'zlashtirish bosh sahifada ham ko'rinadi: talaba alohida
            // bo'limga o'tmasdan holatini ko'ra olsin. forStudent() doimiy
            // sondagi guruhlangan so'rov qiladi, ya'ni sahifaga qo'shimcha
            // yuk bermaydi.
            'progress' => app(\App\Services\ProgressService::class)->forStudent($student->id),
            'absences' => $absences,
            'absences_this_month' => Attendance::where('user_id', $student->id)
                ->whereIn('status', [0, 2])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'test_results' => $testResults,
            'payments' => HistoryPayments::where('user_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * O'qituvchi paneli uchun ma'lumotlar.
     */
    private function teacherDashboardData(User $teacher): array
    {
        $groups = $teacher->teacherGroups()->with('students')->get();
        $groupIds = $groups->pluck('id');

        return [
            'groups' => $groups,
            'student_count' => $teacher->teacherHasStudents(),
            // Hisoblangan oylik o'qituvchining o'z panelidan olib tashlandi —
            // u faqat admin ro'yxatida ko'rinadi (TeacherController@index).
            'today_absences' => Attendance::whereIn('group_id', $groupIds)
                ->whereIn('status', [0, 2])
                ->whereDate('created_at', Carbon::today())
                ->with(['user:id,name', 'group:id,name'])
                ->get(),
            'recent_lessons' => LessonAndHistory::whereIn('group', $groupIds)
                ->where('data', 1)
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * To'lovlar tarixini qidirish
     */
    public function search(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $query = HistoryPayments::query();

            $start = $request->start_date;
            $end = $request->end_date;


            if ($start && $end) {
                // Case: Both dates selected -> Get the range
                $query->whereBetween('date', [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay()
                ]);
            } elseif ($start) {
                // Case: Only start_date -> From that day until now
                $query->where('date', '>=', Carbon::parse($start)->startOfDay());
            } elseif ($end) {
                // Case: Only end_date -> Exactly that one day only
                // whereDate ignores the time and matches only the Y-m-d
                $query->whereDate('date', Carbon::parse($end)->toDateString());
            }

            // Get results with pagination to avoid memory issues
            $historyPayments = $query
                ->latest('date')
                ->get();

            return view('admin.index', [
                'historyPayments' => $historyPayments,
                'start_date' => $start,
                'end_date' => $end,
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Xatolik: ' . $e->getMessage());
        }
    }
}
