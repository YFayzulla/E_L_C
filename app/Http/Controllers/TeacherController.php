<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreRequest;
use App\Http\Requests\Teacher\UpdateRequest;
use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Tenancy\TenantStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * Bu ekran boshqaradigan rollar.
     *
     * `support` — vazifasi tor xodim: faqat oylik testni va dars
     * jarayonidagi ko'nikmalarni baholaydi. Guruhga oddiy o'qituvchi kabi
     * biriktiriladi, lekin oyligi guruh to'lovlaridan hisoblanmaydi.
     */
    public const STAFF_ROLES = ['user', 'support'];

    /**
     * O'qituvchilar ro'yxati.
     *
     * DIQQAT: bazaviy Controller index() ni parametrsiz e'lon qilgan,
     * shuning uchun bu yerda ham parametr bo'lishi mumkin emas.
     */
    public function index()
    {
        try {
            $teachers = User::role(self::STAFF_ROLES)
                ->with('teacherGroups:id,name')
                ->withCount(['teacherGroups as groups_count'])
                ->orderBy('name')
                ->get();

            // Bitta guruhlangan so'rov - qator boshiga teacherHasStudents() chaqirmaymiz.
            $studentCounts = $this->studentCountsByTeacher($teachers->pluck('id')->all());

            return view('admin.teacher.index', compact('teachers', 'studentCounts'));
        } catch (\Exception $e) {
            Log::error('TeacherController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'qituvchilar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * O'qituvchi kartochkasi: guruhlar, statistika, so'nggi darslar.
     */
    public function show($id)
    {
        try {
            $teacher = User::role(self::STAFF_ROLES)->findOrFail($id);

            // group_teachers QATOR id kerak - biriktirishni uzish shu id bo'yicha ketadi.
            $groupLinks = GroupTeacher::with([
                'group' => fn($q) => $q->withCount(['students as members_count']),
            ])
                ->where('teacher_id', $teacher->id)
                ->get()
                ->filter(fn($link) => $link->group !== null)
                ->sortBy(fn($link) => $link->group->name)
                ->values();

            $groupIds = $groupLinks->pluck('group_id')->map(fn($v) => (int) $v)->all();

            $availableGroups = Group::whereNotIn('id', $groupIds ?: [0])
                ->where('name', '!=', 'Waiting Room')
                ->orderBy('name')
                ->get(['id', 'name', 'start_time', 'finish_time']);

            $recentLessons = collect();
            if (! empty($groupIds)) {
                $recentLessons = LessonAndHistory::whereIn('group', $groupIds)
                    ->where('data', 1)
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            }

            // Dars qatorlarida guruh nomini ko'rsatish uchun xarita.
            $groupNames = $groupLinks->mapWithKeys(
                fn($link) => [(int) $link->group_id => $link->group->name]
            );

            return view('admin.teacher.show', [
                'teacher' => $teacher,
                'groupLinks' => $groupLinks,
                'availableGroups' => $availableGroups,
                'recentLessons' => $recentLessons,
                'groupNames' => $groupNames,
                'studentCount' => $teacher->teacherHasStudents(),
                'salary' => $teacher->teacherPayment(),
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');
        } catch (\Exception $e) {
            Log::error('TeacherController@show error: ' . $e->getMessage());
            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi ma\'lumotlarini yuklashda xatolik.');
        }
    }

    /**
     * Yangi o'qituvchi qo'shish sahifasi.
     */
    public function create()
    {
        try {
            $groups = Group::where('name', '!=', 'Waiting Room')
                ->orderBy('name')
                ->get(['id', 'name']);

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
                $uploadedFilePath = $request->file('photo')->storeAs(TenantStorage::path('Photo'), $fileName, 'public');
            } catch (\Exception $e) {
                Log::error('TeacherController@store photo error: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Rasmni yuklashda xatolik.');
            }
        }

        DB::beginTransaction();

        try {
            $payload = $this->commonPayload($request);
            $payload['photo'] = $uploadedFilePath;
            // Parol berilmasa - eski xulq: telefon raqami parol bo'ladi.
            $payload['password'] = Hash::make(
                $request->filled('password') ? $request->input('password') : $request->input('phone')
            );

            $teacher = User::create($payload);

            // Membership and role go through the one writer that knows about
            // Spatie teams; a bare assignRole() here would stamp whatever
            // centre happened to be current, or none at all.
            $this->attachToCurrentCentre(
                $teacher,
                $request->input('percent'),
                $this->staffRole($request)
            );

            if ($this->groupsWereSubmitted($request)) {
                $teacher->teacherGroups()->sync($this->cleanGroupIds($request));
            }

            DB::commit();

            // Strictly after the commit: the mailer is synchronous
            // (QUEUE_CONNECTION=sync) and an unreachable SMTP host would
            // otherwise roll the whole teacher back.
            $this->sendVerificationMail($teacher, 'store');

            return redirect()->route('teacher.index')->with('success', 'O\'qituvchi muvaffaqiyatli qo\'shildi.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($uploadedFilePath && Storage::disk('public')->exists($uploadedFilePath)) {
                Storage::disk('public')->delete($uploadedFilePath);
            }

            Log::error('TeacherController@store error: ' . $e->getMessage());

            // withInput() parametrsiz chaqirilsa OCHIQ parolni sessiyaga yozadi.
            $old = $request->except(['password', 'photo', '_token']);

            if ($e->getCode() == 23000) {
                return redirect()->back()->withInput($old)
                    ->with('error', 'Bu telefon raqami yoki passport allaqachon ro\'yxatdan o\'tgan.');
            }

            return redirect()->back()->withInput($old)->with('error', 'Saqlashda tizim xatoligi yuz berdi.');
        }
    }

    /**
     * Tahrirlash sahifasi.
     */
    public function edit($id)
    {
        try {
            // role('user') SHART: usiz /teacher/{talaba_id}/edit talaba ustida
            // o'qituvchi formasini ochib yuboradi (show() allaqachon shu filtrni qo'llaydi).
            $teacher = User::role(self::STAFF_ROLES)->with('teacherGroups:id,name')->findOrFail($id);

            $groups = Group::orderByRaw("CASE WHEN name = 'Waiting Room' THEN 1 ELSE 0 END, name")
                ->get(['id', 'name']);

            return view('admin.teacher.edit', compact('teacher', 'groups'));
        } catch (\Exception $e) {
            Log::error('TeacherController@edit error: ' . $e->getMessage());
            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');
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
            // role('user') SHART: usiz PUT /teacher/{talaba_id} talabaning
            // telefon/parol/foizini o'qituvchi formasi bilan qayta yozadi.
            $teacher = User::role(self::STAFF_ROLES)->findOrFail($id);
            $oldPhotoPath = $teacher->photo;

            if ($request->hasFile('photo')) {
                $fileName = time() . '.' . $request->file('photo')->getClientOriginalExtension();
                $newPhotoPath = $request->file('photo')->storeAs(TenantStorage::path('Photo'), $fileName, 'public');
            } else {
                $newPhotoPath = $oldPhotoPath;
            }

            $updateData = $this->commonPayload($request);
            $updateData['photo'] = $newPhotoPath;

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->input('password'));
            }

            // E-pochta o'zgarsa - tasdiqni bekor qilamiz (email_verified_at fillable emas).
            $emailChanged = array_key_exists('email', $updateData) && $updateData['email'] !== $teacher->email;

            if ($emailChanged) {
                $teacher->email_verified_at = null;
            }

            $teacher->update($updateData);

            // Xodim turi o'zgargan bo'lsa rolni almashtiramiz. setRole()
            // eskisini olib tashlab yangisini beradi, ya'ni odam bir vaqtda
            // ham o'qituvchi, ham support bo'lib qolmaydi.
            $role = $this->staffRole($request);

            if ($request->has('role') && ! $teacher->hasRole($role)) {
                app(CentreMembershipService::class)->setRole(Centre::current(), $teacher, $role);
                $teacher->unsetRelation('roles');
            }

            // Support uchun foiz saqlanmaydi — oyligi guruhga bog'liq emas.
            $this->syncPercent($teacher, $role === 'support' ? null : $request->input('percent'));

            // Multi-select hech narsa yubormasa, sync([]) BARCHA guruhni uzib yuboradi.
            if ($this->groupsWereSubmitted($request)) {
                $teacher->teacherGroups()->sync($this->cleanGroupIds($request));
            }

            DB::commit();

            if ($request->hasFile('photo') && $oldPhotoPath && Storage::disk('public')->exists($oldPhotoPath)) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            // Only on a real change, so saving an unrelated field does not
            // re-mail the teacher every time.
            if ($emailChanged) {
                $this->sendVerificationMail($teacher, 'update');
            }

            return redirect()->route('teacher.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->hasFile('photo') && $newPhotoPath && Storage::disk('public')->exists($newPhotoPath)) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            Log::error('TeacherController@update error: ' . $e->getMessage());

            // withInput() parametrsiz chaqirilsa OCHIQ parolni sessiyaga yozadi.
            $old = $request->except(['password', 'photo', '_token', '_method']);

            if ($e->getCode() == 23000) {
                return redirect()->back()->withInput($old)
                    ->with('error', 'Bu telefon raqami yoki passport allaqachon ro\'yxatdan o\'tgan.');
            }

            return redirect()->back()->withInput($old)->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    /**
     * O'qituvchini o'chirish.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // role('user') SHART: usiz DELETE /teacher/{talaba_id} talabani
            // "o'qituvchi o'chirildi" degan yashil flash bilan o'chirib yuboradi.
            $teacher = User::role(self::STAFF_ROLES)->findOrFail($id);
            $photoPath = $teacher->photo;

            $teacher->teacherGroups()->detach();
            $teacher->delete();

            DB::commit();

            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            return redirect()->route('teacher.index')
                ->with('success', 'O\'qituvchi va unga tegishli barcha ma\'lumotlar o\'chirildi.');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirish jarayonida xatolik yuz berdi.');
        }
    }

    /**
     * Store va update uchun umumiy ustunlar.
     *
     * `birth_date` ATAYIN yo'q: bunday ustun ham, fillable ham mavjud emas edi,
     * shuning uchun tug'ilgan sana hech qachon saqlanmasdi. Endi bitta maydon - `date_born`.
     *
     * @return array<string, mixed>
     */
    /**
     * Attach a freshly created teacher to the centre they were created in.
     *
     * Falls back to a bare assignRole() while no centre is resolved, which is
     * the case until the tenant middleware is wired up.
     */
    /**
     * Formadan kelgan xodim turi. Noma'lum qiymat oddiy o'qituvchiga
     * tushadi — validatsiya uni allaqachon cheklab qo'ygan.
     */
    private function staffRole(Request $request): string
    {
        $role = (string) $request->input('role', 'user');

        return in_array($role, self::STAFF_ROLES, true) ? $role : 'user';
    }

    private function attachToCurrentCentre(User $teacher, $percent, string $role = 'user'): void
    {
        // Support teacher oyligi guruh to'lovlariga bog'liq emas, ya'ni
        // foiz ham saqlanmaydi — u ko'rsatilmaydigan bo'lsa, yozib
        // qo'yish keyinchalik noto'g'ri hisob-kitobga olib kelardi.
        app(CentreMembershipService::class)->attachToCurrent($teacher, $role, [
            'percent' => $role === 'support' || $percent === null || $percent === ''
                ? null
                : (int) $percent,
        ]);
    }

    /**
     * The payout share belongs to the membership, not the person: the same
     * teacher may work at another centre on different terms.
     */
    private function syncPercent(User $teacher, $percent): void
    {
        $centre = Centre::current();

        if ($centre === null) {
            return;   // users.percent already updated by the payload
        }

        app(CentreMembershipService::class)->setPercent(
            $centre,
            $teacher,
            $percent === null || $percent === '' ? null : (int) $percent
        );
    }

    private function commonPayload(Request $request): array
    {
        $payload = [
            'name' => $request->input('name'),
            'phone' => '998' . preg_replace('/[^0-9]/', '', (string) $request->input('phone')),
            'date_born' => $request->input('date_born') ?: null,
            'passport' => $request->filled('passport') ? trim((string) $request->input('passport')) : null,
            'location' => $request->input('location') ?: null,
            'description' => $request->input('description') ?: null,
            'percent' => $request->input('percent'),
        ];

        // E-pochta maydoni (partials.email-field) mavjud bo'lsagina tegamiz.
        if ($request->has('email')) {
            $payload['email'] = $request->input('email') ?: null;
        }

        return $payload;
    }

    /**
     * Guruhlar ro'yxatini sinxronlash kerakmi?
     *
     * Forma har doim `groups_submitted` bayrog'ini yuboradi - shu sababli
     * tanlovni butunlay bo'shatish ham ishlaydi. Bayroq ham, `group_id` ham
     * bo'lmasa (masalan boshqa manbadan kelgan so'rov), guruhlarga tegilmaydi:
     * aks holda null -> [] ga aylanib, sync() barcha biriktirishni o'chirib
     * yuborar va flash "muvaffaqiyatli" deb turar edi.
     */
    private function groupsWereSubmitted(Request $request): bool
    {
        return $request->boolean('groups_submitted') || $request->has('group_id');
    }

    /**
     * Send the account-confirmation e-mail, if there is an address to send to.
     *
     * MUST be called after DB::commit(). The mailer is synchronous
     * (QUEUE_CONNECTION=sync), so an unreachable SMTP host throws inside the
     * request; inside a transaction that would silently roll the teacher back.
     * A failed send is logged and never surfaces to the admin — the account is
     * saved either way, and the user can resend from the layout banner.
     */
    private function sendVerificationMail(User $teacher, string $context): void
    {
        if (blank($teacher->email)) {
            return;
        }

        try {
            $teacher->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error("TeacherController@{$context} mail error: " . $e->getMessage());
        }
    }

    /**
     * Multi-select'dan kelgan guruh id larini tozalash (bo'sh qiymatlarsiz, takrorsiz).
     *
     * @return array<int, int>
     */
    private function cleanGroupIds(Request $request): array
    {
        $ids = (array) $request->input('group_id', []);

        return array_values(array_unique(array_map(
            'intval',
            array_filter($ids, fn($id) => $id !== null && $id !== '')
        )));
    }

    /**
     * Har bir o'qituvchi uchun takrorlanmas talabalar soni - BITTA so'rov.
     *
     * @param  array<int, int>  $teacherIds
     * @return array<int, int>
     */
    private function studentCountsByTeacher(array $teacherIds): array
    {
        if (empty($teacherIds)) {
            return [];
        }

        return DB::table('group_teachers')
            ->join('group_user', 'group_user.group_id', '=', 'group_teachers.group_id')
            ->whereIn('group_teachers.teacher_id', $teacherIds)
            ->whereIn('group_user.user_id', User::role('student')->select('users.id'))
            ->groupBy('group_teachers.teacher_id')
            ->selectRaw('group_teachers.teacher_id as teacher_id, COUNT(DISTINCT group_user.user_id) as students_total')
            ->pluck('students_total', 'teacher_id')
            ->all();
    }
}
