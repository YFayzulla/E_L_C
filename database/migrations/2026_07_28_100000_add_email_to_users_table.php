<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds the e-mail address used for account verification.
     *
     * nullable() + unique() together are deliberate: MySQL permits unlimited
     * NULLs inside a UNIQUE index, which is what keeps every existing
     * phone-only account legal. Nothing is backfilled — `email_verified_at`
     * stays NULL everywhere.
     *
     * 191 rather than 255 so the index still fits on older utf8mb4 tables.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email', 191)->nullable()->unique()->after('phone');
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                // The User model already casts this to datetime; until now the
                // cast pointed at a column that did not exist.
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });
    }

    public function down()
    {
        // One dropColumn per Schema::table call: SQLite refuses multiple
        // dropColumn/renameColumn commands inside a single modification.
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};
