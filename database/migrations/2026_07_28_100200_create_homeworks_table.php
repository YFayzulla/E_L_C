<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A homework assignment given to a whole group.
     *
     * Deliberately NOT folded into `lesson_and_histories` as a third `data`
     * value: that table has only name/data/group to work with, and it is the
     * denominator of every attendance percentage in the app. Homework is not a
     * lesson — it has its own lifecycle, deadline and score scale.
     */
    public function up()
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            // Optional link to the lesson (data = 1) the homework was set in.
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();       // teacher's material
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->boolean('allow_file')->default(true);   // may students upload?
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('lesson_id')->references('id')->on('lesson_and_histories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['group_id', 'due_date'], 'homeworks_group_due_idx');
            $table->index('created_by', 'homeworks_author_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('homeworks');
    }
};
