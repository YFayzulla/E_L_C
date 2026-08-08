<?php

namespace Tests\Feature\Tenancy;

use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Tenancy\TenantQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bitta savol: ma'lumot markazlar orasida oqib ketmaydimi?
 *
 * Ikkala markazda ATAYLAB bir xil ko'rinadigan ma'lumot bor — bir xil
 * nomli guruh, bir xil ismli o'qituvchi va talabalar. Ism bo'yicha
 * ajratib bo'lmaydi, ya'ni sinov faqat markaz filtri haqiqatan
 * ishlagandagina o'tadi.
 *
 * Bu — HTTP darajasidagi to'plamning (scratchpad/tenant_leak*.php)
 * doimiy, serversiz qismi: u ikkita ishlab turgan subdomen talab qiladi,
 * bu esa CI da ham yuradi.
 */
class CrossCentreIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Centre $alpha;
    private Centre $beta;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->alpha = $this->makeCentre('alpha');
        $this->beta  = $this->makeCentre('beta');

        $this->seedCentre($this->alpha, '9989011122', '9989355500');
        $this->seedCentre($this->beta, '9989012399', '9989355510');
    }

    private function makeCentre(string $slug): Centre
    {
        $centre = Centre::withoutGlobalScopes()->where('slug', $slug)->first() ?? new Centre();

        $centre->forceFill([
            'slug'   => $slug,
            'name'   => ucfirst($slug),
            'status' => Centre::STATUS_ACTIVE,
        ])->save();

        return $centre;
    }

    /**
     * Har bir markazga bir xil ko'rinadigan to'plam: "IELTS Intensive"
     * nomli guruh, "Dilnoza Karimova" ismli o'qituvchi va "Javohir Aliyev"
     * ismli talaba. Faqat telefon raqamlari farq qiladi.
     */
    private function seedCentre(Centre $centre, string $teacherPrefix, string $studentPrefix): void
    {
        $service = app(CentreMembershipService::class);

        Centre::for($centre, function () use ($centre, $service, $teacherPrefix, $studentPrefix) {
            $group = Group::create(['name' => 'IELTS Intensive', 'monthly_payment' => 700000]);

            $teacher = $this->makeUser('Dilnoza Karimova', $teacherPrefix . '33');
            $service->attach($centre, $teacher, 'user');
            GroupTeacher::create(['group_id' => $group->id, 'teacher_id' => $teacher->id]);

            $student = $this->makeUser('Javohir Aliyev', $studentPrefix . '01');
            $service->attach($centre, $student, 'student');
            $student->groups()->attach($group->id, ['payment' => 700000]);
        });
    }

    private function makeUser(string $name, string $phone): User
    {
        $user = new User();
        $user->forceFill([
            'name'     => $name,
            'phone'    => $phone,
            'password' => Hash::make('secret'),
        ])->save();

        return $user;
    }

    /**
     * Satrga o'tkaziladi: SQLite ustun turiga qarab telefonni son qilib
     * qaytaradi, MySQL esa satr — solishtiruv drayverga bog'liq bo'lmasin.
     */
    private function phones(iterable $users): array
    {
        return collect($users)->map(fn ($u) => (string) $u->phone)->sort()->values()->all();
    }

    /* ===================================================== rol bo'yicha */

    public function test_teacher_lists_are_separate(): void
    {
        $inAlpha = Centre::for($this->alpha, fn () => User::role('user')->get());
        $inBeta  = Centre::for($this->beta, fn () => User::role('user')->get());

        $this->assertCount(1, $inAlpha, 'Alpha da bitta o‘qituvchi bo‘lishi kerak');
        $this->assertCount(1, $inBeta, 'Beta da bitta o‘qituvchi bo‘lishi kerak');

        // Ismlar bir xil — shuning uchun telefon bo'yicha.
        $this->assertSame(['998901112233'], $this->phones($inAlpha));
        $this->assertSame(['998901239933'], $this->phones($inBeta));
    }

    public function test_student_lists_are_separate(): void
    {
        $inAlpha = Centre::for($this->alpha, fn () => User::role('student')->get());
        $inBeta  = Centre::for($this->beta, fn () => User::role('student')->get());

        $this->assertSame(['998935550001'], $this->phones($inAlpha));
        $this->assertSame(['998935551001'], $this->phones($inBeta));
    }

    /* ================================================ obyektlar bo'yicha */

    public function test_groups_are_separate_even_with_the_same_name(): void
    {
        $alphaGroups = Centre::for($this->alpha, fn () => Group::pluck('id')->all());
        $betaGroups  = Centre::for($this->beta, fn () => Group::pluck('id')->all());

        $this->assertNotEmpty($alphaGroups);
        $this->assertNotEmpty($betaGroups);
        $this->assertEmpty(
            array_intersect($alphaGroups, $betaGroups),
            'Bir xil nomli guruhlar ham ajralgan bo‘lishi kerak'
        );
    }

    /** Route-model binding shu scope orqali o'tadi, ya'ni bu 404 ni ta'minlaydi. */
    public function test_another_centres_group_cannot_be_found_by_id(): void
    {
        $betaGroup = Centre::for($this->beta, fn () => Group::first());

        Centre::for($this->alpha, function () use ($betaGroup) {
            $this->assertNull(
                Group::find($betaGroup->id),
                'Alpha kontekstida beta guruhi topilmasligi kerak'
            );
        });
    }

    /**
     * `users` da centre_id yo'q — a'zolik many-to-many — ya'ni global scope
     * bu yerga tegmaydi va URL'dan kelgan id ni faqat shu scope to'sadi.
     */
    public function test_in_current_centre_scope_hides_other_centres_people(): void
    {
        $betaStudent = Centre::for($this->beta, fn () => User::role('student')->first());

        Centre::for($this->alpha, function () use ($betaStudent) {
            $this->assertNull(
                User::inCurrentCentre()->find($betaStudent->id),
                'Alpha kontekstida beta talabasi topilmasligi kerak'
            );
        });

        Centre::for($this->beta, function () use ($betaStudent) {
            $this->assertNotNull(
                User::inCurrentCentre()->find($betaStudent->id),
                'O‘z markazida esa topilishi kerak'
            );
        });
    }

    /**
     * Xom so'rov global scope'ni butunlay chetlab o'tadi — admin panelidagi
     * o'qituvchilar jadvali aynan shu sababdan boshqa markazning
     * o'qituvchilarini ko'rsatardi.
     */
    public function test_raw_queries_go_through_the_tenant_filter(): void
    {
        $alphaRows = Centre::for($this->alpha, fn () => TenantQuery::table('group_teachers')->get());
        $betaRows  = Centre::for($this->beta, fn () => TenantQuery::table('group_teachers')->get());

        $this->assertCount(1, $alphaRows);
        $this->assertCount(1, $betaRows);
        $this->assertNotSame($alphaRows[0]->teacher_id, $betaRows[0]->teacher_id);
    }

    public function test_the_tenant_filter_refuses_a_platform_table(): void
    {
        $this->expectException(\RuntimeException::class);

        Centre::for($this->alpha, fn () => TenantQuery::table('users'));
    }

    /* ============================== bitta odam, ikkita markaz — eng o'tkiri */

    public function test_one_person_teaching_at_both_centres_sees_only_the_current_one(): void
    {
        $service = app(CentreMembershipService::class);
        $teacher = Centre::for($this->alpha, fn () => User::role('user')->first());

        $betaGroup = Centre::for($this->beta, function () use ($service, $teacher) {
            $service->attach($this->beta, $teacher, 'user');
            $group = Group::first();
            GroupTeacher::create(['group_id' => $group->id, 'teacher_id' => $teacher->id]);

            return $group;
        });

        $alphaGroup = Centre::for($this->alpha, fn () => Group::first());

        $seenInAlpha = Centre::for($this->alpha, fn () => $teacher->teacherGroups()->pluck('groups.id')->all());
        $seenInBeta  = Centre::for($this->beta, fn () => $teacher->teacherGroups()->pluck('groups.id')->all());

        $this->assertContains($alphaGroup->id, $seenInAlpha);
        $this->assertNotContains($betaGroup->id, $seenInAlpha, 'Alpha da beta guruhi ko‘rinmasligi kerak');

        $this->assertContains($betaGroup->id, $seenInBeta);
        $this->assertNotContains($alphaGroup->id, $seenInBeta, 'Beta da alpha guruhi ko‘rinmasligi kerak');
    }

    /* ==================================================== a'zolik chegarasi */

    public function test_membership_does_not_leak_between_centres(): void
    {
        $alphaTeacher = Centre::for($this->alpha, fn () => User::role('user')->first());

        $this->assertTrue(
            $alphaTeacher->centres()->whereKey($this->alpha->id)->exists(),
            'O‘z markaziga a’zo bo‘lishi kerak'
        );

        $this->assertFalse(
            $alphaTeacher->centres()->whereKey($this->beta->id)->exists(),
            'Boshqa markazga a’zo BO‘LMASLIGI kerak'
        );
    }
}
