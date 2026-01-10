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
        Schema::table('history_payments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type_of_money');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('history_payments', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
