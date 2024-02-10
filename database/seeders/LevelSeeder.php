<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Level::create(['name'=> 'Starter']);
        Level::create(['name'=> 'Elementary']);
        Level::create(['name'=> 'Pre-intermediate']);
        Level::create(['name'=> 'Upper-intermediate']);
        Level::create(['name'=> 'IELTS']);
        Level::create(['name'=> 'Speaking']);
    }
}
