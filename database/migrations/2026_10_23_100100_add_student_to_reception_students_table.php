<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Reception yozuvi endi haqiqiy talaba hisobiga aylana oladi. `student_id` —
| shu yozuvdan ochilgan `users` qatori; to'ldirilgan bo'lsa yozuv qayta
| guruhga yozilmaydi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reception_students', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('recommended_group_id');
            $table->timestamp('enrolled_at')->nullable()->after('assigned_at');

            $table->foreign('student_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reception_students', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'enrolled_at']);
        });
    }
};
