<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Centre;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin "Oylik test" natijalari: bitta talaba — bitta qator, ko'nikmalar
 * ustunlarda; belgilab o'chirish talabaning shu testdagi hamma ballini oladi.
 */
class TestResultsPageTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private LessonAndHistory $history;
    private User $first;
    private User $second;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'student'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        Centre::for($this->centre, function () {
            $group = Group::create(['name' => '26-4 Pre Intermediate', 'monthly_payment' => 0]);
            $this->admin = $this->person('Admin', '998900000030', 'admin');
            $this->first = $this->person('Abduraxmonova Havojon', '998930000031', 'student');
            $this->second = $this->person('Boynazarova Marjona', '998930000032', 'student');

            $this->history = LessonAndHistory::create(['name' => 'Oylik test — Oktabr 2026', 'data' => 2, 'group' => $group->id]);

            $marks = [
                $this->first->id  => ['grammar' => 50, 'reading' => 41, 'listening' => 48, 'writing' => 53, 'speaking' => 55],
                // Ikkinchisi faqat uch yo'nalishdan topshirgan.
                $this->second->id => ['grammar' => 70, 'listening' => 80, 'writing' => 90],
            ];

            foreach ($marks as $userId => $skills) {
                foreach ($skills as $skill => $mark) {
                    Assessment::create([
                        'user_id' => $userId, 'get_mark' => $mark, 'skill' => $skill,
                        'group' => $group->name, 'history_id' => $this->history->id,
                    ]);
                }
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

    public function test_one_row_per_student_with_skill_columns(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('test.show', $this->history->id))
            ->assertOk()
            ->assertSee('Grammatika')
            ->assertSee('O‘qish', false)
            ->assertSee('Gapirish')
            ->assertSee('O‘rtacha', false)
            ->getContent();

        // Har bir qatorda bitta belgi (checkbox) — talabalar soni bilan teng.
        $this->assertSame(2, substr_count($html, 'name="selectedItems[]"'), 'Bitta talaba — bitta qator');
        $this->assertSame(1, substr_count($html, '>Abduraxmonova Havojon<'));
        $this->assertSame(1, substr_count($html, '>Boynazarova Marjona<'));
        // O'rtachalar: (50+41+48+53+55)/5 = 49, (70+80+90)/3 = 80; 80 tepada.
        $this->assertLessThan(strpos($html, 'Abduraxmonova Havojon'), strpos($html, 'Boynazarova Marjona'));
        $this->assertStringContainsString('>49<', $html);
        $this->assertStringContainsString('>80<', $html);
    }

    public function test_test_list_shows_group_name(): void
    {
        Centre::for($this->centre, function () {
            $other = Group::create(['name' => '26-7 Elementary', 'monthly_payment' => 0]);
            LessonAndHistory::create(['name' => 'Oylik test — Oktabr 2026', 'data' => 2, 'group' => $other->id]);
        });

        $this->actingAs($this->admin)
            ->get(route('test'))
            ->assertOk()
            ->assertSee('26-4 Pre Intermediate')
            ->assertSee('26-7 Elementary');
    }

    public function test_selecting_a_row_deletes_all_its_marks(): void
    {
        $ids = Centre::for($this->centre, fn () => Assessment::where('user_id', $this->second->id)->pluck('id')->implode(','));

        $this->actingAs($this->admin)
            ->delete(route('deleteMultiple'), ['selectedItems' => [$ids]])
            ->assertSessionHasNoErrors();

        Centre::for($this->centre, function () {
            $this->assertSame(0, Assessment::where('user_id', $this->second->id)->count());
            $this->assertSame(5, Assessment::where('user_id', $this->first->id)->count());
        });
    }

    public function test_report_downloads_pdf(): void
    {
        $this->actingAs($this->admin)
            ->get(route('test.report', $this->history->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
