<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Finance;
use App\Models\HistoryPayments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

                $todayAttendances = Attendance::whereDate('created_at', $today)
                    ->with('user:id,name') // Agar user bog'langan bo'lsa, N+1 muammosini oldini olish
                    ->get();

                return view('dashboard', [
                    'teachers' => $teachers,
                    'number_of_students' => $numberOfStudents,
                    'daily_income' => $dailyIncome,
                    'daily_transactions' => $dailyTransactions,
                    'debtor_students' => $debtorStudents,
                    'today_attendances' => $todayAttendances,
                    'profit' => $profit,
                    'pie_chart' => [$totalIncome, $totalConsumption]
                ]);
            }

            // 2. STUDENT UCHUN PAGE
            if ($user->hasRole('student')) {
                return view('studentPage');
            }

            // 3. BOSHQA FOYDALANUVCHILAR UCHUN (Bo'sh dashboard)
            // Bu yerda 'teacher' roli uchun ham alohida mantiq bo'lishi kerak
            if ($user->hasRole('user')) {
                // O'qituvchi uchun dashboard yoki boshqa sahifaga yo'naltirish
                // Hozircha 'dashboard' ga yo'naltiramiz, lekin uning kontenti bo'sh bo'ladi
                // yoki o'qituvchiga mos kontent bilan to'ldirilishi kerak.
                return view('dashboard', [
                    'teachers' => [], // O'qituvchi o'zini ko'rmaydi
                    'number_of_students' => 0,
                    'daily_income' => 0,
                    'daily_transactions' => [],
                    'debtor_students' => [],
                    'today_attendances' => [],
                    'profit' => 0,
                    'pie_chart' => [0, 0]
                ]);
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
     * To'lovlar tarixini qidirish
     */
    public function search(Request $request)
    {
        // Validatsiya: Sanalar to'g'ri formatda ekanligini tekshiramiz
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

            $query = HistoryPayments::query();

            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]); // Yoki created_at bo'lsa o'sha ustun
            } elseif ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            } elseif ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }

            // Natijalarni olish (Pagination qo'shish tavsiya etiladi agar ma'lumot ko'p bo'lsa)
            $historyPayments = $query->latest()->get();

            return view('admin.index', [
                'historyPayments' => $historyPayments,
                'start_date' => $startDate ? $startDate->toDateString() : null,
                'end_date' => $endDate ? $endDate->toDateString() : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Qidiruv vaqtida xatolik yuz berdi.');
        }
    }
}
