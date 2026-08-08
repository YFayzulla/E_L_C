<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\Group;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Tenancy\CentreContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Yangi o'quv markazini ochishning yagona joyi.
 *
 * "Markaz" — bu `centres` jadvalidagi bitta qator emas. Ishlaydigan markazga
 * kamida uchta narsa kerak:
 *
 *   1. markazning o'zi;
 *   2. kutish zali (`centres.waiting_room_group_id`) — usiz talaba qabul
 *      qilish, guruhdan chiqarish va kutish xonasi sahifasi ishlamaydi;
 *   3. SMS shablonlari — ilgari ular migratsiya ichida INSERT qilingandi,
 *      ya'ni faqat birinchi markaz uchun bir marta ishlagan.
 *
 * Shu uchtasi bir joyda tug'ilmasa, ikkinchi markaz jimgina nosoz bo'lib
 * ochiladi. Konsol buyrug'i ham, super-admin paneli ham shu servisni
 * chaqiradi — ikkalasi ayri yozilsa, vaqt o'tib bir-biridan uzoqlashardi.
 */
class CentreProvisioner
{
    public function __construct(
        private CentreContext $context,
        private CentreMembershipService $membership,
    ) {
    }

    /**
     * DNS yorlig'i qoidasi: kichik harf, raqam va tire; tire bilan
     * boshlanmaydi va tugamaydi; 63 belgidan oshmaydi (RFC 1035).
     */
    public const SLUG_PATTERN = '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/';

    /**
     * Slug'ni tekshiradi va sababini aytadi. Null — muammo yo'q.
     */
    public function slugProblem(string $slug): ?string
    {
        $slug = Str::lower(trim($slug));

        if ($slug === '') {
            return 'Slug bo‘sh bo‘lishi mumkin emas.';
        }

        if (! preg_match(self::SLUG_PATTERN, $slug)) {
            return 'Slug faqat kichik harf, raqam va tiredan iborat bo‘lishi, '
                . 'tire bilan boshlanmasligi va tugamasligi kerak (eng ko‘pi 63 belgi).';
        }

        if (Centre::isReservedSlug($slug)) {
            return "«{$slug}» band: u pochta, DNS yoki platformaning o‘z "
                . 'subdomenlariga tegishli.';
        }

        if (Centre::withTrashed()->where('slug', $slug)->exists()) {
            return "«{$slug}» allaqachon band.";
        }

        return null;
    }

    /**
     * Markazni to'liq ochadi va uni qaytaradi.
     *
     * Hammasi bitta tranzaksiyada: yarim ochilgan markaz — kutish zalisiz
     * yoki adminsiz — eng yomon holat, chunki u ishlayotganday ko'rinadi.
     *
     * @param  array{
     *     slug: string, name: string,
     *     certificate_prefix?: string|null, timezone?: string|null,
     *     brand_color?: string|null, phone?: string|null, address?: string|null,
     *     admin?: array{name: string, phone: string, password: string, email?: string|null}|null
     * }  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Centre
    {
        $slug = Str::lower(trim($data['slug'] ?? ''));

        if ($problem = $this->slugProblem($slug)) {
            throw ValidationException::withMessages(['slug' => $problem]);
        }

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Markaz nomi kerak.']);
        }

        return DB::transaction(function () use ($data, $slug, $name) {
            $centre = Centre::create([
                'slug'               => $slug,
                'name'               => $name,
                'status'             => Centre::STATUS_ACTIVE,
                'timezone'           => $data['timezone'] ?? config('app.timezone', 'Asia/Tashkent'),
                'certificate_prefix' => Str::upper($data['certificate_prefix'] ?? Str::substr($slug, 0, 3)),
                'brand_color'        => $data['brand_color'] ?? null,
                'phone'              => $data['phone'] ?? null,
                'address'            => $data['address'] ?? null,
                'sms_enabled'        => true,
            ]);

            // Bundan keyingi hamma narsa yangi markaz kontekstida yoziladi:
            // BelongsToCentre centre_id ni o'zi qo'yadi va Spatie'ning team
            // id'si ham shu markazga ishora qiladi.
            $this->context->for($centre, function () use ($centre, $data) {
                $this->createWaitingRoom($centre);
                $this->seedSmsTemplates();

                if (! empty($data['admin'])) {
                    $this->attachAdmin($centre, $data['admin']);
                }
            });

            return $centre->refresh();
        });
    }

    /**
     * Kutish zali — talaba hali guruhga biriktirilmagan paytdagi joyi.
     * Har bir markazniki o'ziniki: ilgari bu butun o'rnatma uchun bitta
     * qattiq yozilgan `id = 1` edi.
     */
    public function createWaitingRoom(Centre $centre): Group
    {
        $group = Group::create([
            'name'            => 'Kutish zali',
            'description'     => 'Guruhga biriktirilmagan talabalar',
            'monthly_payment' => 0,
        ]);

        $centre->forceFill(['waiting_room_group_id' => $group->id])->save();

        return $group;
    }

    /**
     * Markazga standart SMS shablonlarini beradi.
     * Mavjud slug qayta yozilmaydi — buyruqni takror ishlatish xavfsiz.
     *
     * @return int nechta yangi shablon qo'shildi
     */
    public function seedSmsTemplates(): int
    {
        $existing = SmsTemplate::pluck('slug')->all();
        $added = 0;

        foreach (SmsTemplate::defaults() as $template) {
            if (in_array($template['slug'], $existing, true)) {
                continue;
            }

            SmsTemplate::create($template + ['is_active' => true]);
            $added++;
        }

        return $added;
    }

    /**
     * Markazga admin biriktiradi. Telefon bo'yicha odam allaqachon bo'lsa,
     * yangi hisob yaratilmaydi — u shunchaki shu markazda ham admin bo'ladi.
     * Bir odam bir nechta markazda ishlashi mumkin, va telefon raqami
     * O'zbekistonda odamga xos, ya'ni bitta odam = bitta hisob.
     *
     * @param  array{name: string, phone: string, password: string, email?: string|null}  $admin
     */
    public function attachAdmin(Centre $centre, array $admin): User
    {
        $phone = User::normalizePhone($admin['phone'] ?? null);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'admin.phone' => 'Admin telefon raqami kerak.',
            ]);
        }

        // Foydalanuvchi platforma qatlamida — markaz scope'i unga tegmaydi,
        // shuning uchun qidiruv global va bu ataylab shunday.
        $user = User::where('phone', $phone)->first();

        if ($user === null) {
            $password = (string) ($admin['password'] ?? '');

            if (Str::length($password) < 6) {
                throw ValidationException::withMessages([
                    'admin.password' => 'Parol kamida 6 belgidan iborat bo‘lishi kerak.',
                ]);
            }

            $user = new User();
            $user->forceFill([
                'name'              => trim((string) ($admin['name'] ?? 'Administrator')),
                'phone'             => $phone,
                'email'             => $admin['email'] ?? null,
                'password'          => Hash::make($password),
                // Markazni ochgan odam o'z pochtasini tasdiqlab o'tirmasin:
                // uni super-admin qo'lda yaratmoqda, ya'ni u allaqachon
                // ishonchli kanal orqali tasdiqlangan.
                'email_verified_at' => ($admin['email'] ?? null) ? now() : null,
            ])->save();
        }

        $this->membership->attach($centre, $user, 'admin');

        return $user;
    }
}
