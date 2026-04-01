<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('history_id')->nullable()->change();
            // get_mark odatda 100 gacha bo'ladi, lekin kelajakda oshishi mumkin bo'lsa:
            // $table->unsignedInteger('get_mark')->change(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('history_id')->nullable()->change();
        });
    }
};
