<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Makes attendance re-submittable.
     *
     * Today a second submit for the same day creates a second lesson row plus a
     * second set of attendance rows, and flipping Absent -> Present writes
     * nothing at all, so the stale Absent row survives and the cell stays wrong.
     * The unique index below makes that regression impossible at the DB level;
     * the controller change (updateOrCreate / delete-on-present) does the rest.
     *
     * !! Run this on a restore of production first — it DELETES duplicate rows,
     *    keeping the newest per (user_id, lesson_id). The count is logged.
     */
    public function up()
    {
        $removed = $this->dedupe();

        if ($removed > 0) {
            Log::warning("attendances dedupe: removed {$removed} duplicate row(s) before adding the unique index.");
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! $this->hasIndex('attendances_user_lesson_unique')) {
                $table->unique(['user_id', 'lesson_id'], 'attendances_user_lesson_unique');
            }
            if (! $this->hasIndex('attendances_group_created_idx')) {
                $table->index(['group_id', 'created_at'], 'attendances_group_created_idx');
            }
            if (! $this->hasIndex('attendances_user_created_idx')) {
                $table->index(['user_id', 'created_at'], 'attendances_user_created_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach ([
                'attendances_user_lesson_unique',
                'attendances_group_created_idx',
                'attendances_user_created_idx',
            ] as $name) {
                if ($this->hasIndex($name)) {
                    $name === 'attendances_user_lesson_unique'
                        ? $table->dropUnique($name)
                        : $table->dropIndex($name);
                }
            }
        });
    }

    /**
     * Keep the highest id per (user_id, lesson_id). Written with the query
     * builder rather than a MySQL `DELETE ... JOIN` so it also runs on SQLite.
     */
    private function dedupe(): int
    {
        $groups = DB::table('attendances')
            ->select('user_id', 'lesson_id', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('lesson_id')
            ->groupBy('user_id', 'lesson_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $removed = 0;

        foreach ($groups as $row) {
            $removed += DB::table('attendances')
                ->where('user_id', $row->user_id)
                ->where('lesson_id', $row->lesson_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        return $removed;
    }

    private function hasIndex(string $name): bool
    {
        return array_key_exists(
            $name,
            DB::connection()->getDoctrineSchemaManager()->listTableIndexes('attendances')
        );
    }
};
