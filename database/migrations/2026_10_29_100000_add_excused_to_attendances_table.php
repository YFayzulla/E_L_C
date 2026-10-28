<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Davomat: "kelmadi" endi ikki xil — sababli va sababsiz.
|
| `status` o'zgarmaydi (0 — kelmadi, 2 — kechikdi): davomat foizi, qarzdorlik,
| reception qo'ng'iroqlari va hisobotlar uni o'qiydi va ikkala holat ham dars
| qoldirilgan hisoblanadi. Farqni faqat shu ustun saqlaydi; kechikishda null.
|
| Eski yozuvlar: sabab yozilgan "kelmadi" — sababli, bo'sh — sababsiz. Shu
| paytgacha jurnallar ham aynan shunday ko'rsatib kelgan.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('excused')->nullable()->after('reason');
        });

        DB::table('attendances')->where('status', 0)->whereNotNull('reason')->where('reason', '!=', '')
            ->update(['excused' => true]);
        DB::table('attendances')->where('status', 0)->whereNull('excused')
            ->update(['excused' => false]);
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('excused');
        });
    }
};
