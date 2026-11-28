<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Olimpiada ishtirokchisi: maktab va sinf alohida maydonda
| (avval bitta "Maktab / sinf" katagi edi).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('olympiad_participants', function (Blueprint $table) {
            $table->string('grade', 32)->nullable()->after('school');
        });
    }

    public function down(): void
    {
        Schema::table('olympiad_participants', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
