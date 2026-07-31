<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Per-skill marks (reading / listening / writing / speaking) given during a
     * lesson.
     *
     * `lesson_id` is a real FK onto lesson_and_histories.id — the BIGINT primary
     * key, which is safe. It never joins through lesson_and_histories.group
     * (legacy, unindexed), which is why group_id is denormalised here with its
     * own FK.
     *
     * Skill grading may only ever attach to an EXISTING data = 1 lesson. Creating
     * a lesson row from the grading screen would inflate the attendance
     * denominator everywhere.
     *
     * `skill` is a short string rather than an ENUM so the allowed set can grow
     * via config('grading.skills') without another ->change() migration.
     */
    public function up()
    {
        Schema::create('lesson_skill_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->string('skill', 16);
            $table->unsignedTinyInteger('score')->nullable();   // 0..100, NULL = baholanmagan
            $table->string('comment', 255)->nullable();
            $table->unsignedBigInteger('graded_by');
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('lesson_and_histories')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['lesson_id', 'user_id', 'skill'], 'lesson_skill_grades_unique');
            $table->index(['group_id', 'created_at'], 'lsg_group_created_idx');
            $table->index(['user_id', 'skill', 'created_at'], 'lsg_user_skill_created_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_skill_grades');
    }
};
