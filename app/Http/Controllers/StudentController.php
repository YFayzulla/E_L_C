<?php

namespace App\Http\Controllers;

use App\Exports\StudentExport;
use App\Http\Requests\Student\StoreRequest;
use App\Http\Requests\Student\UpdateRequest;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /**
     * Barcha talabalar ro'yxati.
     */
    public function index()
    {
        try {
            $students = User::role('student')
                ->with('groups')
                ->orderBy("name", 'desc')
                ->get();

            return view('admin.student.index', compact('students'));
        } catch (\Exception $e) {
            Log::error('StudentController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talabalar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Yangi talaba qo'shish sahifasi.
     */
    public function create()
    {
        try {
            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")->get();
            return view('admin.student.create', compact('groups'));
        } catch (\Exception $e) {
            Log::error('StudentController@create error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Sahifani yuklashda xatolik.');
        }
    }

    /**
     * Yangi talabani saqlash.
     */
    public function store(StoreRequest $request)
    {
        $uploadedFilePath = null;

        if ($request->hasFile('photo')) {
            try {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $uploadedFilePath = $request->file('photo')->storeAs('Photo', $fileName, 'public');
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', 'Rasmni yuklashda xatolik: ' . $e->getMessage());
            }
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'password' => Hash::make($request->phone),
                'date_born' => $request->birth_date,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'parents_name' => $request->parents_name,
                'parents_tel' => $request->parents_tel,
                'location' => $request->location,
                'photo' => $uploadedFilePath,
                'should_pay' => (int)str_replace(' ', '', $request->should_pay),
                'description' => $request->description,
            ]);

            $user->assignRole('student');

            $groupIds = $request->group_id; // This is now an array
            $user->groups()->attach($groupIds);

            $groups = Group::whereIn('id', $groupIds)->get();
            foreach ($groups as $group) {
                StudentInformation::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'group' => $group->name,
                ]);
            }

            DeptStudent::create([
                'user_id' => $user->id,
                'payed' => 0,
                'dept' => (int)str_replace(' ', '', $request->should_pay),
                'status_month' => 0
            ]);

            DB::commit();

            return redirect()->route('student.index')->with('success', 'Talaba muvaffaqiyatli qo\'shildi.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($uploadedFilePath && Storage::disk('public')->exists($uploadedFilePath)) {
                Storage::disk('public')->delete($uploadedFilePath);
            }

            Log::error('StudentController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Saqlashda tizim xatoligi yuz berdi.');
        }
    }

    /**
     * Talaba ma'lumotlarini ko'rsatish.
     */
    public function show($id)
    {
        try {
            $student = User::with('groups')->findOrFail($id);
            $attendances = Attendance::where('user_id', $id)->latest()->paginate(10);
            $groupHistory = StudentInformation::where('user_id', $id)->orderBy('created_at', 'desc')->get();

            // Fetch test results (Assessments)
            // We need to join with LessonAndHistory to get the test name
            $testResults = Assessment::where('user_id', $id)
                ->join('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
                ->select('assessments.*', 'lesson_and_histories.name as test_name')
                ->latest('assessments.created_at')
                ->get();

            return view('admin.student.show', compact('student', 'attendances', 'groupHistory', 'testResults'));
        } catch (\Exception $e) {
            Log::error('StudentController@show error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talaba ma\'lumotlarini yuklashda xatolik.');
        }
    }

    /**
     * Tahrirlash sahifasi.
     */
    public function edit($id)
    {
        try {
            $student = User::with('groups')->findOrFail($id);
            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")->get();
            return view('admin.student.edit', compact('student', 'groups'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Talaba topilmadi.');
        }
    }

    /**
     * Talabani yangilash.
     */
    public function update(UpdateRequest $request, $id)
    {
        $newPhotoPath = null;
        $oldPhotoPath = null;

        DB::beginTransaction();

        try {
            $student = User::findOrFail($id);
            $oldPhotoPath = $student->photo;

            if ($request->hasFile('photo')) {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $newPhotoPath = $request->file('photo')->storeAs('Photo', $fileName, 'public');
            } else {
                $newPhotoPath = $oldPhotoPath;
            }

            $updateData = [
                'name' => $request->name,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'date_born' => $request->birth_date,
                'parents_name' => $request->parents_name,
                'parents_tel' => $request->parents_tel,
                'location' => $request->location,
                'should_pay' => (int)str_replace(' ', '', $request->should_pay),
                'photo' => $newPhotoPath,
                'description' => $request->description,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $student->update($updateData);

            $newGroupIds = $request->group_id; // Array of group IDs
            $currentGroupIds = $student->groups->pluck('id')->toArray();

            $student->groups()->sync($newGroupIds);

            $addedGroups = array_diff($newGroupIds, $currentGroupIds);
            $groups = Group::whereIn('id', $addedGroups)->get();
            foreach ($groups as $group) {
                StudentInformation::create([
                    'user_id' => $student->id,
                    'group_id' => $group->id,
                    'group' => $group->name
                ]);
            }

            $student->deptStudent()->updateOrCreate(
                ['user_id' => $student->id],
                ['dept' => (int)str_replace(' ', '', $request->should_pay)]
            );

            DB::commit();

            if ($request->hasFile('photo') && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            return redirect()->route('student.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->hasFile('photo') && $newPhotoPath && Storage::disk('public')->exists($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            Log::error('StudentController@update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    /**
     * Talabani o'chirish.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $student = User::findOrFail($id);
            $photoPath = $student->photo;

            $student->groups()->detach();
            StudentInformation::where('user_id', $student->id)->delete();
            DeptStudent::where('user_id', $student->id)->delete();
            Attendance::where('user_id', $student->id)->delete();

            $student->delete();

            DB::commit();

            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            return redirect()->back()->with('success', 'Talaba va unga tegishli barcha ma\'lumotlar o\'chirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StudentController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirish jarayonida xatolik yuz berdi.');
        }
    }

    /**
     * Export single student data to Excel with Comments & Description
     */
    public function exportStudent($id)
    {
        try {
            $student = User::role('student')->findOrFail($id);
            $fileName = 'Student_' . $student->name . '_' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(new StudentExport($id), $fileName);
        } catch (\Exception $e) {
            Log::error('StudentController@exportStudent error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talaba ma\'lumotlarini eksport qilishda xatolik.');
        }
    }

    /**
     * Export all students data to Excel with Comments & Description
     */
    public function exportAllStudents()
    {
        try {
            $fileName = 'All_Students_' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(new StudentExport(), $fileName);
        } catch (\Exception $e) {
            Log::error('StudentController@exportAllStudents error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talabalar ma\'lumotlarini eksport qilishda xatolik.');
        }
    }
}
