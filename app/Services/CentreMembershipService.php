<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The only place that attaches people to centres and gives them roles.
 *
 * Roles must never be assigned outside this service. With Spatie's team feature
 * on, `assignRole()` writes `model_has_roles.centre_id = getPermissionsTeamId()`
 * — so a call made without a centre context either violates the NOT NULL
 * constraint or, worse, lands on whatever centre happened to be current. One
 * writer means one place to get that right.
 */
class CentreMembershipService
{
    /**
     * Attach a person to a centre with a role, or update the role they already
     * have there. Safe to call repeatedly.
     */
    public function attach(
        Centre $centre,
        User $user,
        ?string $role = null,
        array $attributes = []
    ): void {
        DB::transaction(function () use ($centre, $user, $role, $attributes) {
            $existing = $user->centres()->whereKey($centre->id)->exists();

            $payload = array_merge([
                'status'    => Centre::MEMBER_ACTIVE,
                'joined_at' => now(),
                'left_at'   => null,
            ], $attributes);

            if ($existing) {
                // Do not reset joined_at on a re-attach.
                unset($payload['joined_at']);
                $user->centres()->updateExistingPivot($centre->id, $payload);
            } else {
                $user->centres()->attach($centre->id, $payload);
            }

            // First membership becomes the default the picker opens on.
            if ($user->centres()->wherePivot('is_default', true)->doesntExist()) {
                $user->centres()->updateExistingPivot($centre->id, ['is_default' => true]);
            }

            if ($role !== null) {
                Centre::for($centre, function () use ($user, $role) {
                    $user->unsetRelation('roles');
                    $user->assignRole($role);
                });
            }
        });

        $user->unsetRelation('centres');
    }

    /**
     * Attach somebody to the centre the current request belongs to.
     *
     * Bu — ilova ichida yangi odam yaratadigan HAR BIR joy uchun yagona
     * to'g'ri chaqiruv. Yalang'och `assignRole()` rol beradi, lekin
     * `centre_user` qatorini YOZMAYDI — natijada odam yaratiladi, ro'yxatlarda
     * ko'rinadi, lekin kira olmaydi: EnsureCentreMember uni to'sadi.
     * Aynan shu sababdan ko'chishdan keyin yaratilgan talabalar 403 olardi.
     *
     * Markazsiz rol berishning IMKONI YO'Q: `model_has_roles.centre_id`
     * NOT NULL va birlamchi kalit tarkibida. Shuning uchun bu yerda
     * "markazsiz ham urinib ko'ramiz" degan zaxira yo'l yo'q — u baribir
     * tushunarsiz baza xatosiga olib borardi. Sabab aniq aytiladi:
     * chaqiruvchi markaz kontekstini o'rnatishi kerak
     * (Centre::for(...) yoki CentreContext::each(...)).
     */
    public function attachToCurrent(User $user, string $role, array $attributes = []): void
    {
        $centre = Centre::current();

        if ($centre === null) {
            throw new RuntimeException(
                "«{$role}» rolini berish uchun markaz konteksti kerak, lekin u o‘rnatilmagan. "
                . 'HTTP so‘rovda buni ResolveCentre qiladi; konsolda '
                . 'Centre::for($centre, fn() => ...) yoki Centre::each(...) ichida chaqiring.'
            );
        }

        $this->attach($centre, $user, $role, $attributes);
    }

    /**
     * Change somebody's role at a centre, dropping whatever they had before.
     */
    public function setRole(Centre $centre, User $user, string $role): void
    {
        Centre::for($centre, function () use ($user, $role) {
            $user->unsetRelation('roles');
            $user->syncRoles([$role]);
        });
    }

    /**
     * Suspend a membership without destroying its history.
     *
     * The roles go — they are what grants access — but the pivot row stays, so
     * "who used to work here" survives and re-attaching is one call.
     */
    public function suspend(Centre $centre, User $user): void
    {
        DB::transaction(function () use ($centre, $user) {
            $user->centres()->updateExistingPivot($centre->id, [
                'status'  => Centre::MEMBER_SUSPENDED,
                'left_at' => now(),
            ]);

            Centre::for($centre, function () use ($user) {
                $user->unsetRelation('roles');
                $user->syncRoles([]);
            });
        });

        $user->unsetRelation('centres');
    }

    /** The teacher's payout share at this centre. */
    public function setPercent(Centre $centre, User $user, ?int $percent): void
    {
        $user->centres()->updateExistingPivot($centre->id, ['percent' => $percent]);
        $user->unsetRelation('centres');
    }
}
