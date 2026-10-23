<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Group;
use App\Models\ReceptionStudent;
use App\Models\StaffNotification;
use App\Models\User;
use App\Services\StaffNotificationService;
use App\Services\StudentEnrollmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Reception daftaridagi yozuvni haqiqiy talabaga aylantirib, guruhga yozish.
 *
 * Ikki yo'l bor va ikkalasi ham shu yerda:
 *  - reception (yoki admin) yozuvni istalgan faol guruhga yozadi;
 *  - o'qituvchi o'z guruhiga tavsiya qilingan (yoki hali tavsiyasiz)
 *    yozuvni o'zining guruhiga "tortib oladi".
 *
 * Summani faqat admin yozadi. Qolganlarda guruhning standart narxi qo'yiladi
 * va adminga bildirishnoma boradi.
 */
class ReceptionEnrollmentController extends Controller
{
    use AuthorizesGroupAccess;

    public function __construct(
        private StudentEnrollmentService $enrollment,
        private StaffNotificationService $notifications
    ) {
    }

    /** O'qituvchi uchun: guruhiga olish mumkin bo'lgan reception yozuvlari. */
    public function teacherIndex()
    {
        $myGroups = $this->accessibleGroupIds()->map(fn ($id) => (int) $id)->all();

        $students = $this->claimableBy($myGroups)
            ->with('recommendedGroup:id,name')
            // O'z guruhiga tavsiya qilinganlar tepada.
            ->orderByRaw('CASE WHEN recommended_group_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.reception.index', compact('students'));
    }

    public function create(ReceptionStudent $student)
    {
        $this->authorizeRecord($student);

        if (! $student->canEnroll()) {
            return $this->alreadyHandled($student);
        }

        return view('reception.students.enroll', [
            'student' => $student,
            'groups'  => $this->groupOptions(),
            'isAdmin' => auth()->user()->hasRole('admin'),
        ]);
    }

