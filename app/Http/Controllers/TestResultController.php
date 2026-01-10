<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Group;
use App\Models\LessonAndHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestResultController extends Controller
{
    /**
     * Test natijalari tarixi va eng a'lochi talabalar.
     */
    public function index()
    {
        try {
            // 1. Test tarixlarini olish (data = 2 bu test ekanligini bildiradi)
            $testHistories = LessonAndHistory::where('data', 2)
                ->latest() // Eng oxirgi testlar tepada turishi uchun
                ->get();

            // 2. Top 5 talaba
            // OPTIMIZATSIYA: with('student') qo'shildi.
            // Bu orqali View faylida talaba ismini olishda ortiqcha so'rovlar bo'lmaydi.
            $topStudents = Assessment::with('student:id,name,photo') // Faqat kerakli ustunlar
            ->orderBy('get_mark', 'desc')
                ->take(5)
                ->get();

            return view('assessment.main', [
                'data'        => $testHistories,
                'topStudents' => $topStudents
            ]);

        } catch (\Exception $e) {
            Log::error('TestResultController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Natijalarni yuklashda xatolik yuz berdi.');
        }
    }

    /**
     * Konkret test natijalarini ko'rish.
     *
     * @param int $historyId LessonAndHistory ID
     */
    public function showResults($historyId)
    {
        try {
            // 1. Avval tarix mavjudligini tekshiramiz (Validatsiya)
            // Agar history_id noto'g'ri bo'lsa, 404 qaytaradi.
            $history = LessonAndHistory::findOrFail($historyId);

            // 2. Baholarni olish
            // OPTIMIZATSIYA: with('student') - N+1 muammosini yechish uchun
            $assessments = Assessment::where('history_id', $historyId)
                ->with('student:id,name,code') // Talaba ma'lumotlarini oldindan yuklash
                ->get();

            // 3. Guruh ma'lumotlarini aniqlash
            // Assessment jadvalida group ID emas, Name saqlanganligi uchun shunday qidiramiz
            $groupName = $assessments->isNotEmpty() ? $assessments->first()->group : null;
            $group = $groupName ? Group::where('name', $groupName)->first() : null; // Guruh ID sini olish uchun

            // Sidebar uchun barcha guruhlar ro'yxati (select name, id yetarli)
            $allGroups = Group::select('id', 'name')->orderBy('name')->get();

            return view('assessment.index', [
                'assessments' => $assessments,
                'groups'      => $allGroups,
                'id'          => $group ? $group->id : null,
                'groupName'   => $groupName,
                'historyId'   => $historyId
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('test')->with('error', 'Test tarixi topilmadi.');
        } catch (\Exception $e) {
            Log::error('TestResultController@showResults error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Natijalarni ko\'rsatishda xatolik.');
        }
    }

    /**
     * Barcha baholarni va test tarixini o'chirish.
     *
     * @param int $historyId LessonAndHistory ID
     */
    public function destroyAll($historyId)
    {
        try {
            // 1. Tegishli barcha baholarni o'chirish
            Assessment::where('history_id', $historyId)->delete();

            // 2. Test tarixini o'chirish
            $history = LessonAndHistory::findOrFail($historyId);
            $history->delete();

            // Asosiy testlar sahifasiga muvaffaqiyat xabari bilan qaytish
            return redirect()->route('test')->with('success', 'Test natijalari va tarixi muvaffaqiyatli o\'chirildi.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('test')->with('error', 'O\'chiriladigan test tarixi topilmadi.');
        } catch (\Exception $e) {
            Log::error('TestResultController@destroyAll error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ma\'lumotlarni o\'chirishda xatolik yuz berdi.');
        }
    }
}