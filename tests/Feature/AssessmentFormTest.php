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
 * Oylik test formasi: tepadagi guruh tanlash va ustunlarni belgilash —
 * faqat belgilangan ko'nikmalar saqlanadi va o'rtachaga kiradi.
 */
class AssessmentFormTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $teacher;
    private Group $mine;
    private Group $second;
    private Group $theirs;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['grading.skill_mode' => 'skills']);

        foreach (['admin', 'user', 'student'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        Centre::for($this->centre, function () {
            $this->mine = Group::create(['name' => '26-4 Pre Intermediate', 'monthly_payment' => 0]);
            $this->second = Group::create(['name' => '26-7 Elementary', 'monthly_payment' => 0]);
            $this->theirs = Group::create(['name' => 'Begona guruh', 'monthly_payment' => 0]);

            $this->teacher = $this->person('Teacher', '998900000020', 'user');
            $colleague = $this->person('Colleague', '998900000021', 'user');
            GroupTeacher::create(['group_id' => $this->mine->id, 'teacher_id' => $this->teacher->id]);
            GroupTeacher::create(['group_id' => $this->second->id, 'teacher_id' => $this->teacher->id]);
            GroupTeacher::create(['group_id' => $this->theirs->id, 'teacher_id' => $colleague->id]);

            $this->student = $this->person('Abdullayeva Aziza', '998930000020', 'student');
            $this->student->groups()->attach($this->mine->id, ['payment' => 0]);
        });
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function marks(): array
    {
        return Centre::for($this->centre, fn () => Assessment::where('user_id', $this->student->id)
            ->orderBy('skill')
            ->pluck('get_mark', 'skill')
            ->map(fn ($mark) => (int) $mark)
            ->all());
    }

    public function test_form_offers_only_the_teachers_groups(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('assessment.show', $this->mine->id))
            ->assertOk()
            ->assertSee('id="groupSwitch"', false)
            ->assertSee(route('assessment.show', $this->second->id), false)
            ->assertDontSee(route('assessment.show', $this->theirs->id), false)
            ->assertSee('name="skills[]"', false)
            ->assertSee('Yig‘indi', false);
    }

    public function test_only_selected_skills_are_saved_and_averaged(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->mine->id), [
                'lesson'  => 'Oylik test — Oktabr 2026',
                'student' => [$this->student->id],
                // Faqat Grammatika va Tinglash topshirildi.
                'skills'  => ['grammar', 'listening'],
                'score'   => [$this->student->id => [
                    'grammar'   => 70,
                    'listening' => 90,
                    // Belgilanmagan ustun — qo'lda yuborilsa ham saqlanmaydi.
                    'writing'   => 10,
                ]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('assessment.index'));

        $this->assertSame(['grammar' => 70, 'listening' => 90], $this->marks());
        $this->assertSame(80, (int) $this->student->fresh()->mark, 'Oxirgi ball — belgilanganlar o‘rtachasi');
    }

    public function test_writing_only_test(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->mine->id), [
                'student' => [$this->student->id],
                'skills'  => ['writing'],
                'score'   => [$this->student->id => ['writing' => 65]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['writing' => 65], $this->marks());
        $this->assertSame(65, (int) $this->student->fresh()->mark);
    }

    /* ===================================== Ko'nikmalar sahifasi ham shunday */

    private function lesson(): LessonAndHistory
    {
        return Centre::for($this->centre, fn () => LessonAndHistory::create([
            'name' => 'Dars: 24.10.2026', 'data' => 1, 'group' => $this->mine->id,
        ]));
    }

    private function skillMarks(int $lessonId): array
    {
        return Centre::for($this->centre, fn () => LessonSkillGrade::where('lesson_id', $lessonId)
            ->where('user_id', $this->student->id)
            ->orderBy('skill')
            ->pluck('score', 'skill')
            ->map(fn ($score) => (int) $score)
            ->all());
    }

    public function test_skills_page_saves_only_selected_columns(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->teacher)
            ->get(route('skills.grade', $this->mine->id))
            ->assertOk()
            ->assertSee('name="skills[]"', false)
            ->assertSee('Yig‘indi', false);

        $this->actingAs($this->teacher)
            ->post(route('skills.store', $this->mine->id), [
                'lesson_id' => $lesson->id,
                'skills'    => ['reading', 'writing'],
                'score'     => [$this->student->id => ['reading' => 70, 'writing' => 90, 'speaking' => 5]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['reading' => 70, 'writing' => 90], $this->skillMarks($lesson->id));

        // Qayta ochilganda faqat baholangan ustunlar belgilangan.
        $html = $this->actingAs($this->teacher)
            ->get(route('skills.grade', ['group' => $this->mine->id, 'lesson' => $lesson->id]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="reading"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="speaking"\s+checked/', $html);
    }

    public function test_skills_page_requires_a_selected_column(): void
    {
        $lesson = $this->lesson();

        $this->actingAs($this->teacher)
            ->post(route('skills.store', $this->mine->id), [
                'lesson_id' => $lesson->id,
                'score'     => [$this->student->id => ['reading' => 70]],
            ])
            ->assertSessionHasErrors('skills');

        $this->assertSame([], $this->skillMarks($lesson->id));
    }

    public function test_at_least_one_skill_must_be_selected(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('assessment.update', $this->mine->id), [
                'student' => [$this->student->id],
                'score'   => [$this->student->id => ['writing' => 65]],
            ])
            ->assertSessionHasErrors('skills');

        $this->assertSame([], $this->marks());
    }
}
