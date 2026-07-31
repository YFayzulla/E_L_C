<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

/**
 * Kutish zali — guruhga hali biriktirilmagan talabalar shu yerda turadi.
 *
 * Uning id = 1 bo'lishi muhim: Group::waitingRoomId() shunga tayanadi, va
 * to'lovlar ro'yxati hamda ko'chirish mantiqi shu id bo'yicha filtrlaydi.
 */
class GroupSeeder extends Seeder
{
    public function run()
    {
        // firstOrCreate — takroriy `db:seed` da ikkinchi "Waiting Room"
        // yaratilib qolmasligi uchun.
        $group = Group::firstOrCreate(
            ['name' => 'Waiting Room'],
            ['description' => 'This is the waiting room for new members.']
        );

        // The centre points at its own waiting room; there is no magic id any
        // more, so the group just has to be registered once.
        $centre = \App\Models\Centre::current();

        if ($centre !== null && $centre->waiting_room_group_id !== $group->id) {
            $centre->forceFill(['waiting_room_group_id' => $group->id])->save();
        }

        $this->command?->info(
            ($group->wasRecentlyCreated ? 'Kutish zali yaratildi' : 'Kutish zali allaqachon bor')
            . " (id {$group->id})"
        );
    }
}
