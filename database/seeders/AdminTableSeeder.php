<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Boshlang'ich administrator hisobi.
 *
 * Ilgari `User::create()` ishlatilgani uchun `db:seed` ni ikkinchi marta
 * ishga tushirish `UNIQUE constraint failed: users.phone` bilan yiqilar va
 * butun seed zanjirini to'xtatib qo'yardi.
 */
class AdminTableSeeder extends Seeder
{
    public function run()
    {
        $phone = User::normalizePhone('930430959');

        DB::transaction(function () use ($phone) {
            $admin = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'     => 'admin',
                    'password' => Hash::make('a'),
                ]
            );

            if (! $admin->hasRole('admin')) {
                $admin->assignRole('admin');
            }

            $this->command?->info(
                ($admin->wasRecentlyCreated ? 'Admin yaratildi' : 'Admin allaqachon bor')
                . ": {$admin->name} (+{$admin->phone})"
            );
        });
    }
}
