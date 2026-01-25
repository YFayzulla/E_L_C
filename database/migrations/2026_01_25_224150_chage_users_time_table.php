<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('start_time')->nullable()->change();
            $table->string('finish_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->time('start_time')->nullable()->change();
            $table->time('finish_time')->nullable()->change();
        });
    }
};
