<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreRequest;
use App\Http\Requests\Teacher\UpdateRequest;
use App\Models\Group;
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
            $teachers = User::role('user')
                ->with('teacherGroups')
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
            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")->get();
            return view('admin.teacher.create', compact('groups'));
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
            $teacher = User::create([
                'name' => $request->name,
                'password' => Hash::make($request->phone),
                'passport' => $request->passport,
                'date_born' => $request->date_born,
                'location' => $request->location,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'photo' => $uploadedFilePath,
                'percent' => $request->percent,
            ]);

            $teacher->assignRole('user');

            if ($request->has('group_id')) {
                $teacher->teacherGroups()->attach($request->group_id);
            }

            DB::commit();

            return redirect()->route('teacher.index')->with('success', 'O\'qituvchi muvaffaqiyatli qo\'shildi.');

        } catch (\Exception $e) {
            DB::rollBack();

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
            $teacher = User::with('teacherGroups')->findOrFail($id);
            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")->get();
            return view('admin.teacher.edit', compact('teacher', 'groups'));
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

            if ($request->hasFile('photo')) {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $newPhotoPath = $request->file('photo')->storeAs('Photo', $fileName, 'public');
            } else {
                $newPhotoPath = $oldPhotoPath;
            }

            $updateData = [
                'name' => $request->name,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'date_born' => $request->date_born,
                'location' => $request->location,
                'passport' => $request->passport,
                'percent' => $request->percent,
                'photo' => $newPhotoPath,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $teacher->update($updateData);

            $teacher->teacherGroups()->sync($request->group_id);

            DB::commit();

            if ($request->hasFile('photo') && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return redirect()->route('teacher.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            DB::rollBack();

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

            $teacher->teacherGroups()->detach();
            
            $teacher->delete();

            DB::commit();

            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            return redirect()->back()->with('success', 'O\'qituvchi va unga tegishli barcha ma\'lumotlar o\'chirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirish jarayonida xatolik yuz berdi.');
        }
    }
}
