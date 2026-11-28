<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\CefrScale;
use App\Services\CentreMembershipService;
use App\Services\ScoreScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Test o'rtachasi ball bo'yicha (47/50 + 18/25 + 21/25 → 86%, 83% emas)
 * va foizdan CEFR darajasi (chegaralari Sozlamalarda).
 */
class TestAverageAndCefrTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private User $teacher;
    private User $student;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['grading.skill_mode' => 'skills']);

        foreach (['admin', 'user', 'student'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill([
            'slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE,
            'settings' => ['skill_max' => ['grammar' => 50, 'reading' => 25, 'listening' => 25]],
        ])->save();

        Centre::for($this->centre, function () {
            $this->group = Group::create(['name' => '26-3 Pre Intermediate', 'monthly_payment' => 0]);
            $this->admin = $this->person('Admin', '998900000100', 'admin');
            $this->teacher = $this->person('Teacher', '998900000101', 'user');
            GroupTeacher::create(['group_id' => $this->group->id, 'teacher_id' => $this->teacher->id]);
            $this->student = $this->person('Otaboyeva Sevinch', '998930000100', 'student');
            $this->student->groups()->attach($this->group->id, ['payment' => 0]);
        });
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function submitTest(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->group->id), [
                'student' => [$this->student->id],
                'skills'  => ['grammar', 'reading', 'listening'],
                'score'   => [$this->student->id => ['grammar' => 47, 'reading' => 18, 'listening' => 21]],
                'lesson'  => 'Oylik test — Noyabr 2026',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_weighted_average_helper(): void
    {
        $this->assertSame(86, ScoreScale::weighted([
            ['mark' => 47, 'max' => 50], ['mark' => 18, 'max' => 25], ['mark' => 21, 'max' => 25],
        ]));
        $this->assertSame(50, ScoreScale::weighted([['mark' => 25, 'max' => 50], ['mark' => null, 'max' => 25]]), 'Bo‘sh katak hisobga kirmaydi');
        $this->assertNull(ScoreScale::weighted([]));
    }

    public function test_average_is_weighted_by_points_everywhere(): void
    {
        $this->submitTest();

        $this->assertSame(86, (int) $this->student->fresh()->mark, 'Oxirgi ball — 86, 83 emas');

        $test = Centre::for($this->centre, fn () => Assessment::testsFor($this->student->id)->first());
        $this->assertSame(86, $test->average);

        $history = Centre::for($this->centre, fn () => LessonAndHistory::latest('id')->first());

        $this->actingAs($this->admin)
            ->get(route('test.show', $history->id))
            ->assertOk()
            ->assertSee('86%')
            ->assertDontSee('83%')
            ->assertSee('86/100')
            ->assertSee('Daraja')
            ->assertSee('>B2<', false);

        $this->actingAs($this->admin)
            ->get(route('student.show', $this->student->id))
            ->assertOk()
            ->assertSee('86%')
            ->assertSee('>B2<', false);

        $progress = Centre::for($this->centre, fn () => app(\App\Services\ProgressService::class)->forStudent($this->student->id));
        $this->assertSame(86, (int) ($progress['components']['tests'] ?? -1));
    }

    public function test_ielts_mock_half_band_marks_are_accepted(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->group->id), [
                'student' => [$this->student->id],
                'skills'  => ['listening', 'reading'],
                'max'     => ['listening' => 9, 'reading' => 9],
                'score'   => [$this->student->id => ['listening' => '6.5', 'reading' => '7.5']],
                'lesson'  => 'IELTS Mock',
            ])
            ->assertSessionHasNoErrors();

        $test = Centre::for($this->centre, fn () => Assessment::testsFor($this->student->id)->first());
        $this->assertEqualsCanonicalizing([6.5, 7.5], array_column($test->marks, 'mark'));
        $this->assertSame(14, $test->sum, '6.5 + 7.5 = 14');
        $this->assertSame(18, $test->sum_max);
        $this->assertSame(78, $test->average, '14/18 = 78%');

        $this->actingAs($this->admin)
            ->get(route('student.show', $this->student->id))
            ->assertOk()
            ->assertSee('6.5/9')
            ->assertSee('7.5/9');

        // Maksimumdan oshgan kasr va noto'g'ri yozuv rad etiladi.
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->group->id), [
                'student' => [$this->student->id],
                'skills'  => ['listening'],
                'max'     => ['listening' => 9],
                'score'   => [$this->student->id => ['listening' => '9.5']],
            ])
            ->assertSessionHasErrors();

        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->group->id), [
                'student' => [$this->student->id],
                'skills'  => ['listening'],
                'max'     => ['listening' => 9],
                'score'   => [$this->student->id => ['listening' => '6.555']],
            ])
            ->assertSessionHasErrors();
    }

    public function test_admin_sets_cefr_bands_and_levels_follow(): void
    {
        $this->submitTest();

        $this->actingAs($this->admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('CEFR darajasi')
            ->assertSee('name="cefr_from[B2]"', false);

        // Noto'g'ri tartib rad etiladi.
        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'skill_mode' => 'skills',
                'cefr_from'  => ['A1' => 0, 'A2' => 50, 'B1' => 40],
            ])
            ->assertSessionHasErrors('cefr_from.B1');

        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'skill_mode' => 'skills',
                'cefr_from'  => ['A1' => 0, 'A2' => 30, 'B1' => 50, 'B2' => 70, 'C1' => 85, 'C2' => ''],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            ['level' => 'A1', 'from' => 0], ['level' => 'A2', 'from' => 30], ['level' => 'B1', 'from' => 50],
            ['level' => 'B2', 'from' => 70], ['level' => 'C1', 'from' => 85],
        ], $this->centre->fresh()->settings['cefr']);

        // 86% endi C1.
        $this->actingAs($this->admin)
            ->get(route('student.show', $this->student->id))
            ->assertOk()
            ->assertSee('>C1<', false);

        Centre::for($this->centre->fresh(), function () {
            $this->assertSame('B1', CefrScale::levelFor(55));
            $this->assertSame('A1', CefrScale::levelFor(0));
        });

        // Hammasi bo'sh — daraja ko'rsatilmaydi.
        $this->actingAs($this->admin)
            ->put(route('settings.update'), ['skill_mode' => 'skills', 'cefr_from' => ['A1' => '', 'B1' => '']])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->centre->fresh()->settings['cefr']);

        $this->actingAs($this->admin)
            ->get(route('student.show', $this->student->id))
            ->assertOk()
            ->assertDontSee('>Daraja<', false);
    }
}
