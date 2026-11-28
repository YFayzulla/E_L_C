<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Centre;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Belgilab ko'chirish, ota-ona hisobi (faqat belgilansa) va Kelmaganlar
 * jadvalidagi alohida ota-ona ustunlari.
 */
class GuardiansAndBulkMoveTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private Group $from;
    private Group $to;
    private Group $waiting;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'user', 'student', 'parent', 'reception'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        Centre::for($this->centre, function () {
            $this->waiting = Group::create(['name' => 'Waiting Room', 'monthly_payment' => 0]);
            $this->centre->forceFill(['waiting_room_group_id' => $this->waiting->id])->save();
            $this->from = Group::create(['name' => '26-4 Pre Intermediate', 'monthly_payment' => 400000]);
            $this->to = Group::create(['name' => '26-7 Elementary', 'monthly_payment' => 450000]);
            $this->admin = $this->person('Admin', '998900000060', 'admin');
        });
        $this->centre->refresh();
    }

    private function person(string $name, ?string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function student(string $name, string $phone, Group $group): User
    {
        return Centre::for($this->centre, function () use ($name, $phone, $group) {
            $s = $this->person($name, $phone, 'student');
            $s->groups()->attach($group->id, ['payment' => (int) $group->monthly_payment]);

            return $s;
        });
    }

    private function groupIds(User $student): array
    {
        return Centre::for($this->centre, fn () => $student->groups()->pluck('groups.id')
            ->map(fn ($id) => (int) $id)->sort()->values()->all());
    }

    /* ================================================ belgilab ko'chirish */

    public function test_selected_students_move_together(): void
    {
        $a = $this->student('A Talaba', '998930000061', $this->from);
        $b = $this->student('B Talaba', '998930000062', $this->from);
        $c = $this->student('C Talaba', '998930000063', $this->from);

        $this->actingAs($this->admin)
            ->get(route('group.students', $this->from->id))
            ->assertOk()
            ->assertSee('Belgilanganlarni ko‘chirish', false)
            ->assertSee('form="bulkMoveForm"', false);

        $this->actingAs($this->admin)
            ->post(route('group.students.move'), [
                'student_ids'     => [$a->id, $b->id],
                'from_group_id'   => $this->from->id,
                'target_group_id' => $this->to->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame([$this->to->id], $this->groupIds($a));
        $this->assertSame([$this->to->id], $this->groupIds($b));
        $this->assertSame([$this->from->id], $this->groupIds($c), 'Belgilanmagan talaba joyida qoladi');
        $this->assertSame(450000, (int) $a->fresh()->should_pay, 'Yangi guruh narxi yoziladi');
    }

    public function test_waiting_room_students_are_assigned_in_bulk(): void
    {
        $a = $this->student('Kutayotgan A', '998930000064', $this->waiting);
        $b = $this->student('Kutayotgan B', '998930000065', $this->waiting);

        $this->actingAs($this->admin)
            ->get(route('waiters.index'))
            ->assertOk()
            ->assertSee('Belgilanganlarni ko‘chirish', false);

        $this->actingAs($this->admin)
            ->post(route('group.students.move'), [
                'student_ids'     => [$a->id, $b->id],
                'target_group_id' => $this->to->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([$this->to->id], $this->groupIds($a), 'Kutish zalidan chiqadi');
        $this->assertSame([$this->to->id], $this->groupIds($b));
    }

    public function test_students_can_be_sent_back_to_waiting_room(): void
    {
        $a = $this->student('Qaytuvchi A', '998930000066', $this->from);
        $b = $this->student('Ikki guruhli B', '998930000067', $this->from);
        Centre::for($this->centre, fn () => $b->groups()->attach($this->to->id, ['payment' => 450000]));
        $c = $this->student('Qoluvchi C', '998930000068', $this->from);

        $this->actingAs($this->admin)
            ->get(route('group.students', $this->from->id))
            ->assertOk()
            ->assertSee('<option value="' . $this->waiting->id . '">⏳ Kutish zali</option>', false);

        $this->actingAs($this->admin)
            ->post(route('group.students.move'), [
                'student_ids'     => [$a->id, $b->id],
                'from_group_id'   => $this->from->id,
                'target_group_id' => $this->waiting->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame([$this->waiting->id], $this->groupIds($a), 'Boshqa guruhi yo‘q — Kutish zaliga');
        $this->assertSame(0, (int) $a->fresh()->should_pay);
        $this->assertSame([$this->to->id], $this->groupIds($b), 'Boshqa guruhi bor — faqat shu guruhdan chiqadi');
        $this->assertSame([$this->from->id], $this->groupIds($c));

        $this->actingAs($this->admin)
            ->get(route('waiters.index'))
            ->assertOk()
            ->assertSee('Qaytuvchi A')
            ->assertDontSee('Ikki guruhli B');

        // Kutish zali sahifasida o'zini tanlash varianti chiqmaydi.
        $this->actingAs($this->admin)
            ->get(route('group.students', $this->waiting->id))
            ->assertOk()
            ->assertDontSee('⏳ Kutish zali', false);
    }

    public function test_move_requires_selection(): void
    {
        $this->actingAs($this->admin)
            ->post(route('group.students.move'), ['target_group_id' => $this->to->id])
            ->assertSessionHasErrors('student_ids');
    }

    /* ========================================= ota-ona: hisob faqat belgilansa */

    private function storeStudent(array $guardians, string $phone = '931110060'): User
    {
        $this->actingAs($this->admin)
            ->post(route('student.store'), [
                'name'      => 'Yangi Talaba ' . $phone,
                'phone'     => $phone,
                'group_id'  => [$this->from->id],
                'guardians' => $guardians,
            ])
            ->assertSessionHasNoErrors();

        return User::where('phone', '998' . $phone)->firstOrFail();
    }

    private function parentByPhone(string $phone): User
    {
        return User::where('phone', '998' . $phone)->firstOrFail();
    }

    private function loginAs(string $phone)
    {
        auth()->logout();

        return $this->post('/login', ['login' => $phone, 'password' => $phone]);
    }

    public function test_guardian_without_checkbox_is_saved_but_cannot_log_in(): void
    {
        $student = $this->storeStudent([
            'ota' => ['name' => 'Ota Ismi', 'phone' => '941110060'],
        ]);

        $parent = $this->parentByPhone('941110060');
        $this->assertFalse($parent->login_enabled);
        $this->assertSame('Ota Ismi', $student->fresh()->parents_name, 'Ism va raqam saqlanadi');
        $this->assertSame('998941110060', (string) $student->fresh()->parents_tel);

        $this->loginAs('941110060')->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->actingAs($this->admin)
            ->get(route('student.show', $student->id))
            ->assertOk()
            ->assertSee('Faqat aloqa', false);
    }

    public function test_teacher_sees_student_details_and_guardians_but_cannot_edit(): void
    {
        $student = $this->storeStudent([
            'ota' => ['name' => 'Ataniyazov Baxtiyor', 'phone' => '991032352'],
            'ona' => ['name' => 'Yusupova Dilfuza', 'phone' => '995024172'],
        ], '952152352');
        $student->forceFill(['location' => 'Yangiobod Beruniy 5', 'date_born' => '2007-10-12', 'mark' => 23])->save();

        $teacher = Centre::for($this->centre, function () {
            $t = $this->person('Ustoz', '998900000069', 'user');
            \App\Models\GroupTeacher::create(['group_id' => $this->from->id, 'teacher_id' => $t->id]);

            return $t;
        });

        $this->actingAs($teacher)
            ->get(route('student.show', $student->id))
            ->assertOk()
            ->assertSee('Yangiobod Beruniy 5')
            ->assertSee('2007-10-12')
            ->assertSee('26-4 Pre Intermediate')
            ->assertSee('Ota-ona va vasiylar')
            ->assertSee('Ataniyazov Baxtiyor')
            ->assertSee('+998995024172')
            ->assertDontSee(route('student.edit', $student->id), false)
            ->assertDontSee('/parents/', false)
            ->assertDontSee('Payment History')
            ->assertDontSee('Chegirmalar');

        // Boshqa guruhdagi talaba o'qituvchiga yopiq.
        $stranger = $this->student('Begona', '998930000069', $this->to);
        $this->actingAs($teacher)->get(route('student.show', $stranger->id))->assertForbidden();
    }

    public function test_checkbox_opens_account_for_chosen_guardian_only(): void
    {
        $student = $this->storeStudent([
            'ota' => ['name' => 'Ota', 'phone' => '941110061'],
            'ona' => ['name' => 'Ona', 'phone' => '941110062', 'account' => '1'],
        ], '931110061');

        $this->assertFalse($this->parentByPhone('941110061')->login_enabled);
        $this->assertTrue($this->parentByPhone('941110062')->login_enabled);

        $this->loginAs('941110062')->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        // Tahrirda belgini olib tashlash — kirish yopiladi.
        $mother = $this->parentByPhone('941110062');
        $this->actingAs($this->admin)
            ->put(route('student.update', $student->id), [
                'name'      => $student->name,
                'phone'     => '931110061',
                'group_id'  => [$this->from->id],
                'group_payment' => [$this->from->id => 400000],
                'guardians' => [
                    'ota' => ['id' => $this->parentByPhone('941110061')->id, 'name' => 'Ota', 'phone' => '941110061'],
                    'ona' => ['id' => $mother->id, 'name' => 'Ona', 'phone' => '941110062'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($mother->fresh()->login_enabled);
    }

    public function test_account_checkbox_requires_phone(): void
    {
        $this->actingAs($this->admin)
            ->post(route('student.store'), [
                'name'      => 'Talaba',
                'phone'     => '931110063',
                'group_id'  => [$this->from->id],
                'guardians' => ['ota' => ['name' => 'Ota', 'account' => '1']],
            ])
            ->assertSessionHasErrors('guardians.ota.phone');
    }

    public function test_reusing_a_parent_without_checkbox_keeps_existing_login(): void
    {
        $this->storeStudent(['ota' => ['name' => 'Ota', 'phone' => '941110064', 'account' => '1']], '931110064');
        $this->assertTrue($this->parentByPhone('941110064')->login_enabled);

        // Ikkinchi farzand — shu raqam, belgisiz: mavjud kirish o'chmaydi.
        $this->storeStudent(['ota' => ['name' => 'Ota', 'phone' => '941110064']], '931110065');

        $parent = $this->parentByPhone('941110064');
        $this->assertTrue($parent->login_enabled);
        $this->assertSame(2, $parent->children()->count());
    }

    /* ======================================= Kelmaganlar: alohida ustunlar */

    public function test_absences_table_has_separate_guardian_columns(): void
    {
        $student = $this->storeStudent([
            'ota' => ['name' => 'Saparboyev Ota', 'phone' => '941110066'],
            'ona' => ['name' => 'Saparboyeva Ona', 'phone' => '941110067'],
        ], '931110066');

        Centre::for($this->centre, function () use ($student) {
            $lesson = LessonAndHistory::create(['name' => 'Dars', 'data' => 1, 'group' => $this->from->id]);
            Attendance::create([
                'user_id' => $student->id, 'group_id' => $this->from->id, 'lesson_id' => $lesson->id,
                'status' => 0, 'excused' => false, 'who_checked' => $this->admin->id,
            ]);
        });

        $html = $this->actingAs($this->admin)
            ->get(route('reception.absences.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Talaba tel.')
            ->assertSee('Ota-ona tel.')
            ->assertSee('Saparboyev Ota')
            ->assertSee('Saparboyeva Ona')
            ->assertSee('998941110067')
            ->getContent();

        // Ism telefon ustunida takrorlanmaydi ("Ism: raqam" ko'rinishi yo'q).
        $this->assertStringNotContainsString($student->name . ':', $html);
    }
}
