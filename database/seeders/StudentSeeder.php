<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        foreach (range(1, 200) as $index) {
            User::create([
                'name' => $faker->name,
                'phone' => '+998911234567',
                'password' => bcrypt('password'),// You might want to use a more secure method for passwords
                'passport' => $faker->name,
                'date_born' => $faker->date,
                'location' => $faker->city,
                'description' => $faker->text,
                'parents_name' => $faker->name,
                'parents_tel' => $faker->phoneNumber,
                'should_pay' => $faker->numerify,
            ])->assignRole('Student');

        }

        User::create([
            'name' => 'fayzulla',
            'phone' => '+998911234567',
            'password' => bcrypt('password'),// You might want to use a more secure method for passwords
            'passport' => $faker->name,
            'date_born' => $faker->date,
            'location' => $faker->city,
            'description' => $faker->text,
            'parents_name' => $faker->name,
            'parents_tel' => $faker->phoneNumber,
            'should_pay' => $faker->numerify,
        ])->assignRole('Student');
    }
}
