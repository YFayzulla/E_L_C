<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreRequest;
use App\Http\Requests\Finance\UpdateRequest;
use App\Models\Finance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceController extends Controller
{
    /**
     * Xarajatlar ro'yxati va umumiy hisobot.
     */
    public function index()
    {
        try {
            // OPTIMIZATSIYA:
            // 1. paginate() ishlatildi (sahifalash). Agar ma'lumot ko'payib ketsa, sayt qotmaydi.
            // 2. latest() qo'shildi, eng yangi xarajatlar tepada turadi.
            $finances = Finance::latest()->paginate(20);

            // PHP da hisoblash o'rniga SQL da hisoblaymiz (bu tezroq va xotirani tejaydi)
            $totalConsumption = Finance::sum('payment');

            // Bugungi xarajatlarni hisoblash (whereDate funksiyasi aniqroq ishlaydi)
            $dailyConsumption = Finance::whereDate('created_at', Carbon::today())->sum('payment');

            return view('admin.finance.index', [
                'finances'          => $finances,
                'consumption'       => $totalConsumption,
                'daily_consumption' => $dailyConsumption
            ]);
        } catch (\Exception $e) {
            Log::error('FinanceController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Moliya ma\'lumotlarini yuklashda xatolik.');
        }
    }

    /**
     * Yangi xarajat qo'shish.
     */
    public function store(StoreRequest $request)
    {
        // Validatsiya StoreRequest ichida avtomatik bajariladi.
        // Agar xato bo'lsa, kod bu yerga yetib kelmaydi.

        DB::beginTransaction();

        try {
            Finance::create($request->validated());

            DB::commit();

            return redirect()->back()->with('success', 'Xarajat muvaffaqiyatli qo\'shildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinanceController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Saqlashda xatolik yuz berdi.');
        }
    }

    /**
     * Xarajatni yangilash.
     */
    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $finance = Finance::findOrFail($id);
            $finance->update($request->validated());

            DB::commit();

            return redirect()->back()->with('success', 'Xarajat muvaffaqiyatli yangilandi.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'O\'zgartirilayotgan ma\'lumot topilmadi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinanceController@update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    /**
     * Xarajatni o'chirish.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $finance = Finance::findOrFail($id);
            $finance->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Xarajat muvaffaqiyatli o\'chirildi.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'O\'chirilishi kerak bo\'lgan ma\'lumot topilmadi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FinanceController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirishda xatolik yuz berdi.');
        }
    }
}