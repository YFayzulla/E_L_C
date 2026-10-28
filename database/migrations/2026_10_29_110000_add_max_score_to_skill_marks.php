<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Ko'nikma bahosi va oylik test balli endi o'z maksimumi bilan saqlanadi.
|
| Grammatika testi 50 savollik bo'lsa, 50 dan 50 — 100% natija, 50% emas.
| Ball xom holida qoladi; `max_score` qaysi maksimumdan qo'yilganini
| yozib qo'yadi, shuning uchun markaz sozlamasi keyin o'zgarsa ham eski
| ballar qayta talqin qilinmaydi. Mavjud qatorlarga 100 yoziladi — ular
| avvalgidek o'qiladi.
|
| Ball ustunlari tinyint (255 gacha) edi; maksimum 1000 gacha bo'lishi
| mumkinligi uchun smallint ga kengaytiriladi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_skill_grades', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->nullable()->change();
            $table->unsignedSmallInteger('max_score')->default(100)->after('score');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedSmallInteger('get_mark')->change();
            $table->unsignedSmallInteger('max_score')->default(100)->after('get_mark');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_skill_grades', function (Blueprint $table) {
            $table->dropColumn('max_score');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('max_score');
        });
    }
};
