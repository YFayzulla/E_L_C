<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Lifecycle status for groups and students.
     *
     * IMPORTANT: this is NOT `users.status`. That column is the paid-months
     * counter — `php artisan user:status:update` decrements it every month and
     * a negative value means "owes N months". Overloading it would silently
     * corrupt every debt screen in the app, so enrolment state gets its own
     * column.
     *
     * groups.status       0 = faol,    1 = tugagan
     * users.study_status  0 = faol,    1 = bitirgan,  2 = to'xtatgan
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            if (! Schema::hasColumn('groups', 'status')) {
                $table->unsignedTinyInteger('status')->default(0)->after('name');
            }

            if (! Schema::hasColumn('groups', 'finished_at')) {
                $table->date('finished_at')->nullable()->after('status');
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->index('status', 'groups_status_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'study_status')) {
                $table->unsignedTinyInteger('study_status')->default(0)->after('status');
            }

            if (! Schema::hasColumn('users', 'graduated_at')) {
                $table->date('graduated_at')->nullable()->after('study_status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('study_status', 'users_study_status_idx');
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex('groups_status_idx');
        });

        // One dropColumn per call: SQLite refuses several in one modification.
        foreach (['finished_at', 'status'] as $column) {
            if (Schema::hasColumn('groups', $column)) {
                Schema::table('groups', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_study_status_idx');
        });

        foreach (['graduated_at', 'study_status'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
