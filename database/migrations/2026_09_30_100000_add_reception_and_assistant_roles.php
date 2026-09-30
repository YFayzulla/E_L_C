<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles', 'roles');
        $now = now();

        DB::table($table)->insertOrIgnore([
            [
                'name'       => 'reception',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'assistant',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        $roles = config('permission.table_names.roles', 'roles');
        $assignments = config('permission.table_names.model_has_roles', 'model_has_roles');

        foreach (['reception', 'assistant'] as $name) {
            $id = DB::table($roles)->where('name', $name)->where('guard_name', 'web')->value('id');

            if ($id === null) {
                continue;
            }

            DB::table($assignments)->where('role_id', $id)->delete();
            DB::table($roles)->where('id', $id)->delete();
        }
    }
};
