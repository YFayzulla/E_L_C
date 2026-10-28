<?php

namespace App\Services;

use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Yangi talaba hisobini ochib, guruh(lar)ga yozadigan yagona joy.
 *
 * Admin formasi, reception'dan guruhga o'tkazish va o'qituvchining "talaba
 * qo'shish" sahifasi — uchalasi ham shu yerdan o'tadi. Talaba yaratish
 * faqat `users` qatori emas: markaz a'zoligi va rol, guruh pivotidagi oylik
 * summa, `should_pay`, `dept_students` va guruh tarixi bir tranzaksiyada
 * yozilishi kerak, aks holda talaba kira olmaydi yoki to'lovlar ekranida
 * noto'g'ri ko'rinadi.
 */
class StudentEnrollmentService
{
    public function __construct(
        private CentreMembershipService $membership,
        private ParentAccountService $parents
    ) {
    }

    /**
     * @param  array{name: string, phone: string, email?: ?string, date_born?: ?string, parents_name?: ?string,
     *               parents_tel?: ?string, location?: ?string, photo?: ?string, description?: ?string}  $attributes
     *               `phone` — 9 xonali mahalliy raqam (998 siz).
     * @param  array<int, int|string>              $groupIds
     * @param  array<int|string, int|string|null>  $payments  guruh id => oylik summa; bo'sh bo'lsa guruh narxi
     * @param  array<string, array{name: ?string, phone: ?string, email: ?string}>  $guardians
     *
     * @throws \Throwable
     */
    public function enroll(
        array $attributes,
        array $groupIds,
        array $payments = [],
        array $guardians = [],
        ?int $actorId = null
    ): User {
        $actorId ??= auth()->id();
        $digits = preg_replace('/[^0-9]/', '', (string) $attributes['phone']);

        return DB::transaction(function () use ($attributes, $digits, $groupIds, $payments, $guardians, $actorId) {
            $user = User::create([
                'name'         => $attributes['name'],
                // Boshlang'ich parol — telefon raqami, admin formasidagidek.
                'password'     => Hash::make($digits),
                'date_born'    => $attributes['date_born'] ?? null,
                'phone'        => '998' . $digits,
                // Blank must be stored as NULL — `users.email` is uniquely indexed.
                'email'        => ($attributes['email'] ?? null) ?: null,
                'parents_name' => $attributes['parents_name'] ?? null,
                'parents_tel'  => $attributes['parents_tel'] ?? null,
                'location'     => $attributes['location'] ?? null,
                'photo'        => $attributes['photo'] ?? null,
                'description'  => $attributes['description'] ?? null,
            ]);

            // attachToCurrent, assignRole emas: yalang'och assignRole rol
            // beradi, lekin centre_user qatorini yozmaydi — talaba
            // yaratiladi, ro'yxatda ko'rinadi, lekin KIRA OLMAYDI.
            $this->membership->attachToCurrent($user, 'student');

            $groups = Group::whereIn('id', $groupIds)->get();
            $attachData = [];
            $sum = 0;

            foreach ($groups as $group) {
                $raw = $payments[$group->id] ?? null;
                $raw = filled($raw) ? $raw : $group->monthly_payment;
                $value = (int) str_replace([' ', ','], '', (string) $raw);

                $attachData[$group->id] = ['payment' => $value];
                $sum += $value;

                StudentInformation::create([
                    'user_id'  => $user->id,
                    'group_id' => $group->id,
                    'group'    => $group->name,
                    'action'   => StudentGroupService::ACTION_JOINED,
                    'actor_id' => $actorId,
                ]);
            }

            $user->groups()->attach($attachData);

            $user->should_pay = $sum;
            $user->save();

            DeptStudent::create([
                'user_id'      => $user->id,
                'payed'        => 0,
                'dept'         => $sum,
                'status_month' => 0,
            ]);

            // Ota / ona (va ixtiyoriy vasiy) hisoblari bir yo'la yaratiladi va
            // users.parents_name / parents_tel birlamchi vasiy bo'yicha
            // yangilanadi.
            // Always pass through the guardian synchroniser. It also mirrors
            // the primary parent's name and phone onto the legacy columns.
            $this->parents->syncGuardians($user, $guardians);

            return $user;
        });
    }
}
