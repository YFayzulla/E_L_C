<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Ota-ona yozuvi va uning tizimga kirish huquqi endi alohida.
|
| Talaba formasida ota/ona ismi va raqami kiritilsa, yozuv har doim saqlanadi
| (SMS, jurnallar, profil uchun). Lekin tizimga kirish uchun hisob faqat
| "Hisob ochilsin" belgilansa beriladi — `login_enabled`.
|
| Mavjud hisoblarning hammasi true: hozir kira oladiganlar kiraverishadi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('login_enabled')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_enabled');
        });
    }
};
