<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Indexes the progress reports lean on.
     *
     * `assessments.group` is a NAME string and stays unindexed on purpose — no
     * new query should join on it.
     */
    public function up()
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! $this->hasIndex('assessments', 'assessments_history_idx')) {
                $table->index('history_id', 'assessments_history_idx');
            }
            if (! $this->hasIndex('assessments', 'assessments_user_created_idx')) {
                $table->index(['user_id', 'created_at'], 'assessments_user_created_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('assessments', function (Blueprint $table) {
            foreach (['assessments_history_idx', 'assessments_user_created_idx'] as $name) {
                if ($this->hasIndex('assessments', $name)) {
                    $table->dropIndex($name);
                }
            }
        });
    }

    private function hasIndex(string $table, string $name): bool
    {
        return array_key_exists(
            $name,
            DB::connection()->getDoctrineSchemaManager()->listTableIndexes($table)
        );
    }
};
