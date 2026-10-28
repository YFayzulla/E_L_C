<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\LessonSkillGrade;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ko'nikma bo'yicha maksimal ball: 50 dan 50 — 100%, 50% emas.
 *
 * Ball xom holda o'z maksimumi bilan saqlanadi; o'rtacha, rang va
 * o'zlashtirish foizda. Sozlama keyin o'zgarsa, eski ballar o'zgarmaydi.
 */
class SkillMaxScoreTest extends TestCase
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
            // Grammatika 50, O'qish va Tinglash 25 savollik.
            'settings' => ['skill_max' => ['grammar' => 50, 'reading' => 25, 'listening' => 25]],
        ])->save();

        Centre::for($this->centre, function () {
            $this->group = Group::create(['name' => '26-12 Beginner', 'monthly_payment' => 0]);
            $this->admin = $this->person('Admin', '998900000050', 'admin');
            $this->teacher = $this->person('Teacher', '998900000051', 'user');
            GroupTeacher::create(['group_id' => $this->group->id, 'teacher_id' => $this->teacher->id]);
            $this->student = $this->person('Yo‘ldosheva Asila', '998930000050', 'student');
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

    private function lesson(): LessonAndHistory
    {
        return Centre::for($this->centre, fn () => LessonAndHistory::create([
            'name' => 'Dars: 24.10.2026', 'data' => 1, 'group' => $this->group->id,
        ]));
    }

    private function gradeFullMarks(LessonAndHistory $lesson): void
    {
        $this->actingAs($this->teacher)
            ->post(route('skills.store', $this->group->id), [
                'lesson_id' => $lesson->id,
                'skills'    => ['grammar', 'reading', 'listening'],
                'score'     => [$this->student->id => ['grammar' => 50, 'reading' => 25, 'listening' => 25]],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_settings_page_saves_maximums(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'skill_mode' => 'skills',
                'skill_max'  => ['grammar' => 40, 'writing' => 20, 'reading' => ''],
            ])
            ->assertSessionHasNoErrors();

        $settings = $this->centre->fresh()->settings;
        $this->assertSame(['grammar' => 40, 'writing' => 20], $settings['skill_max']);

        $this->actingAs($this->admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('name="skill_max[grammar]"', false)
            ->assertSee('value="40"', false);
    }

    public function test_full_marks_out_of_fifty_are_one_hundred_percent(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->teacher)
            ->get(route('skills.grade', $this->group->id))
            ->assertOk()
            ->assertSee('name="max[grammar]"', false)
            ->assertSee('value="50"', false);

        $this->gradeFullMarks($lesson);

        $grammar = Centre::for($this->centre, fn () => LessonSkillGrade::where('skill', 'grammar')->first());
        $this->assertSame(50, $grammar->score);
        $this->assertSame(50, $grammar->max_score);
        $this->assertSame(100, $grammar->percent());

        // Hisobotda — 100, 50 emas.
        $html = $this->actingAs($this->teacher)
            ->get(route('skills.report', $this->group->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-score="100"', $html);
        $this->assertStringNotContainsString('data-score="50"', $html);
        $this->assertStringNotContainsString('data-score="25"', $html);
    }

    public function test_score_above_maximum_is_rejected(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->teacher)
            ->post(route('skills.store', $this->group->id), [
                'lesson_id' => $lesson->id,
                'skills'    => ['reading'],
                'score'     => [$this->student->id => ['reading' => 30]],
            ])
            ->assertSessionHasErrors('score.' . $this->student->id . '.reading');

        // O'qituvchi shu dars uchun maksimumni oshirsa — qabul qilinadi.
        $this->actingAs($this->teacher)
            ->post(route('skills.store', $this->group->id), [
                'lesson_id' => $lesson->id,
                'skills'    => ['reading'],
                'max'       => ['reading' => 40],
                'score'     => [$this->student->id => ['reading' => 30]],
            ])
            ->assertSessionHasNoErrors();

        $row = Centre::for($this->centre, fn () => LessonSkillGrade::where('skill', 'reading')->first());
        $this->assertSame(40, $row->max_score);
        $this->assertSame(75, $row->percent());
    }

    public function test_changing_settings_later_does_not_reinterpret_old_marks(): void
    {
        $this->gradeFullMarks($this->lesson());

        $this->centre->forceFill(['settings' => ['skill_max' => ['grammar' => 100]]])->save();
        Cache::flush();

        $grammar = Centre::for($this->centre, fn () => LessonSkillGrade::where('skill', 'grammar')->first());
        $this->assertSame(50, $grammar->max_score, 'Eski baho o‘z maksimumini saqlaydi');
        $this->assertSame(100, $grammar->percent());
    }

    public function test_monthly_test_uses_maximums_for_average_and_last_mark(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->group->id), [
                'student' => [$this->student->id],
                'skills'  => ['grammar', 'reading', 'listening'],
                'score'   => [$this->student->id => ['grammar' => 50, 'reading' => 25, 'listening' => 25]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(100, (int) $this->student->fresh()->mark, 'Oxirgi ball — 100%, 33 emas');

        $test = Centre::for($this->centre, fn () => Assessment::testsFor($this->student->id)->first());
        $this->assertSame(100, $test->average);
        $this->assertSame(100, $test->sum);
        $this->assertSame(100, $test->sum_max);
        $this->assertSame([50, 25, 25], array_column($test->marks, 'max'));

        $this->actingAs($this->admin)
            ->get(route('student.show', $this->student->id))
            ->assertOk()
            ->assertSee('50/50')
            ->assertSee('25/25');
    }

    public function test_progress_counts_skill_marks_as_percent(): void
    {
        $this->gradeFullMarks($this->lesson());

        $progress = Centre::for($this->centre, fn () => app(\App\Services\ProgressService::class)->forStudent($this->student->id));

        // Joriy oyning ko'nikma ulushi: 50/50, 25/25, 25/25 — 100, 33 emas.
        $this->assertSame(100, (int) ($progress['components']['skills'] ?? -1));
    }
}
