<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `history_payments.user_id` was created as `unsignedSmallInteger` with no
 * foreign key — it silently caps the installation at 65 535 users, and nothing
 * stops a row pointing at a person who no longer exists.
 *
 * Both matter more once several centres share one table: the id space is shared
 * even though the data is not.
 *
 * Orphans are pointed at NULL rather than deleted. A payment row already carries
 * the payer's name and the group name as plain strings, so the financial record
 * survives intact; only the broken pointer goes away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // Must run before the constraint, or the constraint is what reports it —
        // as an opaque driver error rather than a number you can act on.
        $orphans = DB::table('history_payments')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->select('id'))
            ->update(['user_id' => null]);

        if ($orphans > 0) {
            echo "  history_payments: {$orphans} ta yozuv mavjud bo‘lmagan "
                . "foydalanuvchiga ishora qilardi — user_id NULL ga o‘tkazildi "
                . "(to‘lov yozuvining o‘zi saqlanib qoldi).\n";
        }

        Schema::table('history_payments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // SQLite cannot drop a foreign key at all — the table has to be
        // rebuilt, which is exactly what the ->change() below makes Doctrine
        // do, and the rebuilt table is created without the constraint.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('history_payments', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        // Rows nulled on the way up cannot be restored, and any id above 65 535
        // would not fit — so this only narrows the type back when it is safe to.
        $tooBig = DB::table('history_payments')->where('user_id', '>', 65535)->count();

        if ($tooBig === 0) {
            Schema::table('history_payments', function (Blueprint $table) {
                $table->unsignedSmallInteger('user_id')->nullable()->change();
            });
        }
    }
};
