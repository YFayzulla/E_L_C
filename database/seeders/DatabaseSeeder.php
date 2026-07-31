<?php

namespace Database\Seeders;

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
            AdminTableSeeder::class,
            TeacherTableSeeder::class,
            GroupSeeder::class,
        ]);
    }
}
