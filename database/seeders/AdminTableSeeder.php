<?php

namespace Database\Seeders;

use App\Models\Centre;
use App\Models\User;
use App\Services\CentreMembershipService;
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

            // Membership and role together, through the one writer that knows
            // about Spatie teams.
            app(CentreMembershipService::class)->attach(
                Centre::withoutGlobalScopes()->orderBy('id')->firstOrFail(),
                $admin,
                $admin->hasRole('admin') ? null : 'admin'
            );

            $this->command?->info(
                ($admin->wasRecentlyCreated ? 'Admin yaratildi' : 'Admin allaqachon bor')
                . ": {$admin->name} (+{$admin->phone})"
            );
        });
    }
}
