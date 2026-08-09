<?php

namespace App\Services;

use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the `parent` user accounts in sync with the guardian details typed on
 * the student form.
 *
 * A student may have TWO guardians — ota and ona — each with their own phone
 * number and e-mail address, plus an optional vasiy (guardian). The relationship
 * is stored on `parent_student.relation`; the account itself is an ordinary
 * `parent` user who logs in with their own phone number (initial password: the
 * same number, matching how student accounts are created).
 *
 * `users.parents_name` / `users.parents_tel` are kept mirroring the PRIMARY
 * guardian, because several older reports, exports and the SMS command still
 * read those two columns directly.
 */
class ParentAccountService
{
    /** Relations a guardian can have to a student, in priority order. */
    public const RELATIONS = ['ota', 'ona', 'vasiy'];

    public const LABELS = [
        'ota'   => 'Ota',
        'ona'   => 'Ona',
        'vasiy' => 'Vasiy',
    ];

    /**
     * Synchronise every guardian of a student in one pass.
     *
     * @param  array<string, array{name?: ?string, phone?: ?string, email?: ?string}>  $guardians
     *         keyed by relation: ['ota' => [...], 'ona' => [...]]
     * @return array<string, User>  the accounts that ended up linked, by relation
     */
    public function syncGuardians(User $student, array $guardians): array
    {
        $linked = [];
        $keepIds = [];

        foreach (self::RELATIONS as $relation) {
            $data  = $guardians[$relation] ?? null;
            $phone = User::normalizePhone($data['phone'] ?? null);

            if (! $phone) {
                continue;
            }

            $parent = $this->upsertAccount(
                $phone,
                $data['name'] ?? null,
                $data['email'] ?? null,
                $relation
            );

            if (! $parent) {
                continue;
            }

            $this->link($parent, $student, $relation);

            $linked[$relation] = $parent;
            $keepIds[] = $parent->id;
        }

        // Anyone previously attached but no longer on the form is unlinked.
        // The account itself is left alone — they may still parent other
        // students, and deleting it would cascade their whole history away.
        $stale = $student->guardians()
            ->when($keepIds, fn($q) => $q->whereNotIn('users.id', $keepIds))
            ->pluck('users.id');

        if ($stale->isNotEmpty()) {
            $student->guardians()->detach($stale->all());
        }

        $this->mirrorPrimary($student, $linked);

        return $linked;
    }

    /**
     * Single-guardian entry point, kept for the older callers (the student
     * import/backfill path and anything still passing parents_name/parents_tel).
     */
    public function syncForStudent(User $student, ?string $parentName, ?string $parentPhone, string $relation = 'ota'): ?User
    {
        $phone = User::normalizePhone($parentPhone);

        if (! $phone) {
            return null;
        }

        $parent = $this->upsertAccount($phone, $parentName, null, $relation);

        if (! $parent) {
            return null;
        }

        $this->link($parent, $student, $relation);

        return $parent;
    }

    /**
     * Attach a student to a parent without duplicating the row.
     */
    public function link(User $parent, User $student, ?string $relation = null): void
    {
        if ($parent->id === $student->id) {
            return;
        }

        $parent->children()->syncWithoutDetaching([
            $student->id => ['relation' => $relation],
        ]);
    }

    /**
     * Drop links to parents that no longer match the student's guardian phone.
     * Retained for the single-guardian callers.
     */
    public function pruneStaleLinks(User $student, ?string $currentParentPhone): void
    {
        $phone = User::normalizePhone($currentParentPhone);

        $stale = $student->guardians()
            ->when($phone, fn($q) => $q->where('users.phone', '!=', $phone))
            ->pluck('users.id');

        if ($stale->isNotEmpty()) {
            $student->guardians()->detach($stale->all());
        }
    }

    /**
     * The guardians of a student, keyed by relation, ready for a form.
     *
     * @return array<string, array{id: int, name: string, phone: ?string, email: ?string, verified: bool}>
     */
    public function guardiansOf(User $student): array
    {
        $out = [];

        foreach ($student->guardians()->get() as $guardian) {
            $relation = $guardian->pivot->relation ?: 'ota';

            // Two guardians sharing a relation should not overwrite each other.
            if (isset($out[$relation])) {
                $relation = collect(self::RELATIONS)->first(fn($r) => ! isset($out[$r])) ?? $relation;
            }

            $out[$relation] = [
                'id'       => (int) $guardian->id,
                'name'     => (string) $guardian->name,
                'phone'    => $guardian->phone,
                'email'    => $guardian->email,
                'verified' => $guardian->email && $guardian->email_verified_at !== null,
            ];
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Create the parent account, or update the one that already owns the phone.
     * Returns null when the number belongs to a non-parent account.
     */
    private function upsertAccount(string $phone, ?string $name, ?string $email, string $relation): ?User
    {
        $parent = User::where('phone', $phone)->first();

        if ($parent) {
            // An existing account with this number: only promote it to parent
            // if it is not already a student/teacher/admin.
            if ($parent->roles()->count() === 0) {
                app(CentreMembershipService::class)->attachToCurrent($parent, 'parent');
            }

            if (! $parent->hasRole('parent')) {
                Log::warning("ParentAccountService: phone {$phone} already belongs to user #{$parent->id} with another role; skipping link.");

                return null;
            }

            $dirty = false;

            if ($name && $parent->name !== $name) {
                $parent->name = $name;
                $dirty = true;
            }

            if ($email !== null && $email !== $parent->email) {
                // Changing the address invalidates any previous confirmation.
                $parent->email = $email ?: null;
                $parent->email_verified_at = null;
                $dirty = true;
            }

            if ($dirty) {
                $parent->save();
            }

            return $parent;
        }

        $parent = User::create([
            'name'     => $name ?: (self::LABELS[$relation] ?? 'Ota-ona') . ' ' . substr($phone, -4),
            'phone'    => $phone,
            'email'    => $email ?: null,
            'password' => Hash::make(substr($phone, -9)),
        ]);

        app(CentreMembershipService::class)->attachToCurrent($parent, 'parent');

        return $parent;
    }

    /**
     * Mirror the primary guardian onto users.parents_name / parents_tel.
     *
     * Ota first, then ona, then vasiy. Several older screens, the Excel export
     * and the SMS command read those two columns and would go blank otherwise.
     *
     * @param  array<string, User>  $linked
     */
    private function mirrorPrimary(User $student, array $linked): void
    {
        $primary = null;

        foreach (self::RELATIONS as $relation) {
            if (isset($linked[$relation])) {
                $primary = $linked[$relation];
                break;
            }
        }

        $name  = $primary?->name;
        $phone = $primary?->phone;

        if ($student->parents_name === $name && (string) $student->parents_tel === (string) $phone) {
            return;
        }

        $student->parents_name = $name;
        $student->parents_tel  = $phone;
        $student->save();
    }
}
