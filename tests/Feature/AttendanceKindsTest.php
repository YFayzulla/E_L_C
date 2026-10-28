<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Centre;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Yo'qlama: Keldi · Sababli · Sababsiz · Kechikdi. Sababli va sababsiz
 * ikkalasi ham "kelmadi" (status 0) — farq `excused` da; izoh uchalasida.
 */
class AttendanceKindsTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $teacher;
    private Group $group;
    /** @var array<string, User> */
    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'user', 'student', 'reception'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        Centre::for($this->centre, function () {
            $this->group = Group::create(['name' => '26-4 Pre Intermediate', 'monthly_payment' => 0]);
            $this->teacher = $this->person('Teacher', '998900000040', 'user');
            GroupTeacher::create(['group_id' => $this->group->id, 'teacher_id' => $this->teacher->id]);

            foreach (['present' => 41, 'excused' => 42, 'unexcused' => 43, 'late' => 44] as $key => $n) {
                $student = $this->person(ucfirst($key) . ' Talaba', '9989300000' . $n, 'student');
                $student->groups()->attach($this->group->id, ['payment' => 0]);
                $this->students[$key] = $student;
            }
        });
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function row(string $key): ?Attendance
    {
        return Centre::for($this->centre, fn () => Attendance::where('user_id', $this->students[$key]->id)->first());
    }

    public function test_four_kinds_are_saved_with_comments(): void
    {
        $s = $this->students;

        $this->actingAs($this->teacher)
            ->post(route('attendance.submit', $this->group->id), [
                'status' => [
                    $s['present']->id   => Attendance::FORM_PRESENT,
                    $s['excused']->id   => Attendance::FORM_EXCUSED,
                    $s['unexcused']->id => Attendance::FORM_UNEXCUSED,
                    $s['late']->id      => Attendance::FORM_LATE,
                ],
                'reason' => [
                    $s['present']->id   => 'e’tiborsiz qoladi',
                    $s['excused']->id   => 'Kasal, ma’lumotnoma bor',
                    $s['unexcused']->id => 'Telefon ko‘tarmadi',
                    $s['late']->id      => 'Avtobus kechikdi',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNull($this->row('present'));

        $excused = $this->row('excused');
        $this->assertSame(0, (int) $excused->status);
        $this->assertTrue($excused->excused);
        $this->assertSame('Kasal, ma’lumotnoma bor', $excused->reason);
        $this->assertSame('Sababli', $excused->kindLabel());

        $unexcused = $this->row('unexcused');
        $this->assertSame(0, (int) $unexcused->status);
        $this->assertFalse($unexcused->excused);
        $this->assertSame('Telefon ko‘tarmadi', $unexcused->reason, 'Sababsizda ham izoh yoziladi');
        $this->assertSame('Sababsiz', $unexcused->kindLabel());

        $late = $this->row('late');
        $this->assertSame(2, (int) $late->status);
        $this->assertNull($late->excused);
        $this->assertSame('Avtobus kechikdi', $late->reason, 'Kechikdida ham izoh yoziladi');

        // Qayta ochilganda tanlov saqlanib turadi.
        $html = $this->actingAs($this->teacher)
            ->get(route('attendance.check', $this->group->id))
            ->assertOk()
            ->assertSee('Sababli')
            ->assertSee('Sababsiz')
            ->assertSee('Avtobus kechikdi')
            ->getContent();

        $this->assertMatchesRegularExpression('/id="st-3-' . $s['excused']->id . '" value="3"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/id="st-0-' . $s['unexcused']->id . '" value="0"\s+checked/', $html);
    }

    public function test_switching_back_to_present_removes_the_row(): void
    {
        $id = $this->students['excused']->id;

        $this->actingAs($this->teacher)->post(route('attendance.submit', $this->group->id), [
            'status' => [$id => Attendance::FORM_EXCUSED],
        ]);
        $this->assertNotNull($this->row('excused'));

        $this->actingAs($this->teacher)->post(route('attendance.submit', $this->group->id), [
            'status' => [$id => Attendance::FORM_PRESENT],
        ]);
        $this->assertNull($this->row('excused'));
    }

    public function test_api_accepts_excused_and_keeps_legacy_status(): void
    {
        $s = $this->students;
        // Token markazga bog'langan bo'lishi kerak (EnsureTokenForCentre).
        Sanctum::actingAs($this->teacher, ['centre:' . $this->centre->id]);

        $this->postJson("/api/v1/teacher/groups/{$this->group->id}/attendance", [
            'statuses' => [$s['excused']->id => 3, $s['unexcused']->id => 0],
            'reasons'  => [$s['excused']->id => 'Kasal'],
        ])->assertOk();

        $this->assertTrue($this->row('excused')->excused);
        $this->assertFalse($this->row('unexcused')->excused);

        $sheet = collect($this->getJson("/api/v1/teacher/groups/{$this->group->id}/attendance")
            ->assertOk()
            ->json('data.students'))->keyBy('id');

        // Eski ilova uchun status 0 qoladi; yangi maydonlar farqni beradi.
        $this->assertSame(0, $sheet[$s['excused']->id]['status']);
        $this->assertTrue($sheet[$s['excused']->id]['excused']);
        $this->assertSame(3, $sheet[$s['excused']->id]['form_status']);
        $this->assertSame(0, $sheet[$s['unexcused']->id]['form_status']);
    }
}
