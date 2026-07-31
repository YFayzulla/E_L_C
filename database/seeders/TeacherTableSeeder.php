<?php

namespace Database\Seeders;

use App\Models\Centre;
use App\Models\User;
use App\Services\CentreMembershipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo o'qituvchi hisobi.
 *
 * Diqqat: o'qituvchining roli `user`, `teacher` EMAS. Spatie rollari:
 * admin | user (= o'qituvchi) | student | parent.
 *
 * Telefon 998 prefiksi bilan saqlanadi — User::normalizePhone(), SMS xizmati
 * va ota-ona bog'lash mantiqi hammasi shu ko'rinishni kutadi.
 */
class TeacherTableSeeder extends Seeder
{
    public function run()
    {
        $phone = User::normalizePhone('977913885');

        DB::transaction(function () use ($phone) {
            // firstOrCreate: seeder qayta ishga tushirilsa unique indeksga
            // urilib yiqilmasligi uchun.
            $teacher = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'     => 'teacher1',
                    'password' => Hash::make('a'),
                    'percent'  => 40,   // busiz teacherPayment() doim 0 qaytaradi
                ]
            );

            app(CentreMembershipService::class)->attach(
                Centre::withoutGlobalScopes()->orderBy('id')->firstOrFail(),
                $teacher,
                $teacher->hasRole('user') ? null : 'user',
                ['percent' => 40]   // busiz teacherPayment() doim 0 qaytaradi
            );

            $this->command?->info(
                ($teacher->wasRecentlyCreated ? "O'qituvchi yaratildi" : "O'qituvchi allaqachon bor")
                . ": {$teacher->name} (+{$teacher->phone})"
            );
        });
    }
}
