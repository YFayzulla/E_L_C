<?php

use App\Tenancy\TenantTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 3 of 3: make the column mandatory, constrain it, and rebuild the two
 * unique indexes that would otherwise make two centres fight over one value.
 *
 * This is the only one of the three that can fail on dirty data, and it is the
 * one that needs a maintenance window. It is also the point of no return —
 * the way back is a database restore.
 *
 * PORTABILITY: SQLite cannot ALTER TABLE ADD CONSTRAINT, so Laravel's SQLite
 * grammar silently skips the foreign keys below. Production is MySQL, where
 * they are real; the test suite runs on SQLite, where `centres:audit` is what
 * proves nothing has drifted. NOT NULL and the indexes DO apply on both.
 */
return new class extends Migration
{
    /**
     * Uniques that are installation-wide today and must become per-centre.
     *
     * table => [index name, columns]
     */
    private const UNIQUES = [
        'certificates'  => ['certificates_serial_unique',  ['serial']],
        'sms_templates' => ['sms_templates_slug_unique',   ['slug']],
    ];

    public function up(): void
    {
        $before = [];

        // Refuse to constrain a column that still has holes, and say how many.
        foreach (TenantTables::all() as $table) {
            $before[$table] = DB::table($table)->count();

            $orphans = DB::table($table)->whereNull('centre_id')->count();

            if ($orphans > 0) {
                throw new RuntimeException(
                    "{$table}: {$orphans} ta qatorda centre_id bo‘sh. "
                    . 'Avval 2026_08_01_130100_backfill_centre_id ni ishga tushiring.'
                );
            }
        }

        /*
         * SQLite has no ALTER COLUMN: Doctrine emulates ->change() by creating
         * a new table, copying the rows, DROPping the original and renaming.
         * With foreign keys enforced, that DROP cascades — rebuilding `groups`
         * silently emptied group_user, group_teachers, attendances, homeworks
         * and lesson_skill_grades. Found by a route sweep after the fact, which
         * is exactly why the runbook says to rehearse on a production restore.
         *
         * MySQL alters in place and is unaffected, but the guard is cheap and
         * the test suite runs on SQLite.
         */
        Schema::disableForeignKeyConstraints();

        foreach (TenantTables::all() as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('centre_id')->nullable(false)->change();
            });

            // SQLite rebuilds the table for a ->change(), which drops the index
            // created in step 1; put it back before adding the constraint.
            $this->ensureIndex($table);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('centre_id')
                    ->references('id')->on('centres')
                    ->cascadeOnDelete();
            });
        }

        // ------------------------------------------------- per-centre uniques
        foreach (self::UNIQUES as $table => [$index, $columns]) {
            $this->dropIndexIfExists($table, $index);

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $table) {
                $blueprint->unique(
                    array_merge(['centre_id'], $columns),
                    $table . '_centre_' . implode('_', $columns) . '_unique'
                );
            });
        }

        // Reporting queries all filter by centre and then by date; a bare
        // centre_id index would still scan a year of one centre's rows.
        $this->compositeIndex('history_payments', ['centre_id', 'created_at'], 'history_payments_centre_created_idx');
        $this->compositeIndex('finances', ['centre_id', 'created_at'], 'finances_centre_created_idx');
        $this->compositeIndex('attendances', ['centre_id', 'created_at'], 'attendances_centre_created_idx');
        $this->compositeIndex('lesson_and_histories', ['centre_id', 'data', 'created_at'], 'lah_centre_data_created_idx');

        Schema::enableForeignKeyConstraints();

        // Prove the rebuild kept everything. A silent cascade is the failure
        // mode this migration is most likely to have, so it checks itself.
        foreach (TenantTables::all() as $table) {
            $after = DB::table($table)->count();

            if ($after !== $before[$table]) {
                throw new RuntimeException(
                    "{$table}: migratsiyadan oldin {$before[$table]} ta qator bor edi, "
                    . "keyin {$after} ta qoldi. Bazani zaxiradan tiklang."
                );
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Bu migratsiya orqaga qaytarilmaydi — bazani zaxiradan tiklang.'
        );
    }

    private function ensureIndex(string $table): void
    {
        $name = $table . '_centre_idx';

        if ($this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->index('centre_id', $name);
        });
    }

    private function compositeIndex(string $table, array $columns, string $name): void
    {
        if ($this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropUnique($name);
        });
    }

    /** Index metadata via Doctrine, so this works on MySQL and SQLite alike. */
    private function hasIndex(string $table, string $name): bool
    {
        $indexes = DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);

        return array_key_exists(strtolower($name), array_change_key_case($indexes, CASE_LOWER));
    }
};