    public function store(Request $request, ReceptionStudent $student)
    {
        $this->authorizeRecord($student);

        if (! $student->canEnroll()) {
            return $this->alreadyHandled($student);
        }

        $isAdmin = auth()->user()->hasRole('admin');
        $groupIds = $this->groupOptions()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $request->merge([
            'phone' => preg_replace('/[^0-9]/', '', (string) $request->input('phone')),
            'parent_phone' => ReceptionStudent::localPhone($request->input('parent_phone')) ?: null,
            // "500 000" — odam shunday yozadi; `numeric` dan oldin tozalanadi.
            'payment' => str_replace([' ', ',', "\u{00A0}"], '', (string) $request->input('payment')) ?: null,
        ]);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'digits:9'],
            'parent_name'  => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'digits:9'],
            'group_id'     => ['required', 'integer', Rule::in($groupIds)],
            'payment'      => [Rule::prohibitedIf(! $isAdmin), 'nullable', 'numeric', 'min:0'],
        ], [
            'payment.prohibited'  => 'Oylik to‘lovni faqat administrator belgilaydi.',
            'name.required'       => 'Ism familiyani kiriting.',
            'phone.required'      => 'Talabaning telefon raqamini kiriting — u shu raqam bilan tizimga kiradi.',
            'phone.digits'        => 'Telefon raqami 9 ta raqamdan iborat bo‘lishi kerak (901234567).',
            'parent_phone.digits' => 'Ota-ona telefoni 9 ta raqamdan iborat bo‘lishi kerak.',
            'group_id.required'   => 'Guruhni tanlang.',
            'group_id.in'         => 'Bu guruhga yozish mumkin emas.',
        ]);

        // users.phone 998 bilan saqlanadi, shuning uchun `unique` qoidasi emas.
        if (User::where('phone', '998' . $data['phone'])->exists()) {
            return redirect()->back()->withInput()
                ->withErrors(['phone' => 'Bu telefon raqami bilan hisob allaqachon mavjud.']);
        }

        $groupId = (int) $data['group_id'];

        try {
            $user = $this->enrollment->enroll(
                [
                    'name'         => $data['name'],
                    'phone'        => $data['phone'],
                    'parents_name' => $data['parent_name'] ?? null,
                    'parents_tel'  => $data['parent_phone'] ?? null,
                    'description'  => $student->notes,
                ],
                [$groupId],
                $isAdmin && filled($data['payment'] ?? null) ? [$groupId => $data['payment']] : [],
                [
                    'ota' => [
                        'name'  => $data['parent_name'] ?? null,
                        'phone' => $data['parent_phone'] ?? null,
                        'email' => null,
                    ],
                ]
            );

            $student->update([
                'student_id'           => $user->id,
                'enrolled_at'          => now(),
                'recommended_group_id' => $groupId,
                'status'               => ReceptionStudent::STATUS_ASSIGNED,
                'assigned_by'          => auth()->id(),
                'assigned_at'          => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('ReceptionEnrollmentController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Talabani guruhga yozishda xatolik. O‘zgarishlar saqlanmadi.');
        }

        $groupName = Group::whereKey($groupId)->value('name');

        if (! $isAdmin) {
            $this->notifications->notifyAdmins(
                StaffNotification::TYPE_STUDENT_ENROLLED,
                auth()->user()->name . ' reception’dan talaba yozdi: ' . $user->name,
                "Guruh: {$groupName}. Standart narx yozildi — oylik to‘lovni tekshiring.",
                route('student.edit', $user->id),
                ['student_id' => $user->id, 'reception_student_id' => $student->id]
            );
        }

        $message = "{$user->name} «{$groupName}» guruhiga yozildi. Login: +998{$data['phone']}, boshlang‘ich parol — shu raqam.";

        return redirect()->route($this->isTeacherOnly() ? 'teacher.reception.index' : 'reception.students.index')
            ->with('success', $message);
    }

    /* ------------------------------------------------------------------ */

    private function isTeacherOnly(): bool
    {
        $user = auth()->user();

        return ! $user->hasRole('admin') && ! $user->hasRole('reception');
    }

    /**
     * Reception va admin — har qanday yozuv. O'qituvchi — faqat o'z
     * guruhiga tavsiya qilingan yoki hali hech kimga tavsiya qilinmagan.
     */
    private function authorizeRecord(ReceptionStudent $student): void
    {
        if (! $this->isTeacherOnly()) {
            return;
        }

        $myGroups = $this->accessibleGroupIds()->map(fn ($id) => (int) $id)->all();

        abort_unless(
            $student->recommended_group_id === null || in_array((int) $student->recommended_group_id, $myGroups, true),
            403,
            'Bu talaba boshqa guruhga tavsiya qilingan.'
        );
    }

    /** @param  array<int, int>  $myGroups */
    private function claimableBy(array $myGroups): Builder
    {
        return ReceptionStudent::query()
            ->whereNull('student_id')
            ->where('status', '!=', ReceptionStudent::STATUS_ARCHIVED)
            ->where(fn (Builder $q) => $q
                ->whereNull('recommended_group_id')
                ->orWhereIn('recommended_group_id', $myGroups ?: [0]));
    }

    private function groupOptions()
    {
        $query = Group::query()->teaching()->active()->orderBy('name');

        if ($this->isTeacherOnly()) {
            $query->whereIn('id', $this->accessibleGroupIds());
        }

        return $query->get(['id', 'name', 'monthly_payment']);
    }

    private function alreadyHandled(ReceptionStudent $student)
    {
        $message = $student->isEnrolled()
            ? 'Bu yozuv allaqachon talaba sifatida guruhga yozilgan.'
            : 'Arxivdagi yozuvni guruhga yozib bo‘lmaydi.';

        return redirect()->route($this->isTeacherOnly() ? 'teacher.reception.index' : 'reception.students.index')
            ->with('error', $message);
    }
}
