<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaitersController extends Controller
{
    /**
     * Kutish zalidagi (Guruhsiz) talabalar ro'yxati.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        try {
            // 1. Mavjud guruhlarni olish (Kutish zali ID=1 dan tashqari)
            // OPTIMIZATSIYA: Faqat kerakli ustunlarni (id, name) olish.
            // View faylida guruhni tanlash (select option) uchun shu yetarli.
            $groups = Group::select('id', 'name')
                ->where('id', '!=', 1)
                ->orderBy('name')
                ->get();

            // 2. Kutish zalidagi talabalarni olish (group_id = 1)
            // OPTIMIZATSIYA:
            // - paginate(20): Ro'yxat uzun bo'lsa, sayt qotmaydi.
            // - select(...): Faqat kerakli ma'lumotlarni olamiz.
            // - latest(): Eng oxirgi ro'yxatdan o'tganlar tepada turadi.
            $students = User::role('student')
                ->where('group_id', 1)
                ->select('id', 'name', 'phone', 'created_at', 'image') // Viewga kerakli ustunlarni yozing
                ->latest('created_at') // Yoki ->orderBy('name')
                ->paginate(20);

            return view('admin.waiters.index', compact('students', 'groups'));

        } catch (\Exception $e) {
            Log::error('WaitersController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Kutish zali ma\'lumotlarini yuklashda xatolik yuz berdi.');
        }
    }
}