<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Olimpiada: talabalarni saralab olish.
|
| Ishtirokchi yangi odam (qo'lda yoki reception daftaridan) yoki markazda
| allaqachon o'qiyotgan talaba bo'lishi mumkin. Natija bo'limlar bo'yicha
| (har birining maksimal bali bilan) kiritiladi; o'rin foiz bo'yicha.
| Admin qabul qilinganlarga chegirma beradi va guruhga biriktiradi —
| chegirma student_discounts ga yoziladi (discount_id), talaba hisobi
| user_id ga.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olympiads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->string('name');
            $table->date('held_on')->nullable();
            $table->string('status', 16)->default('planned');
            // [{key, label, max}, ...] — bitta bo'lim ham bo'lishi mumkin.
            $table->json('sections');
            // [{from, to, percent}, ...] — o'rin bo'yicha tavsiya chegirma.
            $table->json('rules')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['centre_id', 'held_on'], 'olympiads_centre_date_idx');
        });

        Schema::create('olympiad_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->unsignedBigInteger('olympiad_id');
            // Mavjud talaba yoki guruhga biriktirilganda ochilgan hisob.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('reception_student_id')->nullable();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 32)->nullable();
            $table->string('school')->nullable();
            // {section_key: ball}
            $table->json('scores')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->unsignedTinyInteger('percent')->nullable();
            $table->unsignedInteger('rank')->nullable();
            // pending | accepted | reserve | rejected
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('olympiad_id')->references('id')->on('olympiads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reception_student_id')->references('id')->on('reception_students')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('discount_id')->references('id')->on('student_discounts')->nullOnDelete();
            $table->index(['centre_id', 'olympiad_id'], 'olympiad_participants_olympiad_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olympiad_participants');
        Schema::dropIfExists('olympiads');
    }
};
