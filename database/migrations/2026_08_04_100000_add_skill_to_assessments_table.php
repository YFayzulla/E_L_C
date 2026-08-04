<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records WHICH skill a test mark measured.
 *
 * Until now the only clue was `for_what` — a free-text box labelled "Desc" on
 * the marking form. Two teachers would write "listening", "Listening test" and
 * "audirovaniye" for the same thing, so nothing could be reported on.
 *
 * The values are the same set `lesson_skill_grades.skill` uses
 * (config('grading.skills')), so both grading paths speak one vocabulary and a
 * skill added to the config appears in both.
 *
 * Nullable on purpose:
 *  - existing rows have no skill and must not be guessed at;
 *  - a test that is not about one skill (grammar, a mixed paper) records NULL,
 *    which the form offers explicitly as "Umumiy".
 *
 * `for_what` stays, demoted to a free-text note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('skill', 16)->nullable()->after('get_mark');

            // Reports read "this centre's listening marks over time".
            $table->index(['skill', 'created_at'], 'assessments_skill_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_skill_created_idx');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('skill');
        });
    }
};
