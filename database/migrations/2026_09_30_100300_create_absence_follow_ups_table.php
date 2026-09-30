<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('contacted_by')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('attendance_id')->references('id')->on('attendances')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->foreign('contacted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['centre_id', 'status'], 'absence_follow_ups_centre_status_idx');
            $table->index(['centre_id', 'contacted_at'], 'absence_follow_ups_centre_contacted_idx');
            $table->index('attendance_id', 'absence_follow_ups_attendance_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_follow_ups');
    }
};
