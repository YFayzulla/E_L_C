<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Kim ko'chirdi?" — `student_information` guruh a'zoligining tarixini
 * saqlaydi, lekin shu paytgacha faqat *nima* bo'lganini yozardi, *kim*
 * qilganini emas. O'qituvchi ham talabani guruhdan guruhga ko'chira
 * oladigan bo'lgach, bu javobsiz savolga aylandi.
 *
 * `StudentGroupService::transfer()` allaqachon `$actorId` ni parametr
 * sifatida qabul qilardi va uni hech qayerga yozmasdan tashlab yuborardi —
 * bu ustun o'sha bo'shliqni to'ldiradi.
 *
 * Nullable, chunki:
 *   - eski qatorlarda aktyor ma'lum emas va uni o'ylab topib bo'lmaydi;
 *   - konsol buyruqlari va seeder'lar autentifikatsiyasiz yozadi.
 *
 * O'chirishda NULL ga o'tadi (cascade EMAS): ishdan bo'shagan o'qituvchining
 * hisobi o'chirilsa ham, ko'chirish fakti tarixda qolishi kerak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_information', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->after('action');
            $table->index('actor_id');
        });

        // Cheklovdan oldin: aks holda buzuq ishora haqida tushunarli xabar
        // o'rniga drayverning o'zidan tushunarsiz xato keladi.
        Schema::table('student_information', function (Blueprint $table) {
            $table->foreign('actor_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // SQLite foreign key'ni umuman DROP qila olmaydi — jadvalni qayta
        // qurish kerak, ustunning o'zi tushirilganda Doctrine buni baribir
        // qiladi va qayta qurilgan jadval cheklovsiz chiqadi.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('student_information', function (Blueprint $table) {
                $table->dropForeign(['actor_id']);
            });
        }

        Schema::table('student_information', function (Blueprint $table) {
            $table->dropColumn('actor_id');
        });
    }
};
