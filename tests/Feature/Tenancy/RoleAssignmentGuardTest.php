<?php

namespace Tests\Feature\Tenancy;

use App\Models\Centre;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rol berish va markazga a'zo qilish AJRALMAS.
 *
 * Yalang'och `$user->assignRole('student')` rol qatorini yozadi, lekin
 * `centre_user` qatorini YOZMAYDI. Natijasi jimgina va o'ta chalg'ituvchi:
 * odam yaratiladi, ro'yxatlarda ko'rinadi, hisobi ishlaydi — lekin kira
 * olmaydi, chunki EnsureCentreMember uni to'sadi.
 *
 * Bu aynan production'da bo'lgan: ko'chishdan keyin yaratilgan talaba
 * 403 olardi va sababini topish uchun bazani qo'lda titish kerak edi.
 *
 * Shuning uchun bu yerda ikkita narsa qo'riqlanadi: xatti-harakat va
 * qoidaning o'zi — kod bo'ylab yalang'och assignRole() qolmasin.
 */
class RoleAssignmentGuardTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first()
            ?? tap(new Centre())->forceFill([
                'slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE,
            ])->save();

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first();
    }

    public function test_attaching_to_the_current_centre_creates_the_membership(): void
    {
        $user = User::factory()->create();

        Centre::for($this->centre, function () use ($user) {
            app(CentreMembershipService::class)->attachToCurrent($user, 'student');
        });

        $this->assertTrue(
            $user->centres()
                ->whereKey($this->centre->id)
                ->wherePivot('status', Centre::MEMBER_ACTIVE)
                ->exists(),
            'Rol berilgan odam markazga FAOL a’zo bo‘lishi kerak — aks holda kira olmaydi.'
        );

        Centre::for($this->centre, function () use ($user) {
            $user->unsetRelation('roles');
            $this->assertTrue($user->hasRole('student'));
        });
    }

    /**
     * Markazsiz rol berishning imkoni yo'q — `model_has_roles.centre_id`
     * NOT NULL va birlamchi kalit tarkibida.
     *
     * Muhimi: xato TUSHUNARLI bo'lishi kerak. Ilgari bu yerda tushunarsiz
     * "Integrity constraint violation" chiqardi va sababini topish uchun
     * sxemani o'qish kerak edi.
     */
    public function test_assigning_without_a_centre_fails_with_a_clear_message(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/markaz konteksti kerak/u');

        app(CentreMembershipService::class)->attachToCurrent($user, 'student');
    }

    /**
     * Qoidaning o'zi: `assignRole()` faqat CentreMembershipService ichida.
     *
     * Bu "toza kod" talabi emas — chetlab o'tilgan har bir chaqiruv kira
     * olmaydigan foydalanuvchi yaratadi va buni test emas, faqat shikoyat
     * qilgan mijoz aniqlaydi.
     */
    public function test_no_bare_assign_role_outside_the_membership_service(): void
    {
        $allowed = 'CentreMembershipService.php';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || $file->getFilename() === $allowed) {
                continue;
            }

            foreach (file($file->getPathname()) as $no => $line) {
                // Izohlar hisobga olinmaydi — ular tushuntirish uchun.
                $trimmed = ltrim($line);

                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                    continue;
                }

                if (str_contains($line, '->assignRole(')) {
                    $offenders[] = str_replace(app_path(), 'app', $file->getPathname())
                        . ':' . ($no + 1) . '  ' . trim($line);
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", array_merge(
            ['Yalang‘och assignRole() topildi. attachToCurrent() ishlating —',
             'aks holda odam rol oladi, lekin markazga a’zo bo‘lmay kira olmaydi:', ''],
            $offenders
        )));
    }
}
