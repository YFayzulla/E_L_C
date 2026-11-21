<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Talaba chegirmalari (foizda, doimiy yoki muddatli).
|
| group_user.payment — talaba haqiqatan to'laydigan summa bo'lib qoladi:
| qarzdorlik, to'lov qabul qilish va o'qituvchi oyligi uni o'qiydi. Asl
| (chegirmasiz) narx endi alohida — base_payment. Chegirma qo'shilsa,
| bekor qilinsa yoki muddati boshlansa/tugasa, payment = base_payment dan
| qayta hisoblanadi (DiscountService).
|
| Mavjud qatorlarda base_payment = payment — hozir hech kimda chegirma yo'q.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->unsignedInteger('base_payment')->nullable()->after('payment');
        });

        DB::table('group_user')->update(['base_payment' => DB::raw('payment')]);

        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->unsignedBigInteger('user_id');
            // null — talabaning barcha guruhlariga.
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedTinyInteger('percent');
            $table->date('starts_on');
            // null — doimiy.
            $table->date('ends_on')->nullable();
            $table->string('reason')->nullable();
            // Guruhga birdaniga berilgan chegirmalar bitta belgi bilan.
            $table->string('batch', 36)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['centre_id', 'user_id'], 'student_discounts_user_idx');
            $table->index(['centre_id', 'batch'], 'student_discounts_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_discounts');

        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn('base_payment');
        });
    }
};
