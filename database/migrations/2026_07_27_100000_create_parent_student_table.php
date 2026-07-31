<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Links a parent account to the students it is allowed to follow.
     * A parent may have several children and a student may have two parents,
     * so this is a plain many-to-many.
     */
    public function up()
    {
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relation')->nullable(); // ota / ona / vasiy
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('parent_student');
    }
};
