<?php

namespace Database\Seeders;

use App\Models\Group;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Group::create([
            'name' => 'Waiting Room',
            'description' => 'This is the waiting room for new members.',
        ]);
    }
}
