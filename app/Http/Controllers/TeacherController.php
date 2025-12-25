<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreRequest;
use App\Http\Requests\Teacher\UpdateRequest;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * O'qituvchilar ro'yxati.
     */
    public function index()
    {
        try {
            // OPTIMIZATSIYA:
            // 1. with('room') - N+1 muammosini oldini olish (View da xona nomi kerak bo'lsa).
            // 2. paginate(20) - Ro'yxat uzun bo'lsa sahifalash.
            $teachers = User::role('user') // Changed from 'user' to 'teacher'
            ->with('room:id,room') // Faqat kerakli ustunlar
            ->orderBy('name')
                ->paginate(20);

            return view('admin.teacher.index', compact('teachers'));
        } catch (\Exception $e) {
            Log::error('TeacherController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'qituvchilar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Yangi o'qituvchi qo'shish sahifasi.
     */
    public function create()
    {
        try {
            // Xonalarni oddiy ro'yxat sifatida olish
            $rooms = Room::orderBy('room')->get();
            return view('admin.teacher.create', compact('rooms'));
        } catch (\Exception $e) {
            Log::error('TeacherController@create error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Sahifani yuklashda xatolik.');
        }
    }

    /**
     * Yangi o'qituvchini saqlash.
     */
    public function store(StoreRequest $request)
    {
        $uploadedFilePath = null;

        // 1. Faylni yuklash (Tranzaksiyadan tashqarida)
        if ($request->hasFile('photo')) {
            try {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $uploadedFilePath = $request->file('photo')->storeAs('Photo', $fileName, 'public');
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', 'Rasmni yuklashda xatolik.');
            }
        }

        DB::beginTransaction();

        try {
            // 2. User yaratish
            $teacher = User::create([
                'name' => $request->name,
                'password' => Hash::make($request->phone), // Telefon raqam parol sifatida
                'passport' => $request->passport,
                'date_born' => $request->date_born,
                'location' => $request->location,
                // Telefon formatlash: Faqat raqamlarni qoldirib, oldiga 998 qo'shish (logikangiz bo'yicha)
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'photo' => $uploadedFilePath,
                'percent' => $request->percent,
                'room_id' => $request->room_id
            ]);

            $teacher->assignRole('user'); // Changed from 'user' to 'teacher'

            // 3. Xonaga tegishli guruhlarga biriktirish
            // Agar o'qituvchi biror xonaga biriktirilsa, shu xonadagi mavjud guruhlarga avtomatik qo'shiladi.
            $groups = Group::where('room_id', $request->room_id)->get();

            if ($groups->isNotEmpty()) {
                $groupTeachers = $groups->map(fn($group) => [
                    'group_id' => $group->id,
                    'teacher_id' => $teacher->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                GroupTeacher::insert($groupTeachers);
            }

            DB::commit();

            return redirect()->route('teacher.index')->with('success', 'O\'qituvchi muvaffaqiyatli qo\'shildi.');

        } catch (\Exception $e) {
            DB::rollBack();

            // XATO BO'LSA: Yuklangan rasmni o'chirib tashlash
            if ($uploadedFilePath && Storage::disk('public')->exists($uploadedFilePath)) {
                Storage::disk('public')->delete($uploadedFilePath);
            }

            Log::error('TeacherController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Saqlashda tizim xatoligi yuz berdi.');
        }
    }

    /**
     * Tahrirlash sahifasi.
     */
    public function edit($id)
    {
        try {
            $teacher = User::findOrFail($id);
            $rooms = Room::orderBy('room')->get();
            return view('admin.teacher.edit', compact('teacher', 'rooms'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'O\'qituvchi topilmadi.');
        }
    }

    /**
     * O'qituvchini yangilash.
     */
    public function update(UpdateRequest $request, $id)
    {
        $newPhotoPath = null;
        $oldPhotoPath = null;

        DB::beginTransaction();

        try {
            $teacher = User::findOrFail($id);
            $oldPhotoPath = $teacher->photo;

            // 1. Rasm yuklash
            if ($request->hasFile('photo')) {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $newPhotoPath = $request->file('photo')->storeAs('Photo', $fileName, 'public');
            } else {
                $newPhotoPath = $oldPhotoPath;
            }

            // 2. Ma'lumotlarni tayyorlash
            $updateData = [
                'name' => $request->name,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'date_born' => $request->date_born,
                'location' => $request->location,
                'passport' => $request->passport,
                'percent' => $request->percent,
                'photo' => $newPhotoPath,
                'room_id' => $request->room_id,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $teacher->update($updateData);

            // 3. Guruh biriktirmalarini yangilash (Sync Logic)
            // O'qituvchi xonasi o'zgarganda yoki shunchaki update bo'lganda,
            // biz eski biriktirmalarni o'chirib, joriy xonadagi guruhlarga qayta biriktiramiz.

            // Diqqat: Bu logika agar o'qituvchi qo'lda maxsus guruhlarga qo'shilgan bo'lsa, ularni o'chirib yuboradi.
            // Lekin sizning kodingizda shunday yozilgan edi, men buni saqlab qoldim
            GroupTeacher::where('teacher_id', $teacher->id)->delete();

            $groups = Group::where('room_id', $request->room_id)->get();

            if ($groups->isNotEmpty()) {
                $groupTeachers = $groups->map(fn($group) => [
                    'group_id' => $group->id,
                    'teacher_id' => $teacher->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                GroupTeacher::insert($groupTeachers);
            }

            DB::commit();

            // MUVAFFAQIYATLI: Eski rasmni o'chirish (agar yangisi yuklangan bo'lsa)
            if ($request->hasFile('photo') && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return redirect()->route('teacher.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            DB::rollBack();

            // XATOLIK: Yangi yuklangan rasmni o'chirish
            if ($request->hasFile('photo') && $newPhotoPath && Storage::disk('public')->exists($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            Log::error('TeacherController@update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    /**
     * O'qituvchini o'chirish.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $teacher = User::findOrFail($id);
            $photoPath = $teacher->photo;

            // 1. Bog'liq ma'lumotlarni o'chirish
            GroupTeacher::where('teacher_id', $teacher->id)->delete();

            // Agar o'qituvchiga bog'liq boshqa jadvallar bo'lsa (masalan, dars jadvallari, davomatlar),
            // ularni ham shu yerda ko'rib chiqish kerak (Set Null yoki Delete).

            // 2. Userni o'chirish
            $teacher->delete();

            DB::commit();

            // 3. Rasmni o'chirish (Tranzaksiya tugagandan keyin)
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            return redirect()->back()->with('success', 'O\'qituvchi muvaffaqiyatli o\'chirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirishda xatolik yuz berdi. O\'qituvchiga bog\'liq ma\'lumotlar mavjud bo\'lishi mumkin.');
        }
    }
}
