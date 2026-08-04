<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * The parent portal is guarded by `role:parent`, so the role has to exist
     * on every environment. Seeding it here keeps `php artisan migrate` enough
     * to get a working install.
     *
     * Raw inserts, NOT Role::findOrCreate(), and the reason matters:
     *
     * A migration runs against the schema as it was at THAT point in history,
     * but an Eloquent model always reflects the app's CURRENT configuration.
     * Once `permission.teams` was switched on, `Role::findOrCreate()` started
     * querying `roles.centre_id` — a column added by a migration that runs
     * eighteen steps later. On a database that had not caught up yet, the
     * whole `migrate` run died here with "Unknown column 'centre_id'".
     *
     * Raw query-builder calls name their own columns, so they cannot drift
     * with the app.
     */
    public function up()
    {
        $table = config('permission.table_names.roles', 'roles');
        $now   = now();

        foreach (['admin', 'user', 'student', 'parent'] as $name) {
            // insertOrIgnore, not insert: (name, guard_name) is unique and
            // three of these four already exist on an established install.
            DB::table($table)->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        $table = config('permission.table_names.roles', 'roles');

        DB::table($table)
            ->where('name', 'parent')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
