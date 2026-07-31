<?php

use App\Tenancy\TenantTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 1 of 3: add the column, nullable, with no constraint.
 *
 * Split deliberately. On MySQL a nullable ADD COLUMN with no default is
 * instant and can run during business hours; the backfill and the NOT NULL
 * switch are the parts that need a window, and keeping them separate means a
 * failure in step 3 does not undo steps 1 and 2.
 *
 * `users` and `parent_student` are absent on purpose:
 *  - a person belongs to centres many-to-many, not to one;
 *  - "this person is the guardian of that person" is a fact about people, not
 *    about a centre. Scoping for guardians comes through the student list,
 *    which is already role-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (TenantTables::all() as $table) {
            if (Schema::hasColumn($table, 'centre_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('centre_id')->nullable();
                $blueprint->index('centre_id', $table . '_centre_idx');
            });
        }
    }

    public function down(): void
    {
        foreach (TenantTables::all() as $table) {
            if (! Schema::hasColumn($table, 'centre_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table . '_centre_idx');
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('centre_id');
            });
        }
    }
};
