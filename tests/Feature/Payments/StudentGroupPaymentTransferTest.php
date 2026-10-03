<?php

namespace Tests\Feature\Payments;

use App\Models\Centre;
use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Services\StudentGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentGroupPaymentTransferTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'payment-transfer')->first()
            ?? tap(new Centre(), function (Centre $centre) {
                $centre->forceFill([
                    'slug' => 'payment-transfer',
                    'name' => 'Payment Transfer',
                    'status' => Centre::STATUS_ACTIVE,
                ])->save();
            });
    }

    public function test_paid_student_stays_paid_after_moving_to_another_group(): void
    {
        Centre::for($this->centre, function () {
            $oldGroup = Group::create(['name' => 'Pre IELTS', 'monthly_payment' => 550000]);
            $newGroup = Group::create(['name' => 'Level 2', 'monthly_payment' => 550000]);
            $student = $this->student(['status' => 1, 'should_pay' => 550000]);

            $oldGroup->students()->attach($student->id, ['payment' => 550000]);
            DeptStudent::create([
                'user_id' => $student->id,
                'dept' => 550000,
                'payed' => 0,
                'status_month' => 1,
            ]);

            app(StudentGroupService::class)->transfer($student->fresh(), [$newGroup->id], [
                $newGroup->id => 550000,
            ]);

            $student->refresh();
            $dept = $student->deptStudent()->firstOrFail();

            $this->assertSame(1, (int) $student->status);
            $this->assertSame(550000, (int) $student->should_pay);
            $this->assertSame(550000, (int) $dept->dept);
            $this->assertSame(0, (int) $dept->payed);
            $this->assertSame(1, (int) $dept->status_month);
            $this->assertSame([$newGroup->id], $student->groups()->pluck('groups.id')->all());
        });
    }

    public function test_partial_payment_is_preserved_when_monthly_payment_changes(): void
    {
        Centre::for($this->centre, function () {
            $oldGroup = Group::create(['name' => 'Old Group', 'monthly_payment' => 600000]);
            $newGroup = Group::create(['name' => 'New Group', 'monthly_payment' => 400000]);
            $student = $this->student(['status' => 0, 'should_pay' => 600000]);

            $oldGroup->students()->attach($student->id, ['payment' => 600000]);
            DeptStudent::create([
                'user_id' => $student->id,
                'dept' => 600000,
                'payed' => 500000,
                'status_month' => 0,
            ]);

            app(StudentGroupService::class)->transfer($student->fresh(), [$newGroup->id], [
                $newGroup->id => 400000,
            ]);

            $student->refresh();
            $dept = $student->deptStudent()->firstOrFail();

            $this->assertSame(1, (int) $student->status);
            $this->assertSame(400000, (int) $student->should_pay);
            $this->assertSame(400000, (int) $dept->dept);
            $this->assertSame(100000, (int) $dept->payed);
            $this->assertSame(1, (int) $dept->status_month);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function student(array $attributes = []): User
    {
        $student = User::factory()->create($attributes);

        app(CentreMembershipService::class)->attachToCurrent($student, 'student');

        return $student;
    }
}
