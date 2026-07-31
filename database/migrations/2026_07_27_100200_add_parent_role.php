<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * The parent portal is guarded by `role:parent`, so the role has to exist
     * on every environment. Seeding it here keeps `php artisan migrate` enough
     * to get a working install.
     */
    public function up()
    {
        foreach (['admin', 'user', 'student', 'parent'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Role::where('name', 'parent')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
