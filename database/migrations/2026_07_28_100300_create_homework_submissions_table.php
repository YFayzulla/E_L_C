<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * One row per (homework, student) carrying BOTH sides of the exchange:
     * what the student handed in, and how the teacher graded it.
     *
     * The unique index is the whole point — submitting and grading are upserts,
     * so re-saving either form is idempotent. That is exactly the bug class that
     * makes attendance uncorrectable today.
     */
    public function up()
    {
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('homework_id');
            $table->unsignedBigInteger('user_id');

            // --- student side -------------------------------------------------
            $table->text('submission_text')->nullable();
            $table->string('submission_file')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // --- teacher side -------------------------------------------------
            // 0 = topshirmagan, 1 = topshirgan, 2 = kech topshirgan
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedSmallInteger('score')->nullable();   // NULL = baholanmagan
            $table->string('comment', 500)->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            $table->foreign('homework_id')->references('id')->on('homeworks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('graded_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['homework_id', 'user_id'], 'homework_submissions_unique');
            $table->index(['user_id', 'created_at'], 'homework_submissions_user_created_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('homework_submissions');
    }
};
