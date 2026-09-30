<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('homeworks', 'skill')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            $table->string('skill')->nullable();
            $table->index(['centre_id', 'skill'], 'homeworks_centre_skill_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('homeworks', 'skill')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropIndex('homeworks_centre_skill_idx');
            $table->dropColumn('skill');
        });
    }
};
