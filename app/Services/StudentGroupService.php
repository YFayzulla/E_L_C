<?php

namespace App\Services;

use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single correct writer for student group membership.
 *
 * Membership is not just a pivot row — it carries money and history:
 *
 *  - `group_user.payment` is summed by User::teacherPayment() to produce a
 *    teacher's salary, so a bare `sync([$id])` (no pivot payload) nulls the
 *    column for added rows and drops it for removed ones, silently shrinking
 *    somebody's wage.
 *  - `users.should_pay` and `dept_students.dept` drive every Qarzdorlik screen
 *    and must be recomputed from the new membership on the same transaction.
 *  - `student_information` is the audit trail rendered on the student card;
 *    it needs exactly one row per real change, joins and departures alike.
 *
 * What this service deliberately does NOT do: touch the `attendances` table.
 * A student's attendance history belongs to the group the lesson happened in.
 * The legacy GroupExtraController@change_group rewrites every attendance row
 * of the student to the first selected group, unscoped by date or old group —
 * that is irreversible data loss and is never reproduced here.
 */
class StudentGroupService
{
    /** student_information.action — talaba guruhga qo'shildi */
    public const ACTION_JOINED = 0;

    /** student_information.action — talaba guruhdan chiqdi */
    public const ACTION_LEFT = 1;

    /**
     * Make $targetGroupIds the student's complete membership.
     *
     * Anything currently attached and absent from the list is a departure, so
     * callers that must preserve a membership (a teacher moving a student who
     * also studies in another teacher's group) have to include it in the list.
     *
     * @param  array<int, int|string>            $targetGroupIds
     * @param  array<int|string, int|string|null> $payments  group id => monthly payment
     *
     * @throws \Throwable
     */
    public function transfer(User $student, array $targetGroupIds, array $payments = [], ?int $actorId = null): void
    {
        $targetIds = $this->normaliseIds($targetGroupIds);

        DB::beginTransaction();

        try {
            // a) before-state
            $before = $student->groups()
                ->pluck('groups.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $currentPayments = DB::table('group_user')
                ->where('user_id', $student->id)
                ->pluck('payment', 'group_id');

            // b) sync WITH the pivot payload
            $groups = Group::whereIn('id', $targetIds)->get();

            $syncData = [];
            $sum = 0;

            foreach ($groups as $group) {
                $submitted = $payments[$group->id] ?? null;

                $raw = filled($submitted)
                    ? $submitted
                    : ($currentPayments[$group->id] ?? $group->monthly_payment);

                $value = (int) str_replace([' ', ','], '', (string) $raw);

                $syncData[$group->id] = ['payment' => $value];
                $sum += $value;
            }

            $student->groups()->sync($syncData);

            // c) history: one row per real change, in both directions
            $after = array_keys($syncData);
            $joined = array_values(array_diff($after, $before));
            $left = array_values(array_diff($before, $after));

            $names = Group::whereIn('id', array_merge($joined, $left))->pluck('name', 'id');

            // `$actorId` shu paytgacha qabul qilinib, hech qayerga
            // yozilmasdan tashlab yuborilardi — tarixda "kim ko'chirdi"
            // degan savol javobsiz qolardi. Chaqiruvchi bermasa, joriy
            // foydalanuvchiga tushamiz; konsolda ikkalasi ham null bo'ladi.
            $actorId ??= auth()->id();

            foreach ($joined as $groupId) {
                StudentInformation::create([
                    'user_id' => $student->id,
                    'group_id' => $groupId,
                    'group' => $names[$groupId] ?? null,
                    'action' => self::ACTION_JOINED,
                    'actor_id' => $actorId,
                ]);
            }

            foreach ($left as $groupId) {
                StudentInformation::create([
                    'user_id' => $student->id,
                    'group_id' => $groupId,
                    'group' => $names[$groupId] ?? null,
                    'action' => self::ACTION_LEFT,
                    'actor_id' => $actorId,
                ]);
            }

            // d) money follows membership
            $student->update(['should_pay' => $sum]);

            $student->deptStudent()->updateOrCreate(
                ['user_id' => $student->id],
                ['dept' => $sum]
            );

            // e) attendances are intentionally left alone.

            DB::commit();

            $student->unsetRelation('groups');
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Group ids the student joins / leaves if $targetGroupIds is applied.
     * Read-only — used to build the confirmation message.
     *
     * @param  array<int, int|string>  $targetGroupIds
     * @return array{joined: array<int, int>, left: array<int, int>}
     */
    public function preview(User $student, array $targetGroupIds): array
    {
        $targetIds = $this->normaliseIds($targetGroupIds);

        $before = $student->groups()
            ->pluck('groups.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'joined' => array_values(array_diff($targetIds, $before)),
            'left' => array_values(array_diff($before, $targetIds)),
        ];
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normaliseIds(array $ids): array
    {
        $clean = array_map(
            fn ($id) => (int) $id,
            array_filter($ids, fn ($id) => filled($id) && is_numeric($id))
        );

        return array_values(array_unique($clean));
    }
}
