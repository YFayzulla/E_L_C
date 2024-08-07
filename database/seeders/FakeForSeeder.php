<?php

namespace Database\Seeders;

use App\Models\DeptStudent;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FakeForSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $teacher = User::query()->forceCreate([
            'name' => 'test',
            'password' => Hash::make('a'),
            'phone' => '0123456789',
        ])->assignRole('user');

        User::query()->forceCreate([
            'name' => 'test2',
            'password' => Hash::make('a'),
            'phone' => '0123456780',
        ])->assignRole('user');

        $group1 = Group::query()->firstOrCreate([
            'name' => 'group_test1',
        ]);
        $group2 = Group::query()->firstOrCreate([
            'name' => 'group_test2',
        ]);

        GroupTeacher::query()->firstOrCreate([
            'teacher_id' => $teacher->id,
            'group_id' => $group1->id,
        ]);
        GroupTeacher::query()->firstOrCreate([
            'teacher_id' => $teacher->id,
            'group_id' => $group2->id,
        ]);

        $i = 1;

        for ($i = 1; $i <= 10; $i++) {


            $student = User::query()->forceCreate([
                'name'=> 'student'.$i,
                'password' => Hash::make('student'.$i),
                'phone' => '012345678'.$i,
                'should_pay'=>10000,
                'group_id' => rand($group1->id, $group2->id)
            ])->assignRole('student');


            StudentInformation::create([
                'user_id' => $student->id,
                'group_id' => $group1->id,
                'group' => $group1->name,
            ]);

            DeptStudent::create([
                'user_id' => $student->id,
                'payed' => 0,
                'dept' => 1000000,
                'status_month' => 0
            ]);
        }

    }
}
