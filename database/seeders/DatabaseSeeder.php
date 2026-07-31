<?php

namespace Database\Seeders;

use App\Models\Centre;
use App\Tenancy\CentreContext;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * `php artisan db:seed` shu ro'yxatni yuritadi — bu yerda yozilmagan
     * seeder umuman ishga tushmaydi. TeacherTableSeeder aynan shu sababdan
     * ishlamayotgan edi.
     *
     * Tartib muhim: rollar birinchi bo'lishi shart, aks holda assignRole()
     * "There is no role named ..." xatosini beradi.
     *
     * CheatSeeder ataylab ro'yxatga kiritilmagan — u `root` / `password`
     * bilan admin yaratadi.
     */
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,
            CentreSeeder::class,
        ]);

        // Everything below assigns roles, and with Spatie teams on an
        // assignment must belong to a centre: `model_has_roles.centre_id` is
        // NOT NULL and part of the primary key. Seeding without a context does
        // not fail quietly — it fails with a constraint violation — but it is
        // still the wrong shape, so establish the centre first.
        $centre = Centre::withoutGlobalScopes()->orderBy('id')->firstOrFail();

        app(CentreContext::class)->for($centre, function () {
            $this->call([
                AdminTableSeeder::class,
                TeacherTableSeeder::class,
                GroupSeeder::class,
            ]);
        });
    }
}
