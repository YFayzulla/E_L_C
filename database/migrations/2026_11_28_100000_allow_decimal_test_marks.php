<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Oylik test balli kasr bo'lishi mumkin: IELTS mock'da 6.5, 7.5 va h.k.
|
| `assessments.get_mark` butun son (smallint) edi — 6.5 saqlanmasdi.
| decimal(7,2) ga o'zgartiriladi: mavjud butun ballar o'zgarmaydi (47 → 47.00),
| maksimum 1000 gacha va ikki kasr xonasigacha sig'adi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Manfiy ball forma tekshiruvida to'xtatiladi (min:0).
            $table->decimal('get_mark', 7, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedSmallInteger('get_mark')->change();
        });
    }
};
