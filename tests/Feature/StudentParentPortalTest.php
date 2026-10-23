<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Centre;
use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\HistoryPayments;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Talaba va ota-ona kabineti: "Natijalarim" sahifasi, shu oyning to'lov
 * holati (tarixsiz) va qarz summasi.
 */
class StudentParentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private Group $group;
    private User $teacher;

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
            $this->group = Group::create(['name' => 'Pre Multilevel', 'monthly_payment' => 450000]);
            $this->teacher = $this->person('Teacher', '998900000010', 'user');
            GroupTeacher::create(['group_id' => $this->group->id, 'teacher_id' => $this->teacher->id]);
        });
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    /** @param int|null $status users.status — to'langan oylar hisoblagichi */
    private function student(?int $status, int $partial = 0): User
    {
        return Centre::for($this->centre, function () use ($status, $partial) {
            $student = $this->person('Abdrimova Munira', '99893' . random_int(1000000, 9999999), 'student');
            $student->forceFill(['status' => $status, 'should_pay' => 450000])->save();
            $student->groups()->attach($this->group->id, ['payment' => 450000]);
            DeptStudent::create(['user_id' => $student->id, 'dept' => 450000, 'payed' => $partial, 'status_month' => 0]);
            HistoryPayments::create([
                'user_id' => $student->id, 'name' => $student->name, 'payment' => 350000,
                'group' => 'Pre Multilevel', 'date' => '2026-06-04', 'type_of_money' => 'cash',
            ]);

            return $student->fresh();
        });
    }

    private function parentOf(User $student): User
    {
        return Centre::for($this->centre, function () use ($student) {
            $parent = $this->person('Yo‘ldoshev Abror', '998977916777', 'parent');
            $parent->children()->attach($student->id, ['relation' => 'ota']);

            return $parent;
        });
    }

    /* ================================================== qarz hisobi */

    public function test_current_debt_follows_paid_months_counter(): void
    {
        $debt = fn (User $s) => Centre::for($this->centre, fn () => $s->currentDebt());

        $this->assertSame(0, $debt($this->student(1)), 'To‘langan oy — qarz yo‘q');
        $this->assertSame(450000, $debt($this->student(0)), 'Shu oy to‘lanmagan');
        $this->assertSame(300000, $debt($this->student(0, 150000)), 'Qisman to‘langan');
        $this->assertSame(900000, $debt($this->student(-2)), 'Ikki oy qarz');
    }

    /* ========================================= talaba: Natijalarim */

    public function test_student_results_page_loads(): void
    {
        $student = $this->student(1);

        Centre::for($this->centre, function () use ($student) {
            $lesson = LessonAndHistory::create(['name' => 'Elementaryga o‘tish test', 'data' => 2, 'group' => $this->group->id]);
            Assessment::create(['user_id' => $student->id, 'get_mark' => 81, 'history_id' => $lesson->id]);
        });

        $this->actingAs($student)
            ->get(route('assessment.index'))
            ->assertOk()
            ->assertSee('Elementaryga o‘tish test', false)
            ->assertSee('81');
    }

    public function test_teacher_assessment_index_loads(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('assessment.index'))
            ->assertOk()
            ->assertSee('Pre Multilevel');
    }

    /* ==================================== talaba bosh sahifasi: to'lov */

    public function test_paid_student_dashboard_shows_no_debt_and_no_history(): void
    {
        $student = $this->student(1);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Qarz yo‘q', false)
            ->assertDontSee('Qarzdorlik')
            ->assertDontSee('350 000 so‘m', false);
    }

    public function test_unpaid_student_dashboard_shows_debt(): void
    {
        $this->actingAs($this->student(0, 150000))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('300 000 so‘m', false)
            ->assertDontSee('Qarz yo‘q', false);
    }

    /* =========================================== ota-ona: to'lovlar */

    public function test_parent_payments_show_status_without_history(): void
    {
        $paid = $this->student(1);
        $parent = $this->parentOf($paid);

        $this->actingAs($parent)
            ->get(route('parent.payments'))
            ->assertOk()
            ->assertSee('To‘langan', false)
            ->assertSee('Qarz yo‘q', false)
            ->assertDontSee('To‘lovlar tarixi', false)
            ->assertDontSee('350 000 so‘m', false);

        $this->actingAs($parent)->get(route('parent.index'))->assertOk()->assertSee('Qarz yo‘q', false);
        $this->actingAs($parent)->get(route('parent.child', $paid->id))->assertOk()->assertDontSee('350 000 so‘m', false);
    }

    public function test_parent_sees_debt_amount_when_unpaid(): void
    {
        $parent = $this->parentOf($this->student(0));

        $this->actingAs($parent)
            ->get(route('parent.payments'))
            ->assertOk()
            ->assertSee('Qarz')
            ->assertSee('450 000 so‘m', false)
            ->assertDontSee('Qarz yo‘q', false);
    }
}
