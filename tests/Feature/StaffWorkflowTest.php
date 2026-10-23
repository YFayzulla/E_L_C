<?php

namespace Tests\Feature;

use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\ReceptionStudent;
use App\Models\StaffNotification;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * O'qituvchi / reception / admin oqimlari: ko'chirish, reception'dan guruhga
 * yozish, o'qituvchi talaba qo'shishi va admin bildirishnomalari.
 *
 * So'rovlar `localhost` ga ketadi — phpunit.xml dagi APP_DEFAULT_CENTRE=alpha
 * uni alpha markaziga bog'laydi.
 */
class StaffWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private User $teacher;
    private User $colleague;
    private User $reception;
    private Group $mine;
    private Group $theirs;
    private Group $waiting;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'user', 'student', 'parent', 'reception'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill([
            'slug'   => 'alpha',
            'name'   => 'Alpha',
            'status' => Centre::STATUS_ACTIVE,
        ])->save();

        Centre::for($this->centre, function () {
            $service = app(CentreMembershipService::class);

            $this->waiting = Group::create(['name' => 'Waiting Room', 'monthly_payment' => 0]);
            $this->centre->forceFill(['waiting_room_group_id' => $this->waiting->id])->save();

            $this->mine = Group::create(['name' => 'Mine', 'monthly_payment' => 500000]);
            $this->theirs = Group::create(['name' => 'Theirs', 'monthly_payment' => 600000]);

            $this->admin = $this->person('Admin', '998900000001', 'admin', $service);
            $this->teacher = $this->person('Teacher', '998900000002', 'user', $service);
            $this->colleague = $this->person('Colleague', '998900000003', 'user', $service);
            $this->reception = $this->person('Reception', '998900000004', 'reception', $service);

            GroupTeacher::create(['group_id' => $this->mine->id, 'teacher_id' => $this->teacher->id]);
            GroupTeacher::create(['group_id' => $this->theirs->id, 'teacher_id' => $this->colleague->id]);
        });

        $this->centre->refresh();
    }

    private function person(string $name, string $phone, string $role, CentreMembershipService $service): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        $service->attach($this->centre, $user, $role);

        return $user;
    }

    private function student(string $name, string $phone, Group $group, int $payment): User
    {
        return Centre::for($this->centre, function () use ($name, $phone, $group, $payment) {
            $user = new User();
            $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret'), 'should_pay' => $payment])->save();
            app(CentreMembershipService::class)->attach($this->centre, $user, 'student');
            $user->groups()->attach($group->id, ['payment' => $payment]);

            return $user;
        });
    }

    private function inCentre(callable $fn)
    {
        return Centre::for($this->centre, $fn);
    }

    /* ============================================== 1. ko'chirish */

    public function test_teacher_cannot_submit_payment_on_transfer(): void
    {
        $student = $this->student('Ali', '998911111110', $this->mine, 450000);

        $this->actingAs($this->teacher)
            ->post(route('student.transfer', $student->id), [
                'group_id' => [$this->theirs->id],
                'group_payment' => [$this->theirs->id => 1],
            ])
            ->assertSessionHasErrors('group_payment');

        $this->inCentre(function () use ($student) {
            $this->assertSame([$this->mine->id], $student->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame(0, StaffNotification::count());
        });
    }

    public function test_teacher_transfer_uses_group_price_and_notifies_admin(): void
    {
        $student = $this->student('Ali', '998911111111', $this->mine, 450000);

        $this->actingAs($this->teacher)
            ->post(route('student.transfer', $student->id), [
                'group_id' => [$this->theirs->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->inCentre(function () use ($student) {
            $this->assertSame(
                600000,
                (int) DB::table('group_user')->where('user_id', $student->id)->where('group_id', $this->theirs->id)->value('payment'),
                'O‘qituvchi yuborgan summa emas, guruh narxi yozilishi kerak'
            );
            $this->assertSame(600000, (int) $student->fresh()->should_pay);

            $notice = StaffNotification::where('user_id', $this->admin->id)->first();
            $this->assertNotNull($notice, 'Adminga bildirishnoma borishi kerak');
            $this->assertSame(StaffNotification::TYPE_STUDENT_TRANSFERRED, $notice->type);
            $this->assertStringContainsString('Mine', $notice->body);
            $this->assertStringContainsString('Theirs', $notice->body);
        });
    }

    public function test_admin_transfer_keeps_payment_and_sends_no_notification(): void
    {
        $student = $this->student('Vali', '998911111112', $this->mine, 450000);

        $this->actingAs($this->admin)
            ->post(route('student.transfer', $student->id), [
                'group_id' => [$this->theirs->id],
                'group_payment' => [$this->theirs->id => 300000],
            ])
            ->assertSessionHasNoErrors();

        $this->inCentre(function () use ($student) {
            $this->assertSame(300000, (int) $student->fresh()->should_pay);
            $this->assertSame(0, StaffNotification::count());
        });
    }

    public function test_transfer_form_hides_payments_from_teacher(): void
    {
        $student = $this->student('Sardor', '998911111113', $this->mine, 450000);

        $this->actingAs($this->teacher)
            ->get(route('student.transfer.form', $student->id))
            ->assertOk()
            ->assertDontSee('group_payment[', false)
            ->assertDontSee('@endif', false)
            ->assertSee('Oylik to‘lovni administrator belgilaydi', false);
    }

    public function test_admin_sees_and_opens_notifications(): void
    {
        $student = $this->student('Ali', '998911111114', $this->mine, 450000);

        $this->actingAs($this->teacher)->post(route('student.transfer', $student->id), [
            'group_id' => [$this->theirs->id],
        ]);

        $notice = $this->inCentre(fn () => StaffNotification::where('user_id', $this->admin->id)->firstOrFail());

        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Bildirishnomalar');
        $this->actingAs($this->admin)->get(route('notifications.index'))->assertOk()->assertSee($notice->title);

        $this->actingAs($this->admin)
            ->get(route('notifications.open', $notice->id))
            ->assertRedirect(route('student.edit', $student->id));

        $this->assertNotNull($this->inCentre(fn () => $notice->fresh()->read_at));

        // Boshqa odamning bildirishnomasi ochilmaydi.
        $this->actingAs($this->teacher)->get(route('notifications.open', $notice->id))->assertNotFound();
    }

    /* ======================================== 2. reception -> guruh */

    private function receptionRecord(array $attributes = []): ReceptionStudent
    {
        return $this->inCentre(fn () => ReceptionStudent::create(array_merge([
            'name'         => 'Yangi Talaba',
            'phone'        => '+998 93 123 45 67',
            'parent_name'  => 'Ota',
            'parent_phone' => '93 765 43 21',
            'status'       => ReceptionStudent::STATUS_TESTED,
        ], $attributes)));
    }

    public function test_reception_enrolls_record_into_group(): void
    {
        $record = $this->receptionRecord(['recommended_group_id' => $this->mine->id]);

        $this->actingAs($this->reception)
            ->get(route('reception.students.enroll', $record->id))
            ->assertOk()
            ->assertSee('931234567');

        $this->actingAs($this->reception)
            ->post(route('reception.students.enroll.store', $record->id), [
                'name'         => 'Yangi Talaba',
                'phone'        => '931234567',
                'parent_name'  => 'Ota',
                'parent_phone' => '937654321',
                'group_id'     => $this->mine->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reception.students.index'));

        $this->inCentre(function () use ($record) {
            $record->refresh();
            $student = User::where('phone', '998931234567')->firstOrFail();

            $this->assertSame($student->id, (int) $record->student_id);
            $this->assertSame(ReceptionStudent::STATUS_ASSIGNED, $record->status);
            $this->assertTrue($student->hasRole('student'));
            $this->assertSame([$this->mine->id], $student->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all());
            // Reception summani yozolmaydi — guruh narxi.
            $this->assertSame(500000, (int) $student->should_pay);
            $this->assertTrue($student->belongsToCentre($this->centre->id));

            $this->assertSame(1, StaffNotification::where('user_id', $this->admin->id)
                ->where('type', StaffNotification::TYPE_STUDENT_ENROLLED)->count());
        });

        // Ikkinchi marta yozib bo'lmaydi.
        $this->actingAs($this->reception)
            ->get(route('reception.students.enroll', $record->id))
            ->assertRedirect(route('reception.students.index'));
    }

    public function test_teacher_claims_record_into_own_group_only(): void
    {
        $forMe = $this->receptionRecord(['recommended_group_id' => $this->mine->id, 'phone' => '931110000']);
        $forColleague = $this->receptionRecord(['recommended_group_id' => $this->theirs->id, 'phone' => '931110001']);

        $this->actingAs($this->teacher)
            ->get(route('teacher.reception.index'))
            ->assertOk()
            ->assertSee(route('reception.students.enroll', $forMe->id), false)
            ->assertDontSee(route('reception.students.enroll', $forColleague->id), false);

        $this->actingAs($this->teacher)
            ->get(route('reception.students.enroll', $forColleague->id))
            ->assertForbidden();

        // O'z yozuvini hamkasbining guruhiga yozolmaydi.
        $this->actingAs($this->teacher)
            ->post(route('reception.students.enroll.store', $forMe->id), [
                'name' => 'X', 'phone' => '931110000', 'group_id' => $this->theirs->id,
            ])
            ->assertSessionHasErrors('group_id');

        $this->actingAs($this->teacher)
            ->post(route('reception.students.enroll.store', $forMe->id), [
                'name' => 'X', 'phone' => '931110000', 'group_id' => $this->mine->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('teacher.reception.index'));

        $this->inCentre(fn () => $this->assertNotNull($forMe->fresh()->student_id));
    }

    public function test_non_admin_cannot_submit_payment_on_enroll(): void
    {
        $record = $this->receptionRecord(['recommended_group_id' => $this->mine->id]);

        foreach ([$this->reception, $this->teacher] as $actor) {
            $this->actingAs($actor)
                ->post(route('reception.students.enroll.store', $record->id), [
                    'name' => 'X', 'phone' => '931234567', 'group_id' => $this->mine->id, 'payment' => '1',
                ])
                ->assertSessionHasErrors('payment');
        }

        $this->inCentre(fn () => $this->assertNull($record->fresh()->student_id));
    }

    public function test_enroll_rejects_existing_phone(): void
    {
        $this->student('Bor', '998931234567', $this->mine, 500000);
        $record = $this->receptionRecord();

        $this->actingAs($this->reception)
            ->post(route('reception.students.enroll.store', $record->id), [
                'name' => 'Takror', 'phone' => '931234567', 'group_id' => $this->mine->id,
            ])
            ->assertSessionHasErrors('phone');
    }

    /* ============================================ 3. PDF test fayli */

    public function test_reception_can_upload_pdf_test(): void
    {
        Storage::fake('local');

        $this->actingAs($this->reception)
            ->post(route('reception.students.store'), [
                'name'       => 'PDF Talaba',
                'status'     => ReceptionStudent::STATUS_TESTED,
                'test_image' => UploadedFile::fake()->create('test.pdf', 300, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reception.students.index'));

        $record = $this->inCentre(fn () => ReceptionStudent::where('name', 'PDF Talaba')->firstOrFail());
        $this->assertStringEndsWith('.pdf', $record->test_image_path);
        Storage::disk('local')->assertExists($record->test_image_path);
    }

    /* ======================================== 4. talaba qo'shish */

    public function test_teacher_adds_new_student_to_own_group(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('teacher.students.create', ['group' => $this->mine->id]))
            ->assertOk()
            ->assertDontSee('Theirs');

        $this->actingAs($this->teacher)
            ->post(route('teacher.students.store'), [
                'name' => 'Teacher Added',
                'phone' => '944444443',
                'group_id' => [$this->mine->id],
                'group_payment' => [$this->mine->id => 1],
            ])
            ->assertSessionHasErrors('group_payment');

        $this->assertFalse(User::where('phone', '998944444443')->exists());

        $this->actingAs($this->teacher)
            ->post(route('teacher.students.store'), [
                'name' => 'Teacher Added',
                'phone' => '944444444',
                'group_id' => [$this->mine->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('teacher.groups'));

        $this->inCentre(function () {
            $student = User::where('phone', '998944444444')->firstOrFail();
            $this->assertSame(500000, (int) $student->should_pay);
            $this->assertSame(1, StaffNotification::where('type', StaffNotification::TYPE_STUDENT_ENROLLED)->count());
        });
    }

    public function test_teacher_cannot_add_student_to_colleague_group(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.students.store'), [
                'name' => 'Nope',
                'phone' => '944444445',
                'group_id' => [$this->theirs->id],
            ])
            ->assertForbidden();

        $this->assertFalse(User::where('phone', '998944444445')->exists());
    }

    public function test_admin_adds_waiting_student_to_teacher_group(): void
    {
        $waiting = $this->student('Kutayotgan', '998955555555', $this->waiting, 0);

        $this->actingAs($this->admin)
            ->get(route('teacher.show', $this->teacher->id))
            ->assertOk()
            ->assertSee('Guruhga talaba qo‘shish', false)
            ->assertSee('Kutayotgan');

        $this->actingAs($this->admin)
            ->post(route('teacher_group.students.store', $this->teacher->id), [
                'student_id' => $waiting->id,
                'group_id' => $this->mine->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->inCentre(function () use ($waiting) {
            $this->assertSame(
                [$this->mine->id],
                $waiting->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all(),
                'Kutish zalidan chiqib, faqat yangi guruhda qolishi kerak'
            );
            $this->assertSame(500000, (int) $waiting->fresh()->should_pay);
        });

        // O'qituvchiga biriktirilmagan guruhga emas.
        $this->actingAs($this->admin)
            ->post(route('teacher_group.students.store', $this->teacher->id), [
                'student_id' => $waiting->id,
                'group_id' => $this->theirs->id,
            ])
            ->assertSessionHasErrors('group_id');
    }

    /* =============================== 6. talabalar ro'yxati va sanoq */

    public function test_student_list_and_dashboard_skip_waiting_room(): void
    {
        $this->student('Faol Talaba', '998966666661', $this->mine, 500000);
        $this->student('Kutish Talaba', '998966666662', $this->waiting, 0);

        $this->actingAs($this->admin)
            ->get(route('student.index'))
            ->assertOk()
            ->assertSee('Faol Talaba')
            ->assertDontSee('Kutish Talaba')
            ->assertSee('1 ta talaba ko‘rsatilmadi', false);

        $this->actingAs($this->admin)
            ->get(route('student.index', ['all' => 1]))
            ->assertOk()
            ->assertSee('Kutish Talaba');

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('number_of_students', 1)
            ->assertViewHas('number_of_waiting', 1);
    }
}
