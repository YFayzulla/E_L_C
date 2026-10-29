<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parent and guardian contacts are optional and may be shared by several
     * people. Student-phone uniqueness remains enforced by its request rules.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_phone_unique');
            $table->dropUnique('users_parents_tel_unique');
        });
    }

    /**
     * This cannot safely restore the unique indexes because production may
     * already contain shared guardian phone numbers.
     */
    public function down(): void
    {
        // Intentionally irreversible after shared guardian data is saved.
    }
};
