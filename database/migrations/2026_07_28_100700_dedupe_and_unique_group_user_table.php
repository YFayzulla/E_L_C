<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `group_user` has no unique index, so attach() can double-enroll a student.
     * That double-counts Group::students()->count() and inflates the teacher's
     * payout via User::teacherPayment(), which sums group_user.payment.
     *
     * Required before the student-transfer feature ships.
     * Keeps the row with the highest payment (then the newest id).
     */
    public function up()
    {
        $removed = $this->dedupe();

        if ($removed > 0) {
            Log::warning("group_user dedupe: removed {$removed} duplicate enrolment(s) before adding the unique index.");
        }

        if (! $this->hasIndex('group_user_unique')) {
            Schema::table('group_user', function (Blueprint $table) {
                $table->unique(['user_id', 'group_id'], 'group_user_unique');
            });
        }
    }

    public function down()
    {
        if ($this->hasIndex('group_user_unique')) {
            Schema::table('group_user', function (Blueprint $table) {
                $table->dropUnique('group_user_unique');
            });
        }
    }

    private function dedupe(): int
    {
        $groups = DB::table('group_user')
            ->select('user_id', 'group_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id', 'group_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $removed = 0;

        foreach ($groups as $row) {
            // Prefer the row that actually carries a payment, then the newest.
            $keep = DB::table('group_user')
                ->where('user_id', $row->user_id)
                ->where('group_id', $row->group_id)
                ->orderByRaw('payment IS NULL')
                ->orderByDesc('payment')
                ->orderByDesc('id')
                ->value('id');

            $removed += DB::table('group_user')
                ->where('user_id', $row->user_id)
                ->where('group_id', $row->group_id)
                ->where('id', '!=', $keep)
                ->delete();
        }

        return $removed;
    }

    private function hasIndex(string $name): bool
    {
        return array_key_exists(
            $name,
            DB::connection()->getDoctrineSchemaManager()->listTableIndexes('group_user')
        );
    }
};
