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
        Level::create(['name'=> 'beginner']);
        Level::create(['name'=> 'elementary']);
        Level::create(['name'=> 'pre-intermediate']);
        Level::create(['name'=> 'upper-intermediate']);
    }
}
