<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform owner: creates and suspends centres, and is not an admin of any
 * centre's data.
 *
 * A column rather than a Spatie role, because Spatie cannot express one. With
 * teams switched on, `model_has_roles.team_foreign_key` is NOT NULL *and* part
 * of the composite primary key (see 2023_11_27_185451_create_permission_tables),
 * so there is no such thing as a role assignment that belongs to no centre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
