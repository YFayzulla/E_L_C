<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 32)->nullable();
            $table->string('source')->nullable();
            $table->timestamp('test_taken_at')->nullable();
            $table->string('test_type')->nullable();
            $table->string('score')->nullable();
            $table->string('level')->nullable();
            $table->unsignedBigInteger('recommended_group_id')->nullable();
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('recommended_group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('registered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['centre_id', 'status'], 'reception_students_centre_status_idx');
            $table->index(['centre_id', 'level'], 'reception_students_centre_level_idx');
            $table->index(['centre_id', 'created_at'], 'reception_students_centre_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_students');
    }
};
