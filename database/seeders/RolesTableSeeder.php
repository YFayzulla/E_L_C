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
     * support — support teacher for assessments and skills
     * reception — front desk workflow
     * assistant — speaking/writing homework checker
     *
     * Idempotent, so it is safe to re-run on an existing database.
     */
    public function run()
    {
        foreach (['admin', 'user', 'student', 'parent', 'support', 'reception', 'assistant'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
