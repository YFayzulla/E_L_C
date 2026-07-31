<?php

use App\Models\Centre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Turns the existing single-tenant installation into centre #1.
 *
 * Everything already in the database belongs to one centre by definition, so
 * the backfill needs no cleverness — only care that it can be re-run, because
 * it will be: once on a production restore during the rehearsal, and once for
 * real.
 */
return new class extends Migration
{
    public function up(): void
    {
        $slug = env('CENTRE_SLUG', 'alpha');

        $centre = Centre::withoutGlobalScopes()->where('slug', $slug)->first();

        if ($centre === null) {
            $centre = new Centre();
            $centre->forceFill([
                'slug'               => $slug,
                'name'               => env('APP_NAME', 'ALPHA'),
                'status'             => Centre::STATUS_ACTIVE,
                'timezone'           => config('app.timezone', 'Asia/Tashkent'),
                'certificate_prefix' => env('CERTIFICATE_PREFIX', 'ALP'),
                // Existing installations send SMS from the platform .env
                // credentials; leaving these null keeps that working.
                'sms_enabled'        => true,
            ])->save();
        }

        // The Kutish zali the installation has always used.
        if ($centre->waiting_room_group_id === null) {
            $waitingRoom = DB::table('groups')->where('name', 'Waiting Room')->first()
                ?? DB::table('groups')->orderBy('id')->first();

            if ($waitingRoom !== null) {
                DB::table('centres')
                    ->where('id', $centre->id)
                    ->update(['waiting_room_group_id' => $waitingRoom->id]);
            }
        }

        // Every existing account becomes an active member. Chunked because a
        // busy centre has thousands of students and this runs in a window.
        DB::table('users')->orderBy('id')->chunkById(500, function ($users) use ($centre) {
            $now  = now();
            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    'centre_id'  => $centre->id,
                    'user_id'    => $user->id,
                    'status'     => Centre::MEMBER_ACTIVE,
                    // Carried over from users.percent; the column stays for now
                    // so a rollback loses nothing.
                    'percent'    => $user->percent,
                    'is_default' => true,
                    'joined_at'  => $user->created_at ?? $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                // insertOrIgnore, not insert: re-running must not explode on
                // the (centre_id, user_id) unique index.
                DB::table('centre_user')->insertOrIgnore($rows);
            }
        });
    }

    public function down(): void
    {
        $slug = env('CENTRE_SLUG', 'alpha');

        $centre = Centre::withoutGlobalScopes()->withTrashed()->where('slug', $slug)->first();

        if ($centre !== null) {
            DB::table('centre_user')->where('centre_id', $centre->id)->delete();
            DB::table('centres')->where('id', $centre->id)->delete();
        }
    }
};
