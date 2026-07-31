<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * `lesson_and_histories.group` holds a GROUP ID (not a name) — it is written
     * by TeacherAdminPanel@attendance_submit and AssessmentController@update, and
     * read by AttendanceService, AttendanceExport, User::attendanceRate() and the
     * teacher dashboard.
     *
     * It was created as TINYINT UNSIGNED, so it silently caps at 255: group #256
     * would roll the whole attendance transaction back in strict mode. Widen it,
     * and index it — the progress reports make this the hottest lookup in the app.
     *
     * The column is deliberately NOT renamed; too many readers depend on the name.
     * No foreign key either: historical rows may point at deleted groups.
     */
    public function up()
    {
        Schema::table('lesson_and_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('group')->nullable()->change();
        });

        if (! $this->hasIndex('lah_group_data_created_idx')) {
            Schema::table('lesson_and_histories', function (Blueprint $table) {
                $table->index(['group', 'data', 'created_at'], 'lah_group_data_created_idx');
            });
        }
    }

    public function down()
    {
        if ($this->hasIndex('lah_group_data_created_idx')) {
            Schema::table('lesson_and_histories', function (Blueprint $table) {
                $table->dropIndex('lah_group_data_created_idx');
            });
        }

        // Not narrowed back to TINYINT: existing ids may already exceed 255.
    }

    private function hasIndex(string $name): bool
    {
        return array_key_exists(
            $name,
            \Illuminate\Support\Facades\DB::connection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('lesson_and_histories')
        );
    }
};
