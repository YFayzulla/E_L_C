<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesTableSeeder extends Seeder
{
    /**
     * Application roles.
     *
     * admin   — full access
     * user    — teacher
     * student — learner portal
     * parent  — read-only guardian portal over their own children
     *
     * Idempotent, so it is safe to re-run on an existing database.
     */
    public function run()
    {
        foreach (['admin', 'user', 'student', 'parent'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
