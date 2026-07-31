<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Graduation certificates.
     *
     * `file` is nullable on purpose: the centre can either upload a designed
     * certificate, or leave it empty and let the app render one from a template
     * with the existing dompdf. Either way the student downloads it from their
     * own profile.
     *
     * `serial` is unique so a certificate can be checked against the register.
     */
    public function up()
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('group_id')->nullable();

            $table->string('serial', 32)->unique();
            $table->string('title');
            $table->string('level')->nullable();        // B1, IELTS 6.5, ...
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->text('note')->nullable();

            $table->string('file')->nullable();         // uploaded, else generated
            $table->date('issued_at');
            $table->unsignedBigInteger('issued_by');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['user_id', 'issued_at'], 'certificates_user_issued_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificates');
    }
};
