<?php

namespace App\Http\Controllers;

use App\Exports\StudentExport;
use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Http\Requests\Student\StoreRequest;
use App\Http\Requests\Student\UpdateRequest;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use App\Services\ParentAccountService;
use App\Services\ProgressService;
use App\Tenancy\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use AuthorizesGroupAccess;

    public function __construct(private ParentAccountService $parents)
    {
    }

    /**
     * Barcha talabalar ro'yxati.
     */
    public function index()
    {
        try {
            $students = User::role('student')
                ->with('groups')
                ->orderBy("name")
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
                $uploadedFilePath = $request->file('photo')->storeAs(TenantStorage::path('Photo'), $fileName, 'public');
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
                // Blank must be stored as NULL — `users.email` is uniquely indexed.
                'email' => $request->email ?: null,
                'parents_name' => $request->parents_name,
                'parents_tel' => $request->parents_tel,
                'location' => $request->location,
                'photo' => $uploadedFilePath,
                'description' => $request->description,
            ]);

            $user->assignRole('student');

            $groupIds = $request->group_id; // This is now an array

            // Build attach array with per-group payments (pivot `payment`).
            $submittedPayments = $request->input('group_payment', []);
            $groups = Group::whereIn('id', $groupIds)->get();
            $attachData = [];
            $sumPayments = 0;
            foreach ($groups as $group) {
                $raw = $submittedPayments[$group->id] ?? $group->monthly_payment;
                $paymentValue = (int)str_replace([' ', ','], '', $raw);
                $attachData[$group->id] = ['payment' => $paymentValue];
                $sumPayments += $paymentValue;

                StudentInformation::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'group' => $group->name,
                    'actor_id' => auth()->id(),
                ]);
            }

            $user->groups()->attach($attachData);

            // Set user's should_pay to the sum of group payments (role preserved but no UI input).
            $user->should_pay = $sumPayments;
            $user->save();

            DeptStudent::create([
                'user_id' => $user->id,
                'payed' => 0,
                'dept' => $sumPayments,
                'status_month' => 0
            ]);

            // Guardian details create (or reuse) a parent account so the family
            // can follow this student from the ota-ona portal.
            // Ota / ona (va ixtiyoriy vasiy) hisoblari bir yo'la yaratiladi va
            // parent_student.relation orqali bog'lanadi. Bu ham
            // users.parents_name / parents_tel ustunlarini birlamchi vasiy
            // bo'yicha yangilab qo'yadi.
            $this->parents->syncGuardians($user, $this->guardianInput($request));

            DB::commit();

            // Mail is deliberately sent AFTER the commit: QUEUE_CONNECTION=sync and an
            // unreachable MAIL_HOST would otherwise throw inside the transaction and roll
            // the whole student back. A failed e-mail must never lose the record.
            $this->sendVerificationMail($user);

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
     * Guardian blocks off the student form, keyed by relation.
     *
     * Falls back to the legacy single parents_name/parents_tel pair so an older
     * form (or an integration) that still posts those two fields keeps working.
     *
     * @return array<string, array{name: ?string, phone: ?string, email: ?string}>
     */
    private function guardianInput(Request $request): array
    {
        $out = [];

        foreach (ParentAccountService::RELATIONS as $relation) {
            $block = $request->input("guardians.{$relation}", []);

            $out[$relation] = [
                'name'  => $block['name'] ?? null,
                'phone' => $block['phone'] ?? null,
                'email' => $block['email'] ?? null,
            ];
        }

        if (blank($out['ota']['phone']) && filled($request->input('parents_tel'))) {
            $out['ota'] = [
                'name'  => $request->input('parents_name'),
                'phone' => $request->input('parents_tel'),
                'email' => null,
            ];
        }

        return $out;
    }

    /**
     * Talaba ma'lumotlarini ko'rsatish.
     */
    public function show($id)
    {
        // Outside the try: this route is role:admin|user, so without a
        // relationship check any teacher could read any student's full record
        // (phone, passport, address, guardian number) by editing the URL.
        // abort() throws, and the catch below would swallow it into a redirect.
        $this->assertTeachesStudent((int) $id);

        try {
            $student = User::inCurrentCentre()->with('groups')->findOrFail($id);
            $attendances = Attendance::where('user_id', $id)->latest()->paginate(10);
            // `actor` oldindan yuklanadi: aks holda tarixdagi har bir qator
            // uchun bittadan qo'shimcha so'rov ketardi.
            $groupHistory = StudentInformation::where('user_id', $id)
                ->with('actor:id,name,photo')
                ->orderBy('created_at', 'desc')
                ->get();

            // Fetch test results (Assessments)
            // We need to join with LessonAndHistory to get the test name
            $testResults = Assessment::where('user_id', $id)
                ->join('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
                ->select('assessments.*', 'lesson_and_histories.name as test_name')
                ->latest('assessments.created_at')
                ->get();

            $progress = app(ProgressService::class)->forStudent((int) $id);

            return view('admin.student.show', compact(
                'student', 'attendances', 'groupHistory', 'testResults', 'progress'
            ));
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
            $student = User::inCurrentCentre()->with('groups')->findOrFail($id);
            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")->get();
            $guardians = $this->parents->guardiansOf($student);

            return view('admin.student.edit', compact('student', 'groups', 'guardians'));
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
        $student = null;
        $emailChanged = false;

        DB::beginTransaction();

        try {
            $student = User::inCurrentCentre()->findOrFail($id);
            $oldPhotoPath = $student->photo;
            $originalEmail = $student->email;

            if ($request->hasFile('photo')) {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $newPhotoPath = $request->file('photo')->storeAs(TenantStorage::path('Photo'), $fileName, 'public');
            } else {
                $newPhotoPath = $oldPhotoPath;
            }

            // Blank must be stored as NULL — `users.email` is uniquely indexed.
            $newEmail = $request->email ?: null;
            $emailChanged = $newEmail !== $originalEmail;

            $updateData = [
                'name' => $request->name,
                'phone' => '998' . preg_replace('/[^0-9]/', '', $request->phone),
                'date_born' => $request->birth_date,
                'email' => $newEmail,
                'parents_name' => $request->parents_name,
                'parents_tel' => $request->parents_tel,
                'location' => $request->location,
                'photo' => $newPhotoPath,
                'description' => $request->description,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // A new address is unconfirmed by definition. `email_verified_at` is
            // intentionally NOT fillable, so it is assigned directly; the update()
            // below persists it together with the rest of the dirty attributes.
            if ($emailChanged) {
                $student->email_verified_at = null;
            }

            $student->update($updateData);

            $newGroupIds = $request->group_id; // Array of group IDs
            $currentGroupIds = $student->groups->pluck('id')->toArray();

            // Prepare sync data including pivot payments
            $submittedPayments = $request->input('group_payment', []);
            $groupsForSync = Group::whereIn('id', $newGroupIds)->get();
            $syncData = [];
            $sumPayments = 0;
            foreach ($groupsForSync as $group) {
                $raw = $submittedPayments[$group->id] ?? $group->monthly_payment;
                $paymentValue = (int)str_replace([' ', ','], '', $raw);
                $syncData[$group->id] = ['payment' => $paymentValue];
                $sumPayments += $paymentValue;
            }


            $student->groups()->sync($syncData);

            $addedGroups = array_diff($newGroupIds, $currentGroupIds);
            $groups = Group::whereIn('id', $addedGroups)->get();
            foreach ($groups as $group) {
                StudentInformation::create([
                    'user_id' => $student->id,
                    'group_id' => $group->id,
                    'group' => $group->name,
                    'actor_id' => auth()->id(),
                ]);
            }

            // Update user's should_pay to the sum of group payments
            $student->update([
                'should_pay' => $sumPayments,
            ]);

            // Update or create DeptStudent record with new dept sum
            $student->deptStudent()->updateOrCreate(
                ['user_id' => $student->id],
                ['dept' => $sumPayments]
            );

            // Keep the guardian link in step with the phone number on the form:
            // drop links that no longer match, then (re)create the current one.
            // syncGuardians unlinks anyone dropped from the form itself, so the
            // old single-number prune is not needed here.
            $this->parents->syncGuardians($student, $this->guardianInput($request));

            DB::commit();

            if ($request->hasFile('photo') && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            // Only bother the student when the address actually moved, and only
            // once the row is safely committed (see the note in store()).
            if ($emailChanged) {
                $this->sendVerificationMail($student);
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
            $student = User::inCurrentCentre()->findOrFail($id);
            $photoPath = $student->photo;

            $student->groups()->detach();
            $student->guardians()->detach();
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
     * Fire the confirmation e-mail without ever letting the mailer break the save.
     *
     * MUST be called outside a transaction: the mailer runs synchronously
     * (QUEUE_CONNECTION=sync) and the SMTP host is not always reachable.
     * A blank address is a no-op inside the model.
     */
    private function sendVerificationMail(?User $user): void
    {
        if (! $user || blank($user->email)) {
            return;
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('StudentController@sendVerificationMail error: ' . $e->getMessage());
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
