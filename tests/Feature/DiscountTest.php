<?php

namespace Tests\Feature;

use App\Models\Centre;
use App\Models\Group;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Services\DiscountService;
use App\Tenancy\TenantQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Chegirmalar: foizda, doimiy yoki muddatli; bitta talabaga yoki guruhga.
 * group_user.payment — to'lanadigan summa, base_payment — asl narx.
 */
class DiscountTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private Group $group;
    private Group $other;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        Centre::for($this->centre, function () {
            $this->group = Group::create(['name' => '26-4 Pre Intermediate', 'monthly_payment' => 400000]);
            $this->other = Group::create(['name' => '26-7 Elementary', 'monthly_payment' => 500000]);
            $this->admin = $this->person('Admin', '998900000070', 'admin');
        });
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function student(string $phone, Group $group, int $payment = 400000): User
    {
        return Centre::for($this->centre, function () use ($phone, $group, $payment) {
            $s = $this->person('Talaba ' . $phone, $phone, 'student');
            $s->groups()->attach($group->id, ['payment' => $payment, 'base_payment' => $payment]);
            $s->forceFill(['should_pay' => $payment])->save();
            $s->deptStudent()->create(['dept' => $payment, 'payed' => 0, 'status_month' => 0]);

            return $s;
        });
    }

    /** @return array{payment: int, base: int, should_pay: int, dept: int} */
    private function money(User $student, ?Group $group = null): array
    {
        return Centre::for($this->centre, function () use ($student, $group) {
            $row = TenantQuery::table('group_user')
                ->where('user_id', $student->id)
                ->where('group_id', ($group ?? $this->group)->id)
                ->first();
            $fresh = $student->fresh();

            return [
                'payment'    => (int) $row->payment,
                'base'       => (int) $row->base_payment,
                'should_pay' => (int) $fresh->should_pay,
                'dept'       => (int) $fresh->deptStudent->dept,
            ];
        });
    }

    private function inCentre(callable $fn)
    {
        return Centre::for($this->centre, $fn);
    }

    public function test_permanent_discount_for_one_student(): void
    {
        $student = $this->student('998930000071', $this->group);
        $neighbour = $this->student('998930000072', $this->group);

        $this->actingAs($this->admin)
            ->post(route('discounts.student.store', $student->id), [
                'percent'   => 20,
                'period'    => 'permanent',
                'starts_on' => today()->toDateString(),
                'reason'    => 'Aka-uka',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(['payment' => 320000, 'base' => 400000, 'should_pay' => 320000, 'dept' => 320000], $this->money($student));
        $this->assertSame(400000, $this->money($neighbour)['payment'], 'Boshqa talabaga ta’sir qilmaydi');

        $this->actingAs($this->admin)
            ->get(route('student.show', $student->id))
            ->assertOk()
            ->assertSee('Chegirmalar')
            ->assertSee('Aka-uka')
            ->assertSee('−20%', false);

        $this->actingAs($this->admin)
            ->get(route('dept.index'))
            ->assertOk()
            ->assertSee('−20%', false);
    }

    public function test_group_discount_applies_to_every_student(): void
    {
        $a = $this->student('998930000073', $this->group);
        $b = $this->student('998930000074', $this->group);

        $this->actingAs($this->admin)
            ->get(route('group.students', $this->group->id))
            ->assertOk()
            ->assertSee('Guruhga chegirma');

        $this->actingAs($this->admin)
            ->post(route('discounts.group.store', $this->group->id), [
                'percent'   => 10,
                'period'    => 'permanent',
                'starts_on' => today()->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(360000, $this->money($a)['payment']);
        $this->assertSame(360000, $this->money($b)['payment']);
        $this->assertSame(2, $this->inCentre(fn () => StudentDiscount::whereNotNull('batch')->count()));
    }

    public function test_discounts_do_not_stack_largest_wins(): void
    {
        $student = $this->student('998930000075', $this->group);
        $service = app(DiscountService::class);

        $this->actingAs($this->admin);
        $this->inCentre(fn () => $service->give($student, 10, $this->group->id, today()));
        $this->inCentre(fn () => $service->give($student, 50, null, today()));

        $this->assertSame(200000, $this->money($student)['payment']);
    }

    public function test_limited_discount_starts_and_ends_on_its_dates(): void
    {
        $student = $this->student('998930000076', $this->group);
        $service = app(DiscountService::class);
        $this->actingAs($this->admin);

        // Ertadan boshlanadi — bugun hali asl narx.
        $this->inCentre(fn () => $service->give($student, 25, null, today()->addDay(), today()->addDays(10)));
        $this->assertSame(400000, $this->money($student)['payment']);

        // Ertaga — chegirma qo'llanadi.
        $this->inCentre(fn () => $service->refreshDue(today()->addDay()));
        $this->assertSame(300000, $this->money($student)['payment']);

        // Tugash kunidan keyin — asl narxga qaytadi.
        $this->inCentre(fn () => $service->refreshDue(today()->addDays(11)));
        $this->assertSame(400000, $this->money($student)['payment']);
    }

    public function test_limited_discount_requires_end_date(): void
    {
        $student = $this->student('998930000077', $this->group);

        $this->actingAs($this->admin)
            ->post(route('discounts.student.store', $student->id), [
                'percent' => 20, 'period' => 'limited', 'starts_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('ends_on');
    }

    public function test_cancel_restores_base_price(): void
    {
        $student = $this->student('998930000078', $this->group);
        $this->actingAs($this->admin);
        $discount = $this->inCentre(fn () => app(DiscountService::class)->give($student, 20, null, today()));

        $this->actingAs($this->admin)
            ->post(route('discounts.cancel', $discount->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(400000, $this->money($student)['payment']);
        $this->assertNotNull($this->inCentre(fn () => $discount->fresh()->cancelled_at));
    }

    public function test_transfer_keeps_discount_without_applying_it_twice(): void
    {
        $student = $this->student('998930000079', $this->group);
        $this->actingAs($this->admin);
        $this->inCentre(fn () => app(DiscountService::class)->give($student, 20, null, today()));

        // Ikkinchi guruhga ham qo'shiladi (eskisi saqlanadi).
        $this->actingAs($this->admin)
            ->post(route('student.transfer', $student->id), [
                'group_id' => [$this->group->id, $this->other->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['payment' => 320000, 'base' => 400000, 'should_pay' => 720000, 'dept' => 720000], $this->money($student));
        $this->assertSame(400000, $this->money($student, $this->other)['payment'], '500 000 dan 20%');
    }

    public function test_editing_student_keeps_base_price_and_discount(): void
    {
        $student = $this->student('998930000080', $this->group);
        $this->actingAs($this->admin);
        $this->inCentre(fn () => app(DiscountService::class)->give($student, 20, null, today()));

        $this->actingAs($this->admin)
            ->get(route('student.edit', $student->id))
            ->assertOk()
            ->assertSee('"' . $this->group->id . '":400000', false);

        $this->actingAs($this->admin)
            ->put(route('student.update', $student->id), [
                'name'          => 'Yangi ism',
                'phone'         => '930000080',
                'group_id'      => [$this->group->id],
                'group_payment' => [$this->group->id => 400000],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['payment' => 320000, 'base' => 400000, 'should_pay' => 320000, 'dept' => 320000], $this->money($student));
    }
}
