<?php

namespace Tests\Feature\Tenancy;

use App\Models\Centre;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Tenancy\CentreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pins the package behaviour the whole tenancy design rests on.
 *
 * Turning `permission.teams` on is what makes 49 existing `User::role(...)`
 * call sites and every `role:` middleware declaration centre-aware without a
 * single edit. If a package upgrade changed that, everything would still
 * compile and quietly serve the wrong centre's data — so it is asserted here
 * rather than assumed.
 */
class SpatieTeamSemanticsTest extends TestCase
{
    use RefreshDatabase;

    private Centre $alpha;
    private Centre $beta;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->alpha = $this->makeCentre('alpha');
        $this->beta  = $this->makeCentre('beta');
    }

    /**
     * The backfill migration already creates the installation's first centre,
     * so this has to be idempotent rather than assume an empty table.
     */
    private function makeCentre(string $slug): Centre
    {
        $centre = Centre::withoutGlobalScopes()->where('slug', $slug)->first() ?? new Centre();

        $centre->forceFill([
            'slug'   => $slug,
            'name'   => ucfirst($slug),
            'status' => Centre::STATUS_ACTIVE,
        ])->save();

        return $centre;
    }

    public function test_the_teams_feature_is_on_and_keyed_by_centre(): void
    {
        $this->assertTrue(config('permission.teams'));
        $this->assertSame('centre_id', config('permission.column_names.team_foreign_key'));
    }

    public function test_role_definitions_are_global_but_assignments_are_not(): void
    {
        $this->assertNull(
            Role::findByName('admin', 'web')->centre_id,
            'Rol ta’riflari global bo‘lishi kerak — faqat biriktirish markazga tegishli.'
        );
    }

    /** The headline case: one person, two centres, two different roles. */
    public function test_one_person_can_hold_different_roles_at_two_centres(): void
    {
        $user    = User::factory()->create();
        $service = app(CentreMembershipService::class);

        $service->attach($this->alpha, $user, 'admin');
        $service->attach($this->beta, $user, 'user');

        Centre::for($this->alpha, function () use ($user) {
            $user->unsetRelation('roles');
            $this->assertTrue($user->hasRole('admin'), 'Alpha da admin bo‘lishi kerak');
            $this->assertFalse($user->hasRole('user'), 'Alpha da o‘qituvchi BO‘LMASLIGI kerak');
        });

        Centre::for($this->beta, function () use ($user) {
            $user->unsetRelation('roles');
            $this->assertTrue($user->hasRole('user'), 'Beta da o‘qituvchi bo‘lishi kerak');
            $this->assertFalse($user->hasRole('admin'), 'Beta da admin BO‘LMASLIGI kerak');
        });
    }

    /** This is what makes the 49 untouched call sites correct. */
    public function test_the_role_scope_only_sees_the_current_centre(): void
    {
        $service = app(CentreMembershipService::class);

        $service->attach($this->alpha, User::factory()->create(), 'student');
        $service->attach($this->alpha, User::factory()->create(), 'student');
        $service->attach($this->beta, User::factory()->create(), 'student');

        $this->assertSame(2, Centre::for($this->alpha, fn() => User::role('student')->count()));
        $this->assertSame(1, Centre::for($this->beta, fn() => User::role('student')->count()));
    }

    /**
     * The dangerous half of the lever, and the reason every console command
     * has to iterate centres: with no team id, Spatie matches nothing.
     *
     * A command that forgot to would report success having touched nobody.
     */
    public function test_without_a_centre_context_nobody_has_any_role(): void
    {
        $user = User::factory()->create();
        app(CentreMembershipService::class)->attach($this->alpha, $user, 'student');

        app(CentreContext::class)->forget();

        $this->assertCount(0, $user->fresh()->roles);
        $this->assertSame(0, User::role('student')->count());
    }

    public function test_each_iterates_every_active_centre(): void
    {
        $service = app(CentreMembershipService::class);
        $service->attach($this->alpha, User::factory()->create(), 'student');
        $service->attach($this->beta, User::factory()->create(), 'student');
        $service->attach($this->beta, User::factory()->create(), 'student');

        $seen = [];

        Centre::each(function (Centre $centre) use (&$seen) {
            $seen[$centre->slug] = User::role('student')->count();
        });

        $this->assertSame(['alpha' => 1, 'beta' => 2], $seen);
    }

    /** A suspended centre is skipped by the sweep, not silently included. */
    public function test_each_skips_a_suspended_centre(): void
    {
        $this->beta->update(['status' => Centre::STATUS_SUSPENDED]);

        $seen = [];
        Centre::each(function (Centre $centre) use (&$seen) {
            $seen[] = $centre->slug;
        });

        $this->assertSame(['alpha'], $seen);
    }

    public function test_the_context_is_restored_after_for_and_after_a_throw(): void
    {
        Centre::for($this->alpha, function () {
            $this->assertSame($this->alpha->id, Centre::currentId());

            try {
                Centre::for($this->beta, fn() => throw new \RuntimeException('boom'));
            } catch (\RuntimeException) {
                // expected
            }

            $this->assertSame(
                $this->alpha->id,
                Centre::currentId(),
                'Xato tashlangandan keyin ham oldingi markaz tiklanishi kerak.'
            );
        });

        $this->assertNull(Centre::currentId());
    }
}
