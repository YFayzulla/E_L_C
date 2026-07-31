<?php

use App\Models\Centre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switches spatie/laravel-permission into "teams" mode, with the centre as the
 * team. This is the lever the whole design rests on: with it, every existing
 * `User::role('student')` call and every `role:admin` middleware declaration
 * becomes centre-aware with no edit to either.
 *
 * The tables are rebuilt rather than altered. `model_has_roles` keys on a
 * composite PRIMARY KEY that has to gain a column, and neither SQLite nor a
 * portable Laravel schema call can do that in place. They are small — a few
 * rows per user — so a read/drop/recreate/reinsert is both simpler and easier
 * to verify than a driver-specific ALTER.
 *
 * NOTE for MySQL: DDL commits implicitly, so a transaction would not protect
 * this. Rehearse it on a restore of production before running it for real.
 */
return new class extends Migration
{
    public function up(): void
    {
        $names   = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'centre_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $pivotPermission = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        // Already converted (a re-run, or a fresh install that migrated with
        // teams already on).
        if (Schema::hasColumn($names['model_has_roles'], $teamKey)) {
            return;
        }

        // Existing rows, held in memory while the tables are rebuilt.
        $roles           = DB::table($names['roles'])->get();
        $permissions     = DB::table($names['permissions'])->get();
        $modelRoles      = DB::table($names['model_has_roles'])->get();
        $modelPerms      = DB::table($names['model_has_permissions'])->get();
        $rolePerms       = DB::table($names['role_has_permissions'])->get();

        // Everything that exists today belongs to the first centre.
        $centreId = Centre::withoutGlobalScopes()->orderBy('id')->value('id');

        if ($centreId === null && $modelRoles->isNotEmpty()) {
            throw new RuntimeException(
                'Markaz topilmadi. Avval 2026_08_01_110300_backfill_first_centre '
                . 'migratsiyasi ishlashi kerak.'
            );
        }

        Schema::drop($names['role_has_permissions']);
        Schema::drop($names['model_has_roles']);
        Schema::drop($names['model_has_permissions']);
        Schema::drop($names['roles']);
        Schema::drop($names['permissions']);

        Schema::create($names['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($names['roles'], function (Blueprint $table) use ($teamKey) {
            $table->bigIncrements('id');
            // NULL means a global role *definition* — the four roles this app
            // ships are the same everywhere; only the assignment is per-centre.
            $table->unsignedBigInteger($teamKey)->nullable();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->index($teamKey, 'roles_team_foreign_key_index');
            $table->unique([$teamKey, 'name', 'guard_name']);
        });

        Schema::create($names['model_has_permissions'], function (Blueprint $table) use (
            $names, $columns, $teamKey, $morphKey, $pivotPermission
        ) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($morphKey);
            $table->index([$morphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')->on($names['permissions'])
                ->onDelete('cascade');

            $table->unsignedBigInteger($teamKey);
            $table->index($teamKey, 'model_has_permissions_team_foreign_key_index');
            $table->primary(
                [$teamKey, $pivotPermission, $morphKey, 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::create($names['model_has_roles'], function (Blueprint $table) use (
            $names, $teamKey, $morphKey, $pivotRole
        ) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($morphKey);
            $table->index([$morphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')->on($names['roles'])
                ->onDelete('cascade');

            $table->unsignedBigInteger($teamKey);
            $table->index($teamKey, 'model_has_roles_team_foreign_key_index');
            $table->primary(
                [$teamKey, $pivotRole, $morphKey, 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::create($names['role_has_permissions'], function (Blueprint $table) use (
            $names, $pivotRole, $pivotPermission
        ) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')->on($names['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')->on($names['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        // ------------------------------------------------------------ restore

        foreach ($permissions->chunk(200) as $chunk) {
            DB::table($names['permissions'])->insert(
                $chunk->map(fn($r) => (array) $r)->all()
            );
        }

        foreach ($roles->chunk(200) as $chunk) {
            DB::table($names['roles'])->insert(
                $chunk->map(function ($r) use ($teamKey) {
                    $row = (array) $r;
                    $row[$teamKey] = null;   // global definition

                    return $row;
                })->all()
            );
        }

        foreach ($modelRoles->chunk(500) as $chunk) {
            DB::table($names['model_has_roles'])->insert(
                $chunk->map(function ($r) use ($teamKey, $centreId) {
                    $row = (array) $r;
                    $row[$teamKey] = $centreId;

                    return $row;
                })->all()
            );
        }

        foreach ($modelPerms->chunk(500) as $chunk) {
            DB::table($names['model_has_permissions'])->insert(
                $chunk->map(function ($r) use ($teamKey, $centreId) {
                    $row = (array) $r;
                    $row[$teamKey] = $centreId;

                    return $row;
                })->all()
            );
        }

        foreach ($rolePerms->chunk(500) as $chunk) {
            DB::table($names['role_has_permissions'])->insert(
                $chunk->map(fn($r) => (array) $r)->all()
            );
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Reversing means dropping the team column, which means rebuilding the
        // same five tables again. Not worth carrying: the way back from this
        // migration is a database restore, which is what the runbook says.
        throw new RuntimeException(
            'Bu migratsiya orqaga qaytarilmaydi — bazani zaxiradan tiklang.'
        );
    }
};
