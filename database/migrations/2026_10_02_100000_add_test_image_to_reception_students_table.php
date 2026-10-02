<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reception_students', function (Blueprint $table) {
            $table->string('test_image_path')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('reception_students', function (Blueprint $table) {
            $table->dropColumn('test_image_path');
        });
    }
};
