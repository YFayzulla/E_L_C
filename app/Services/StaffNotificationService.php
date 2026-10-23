<?php

namespace App\Services;

use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Markaz adminlariga ichki bildirishnoma yozadi.
 *
 * Xato bo'lsa faqat logga yoziladi: bildirishnoma — qo'shimcha, u tufayli
 * ko'chirish yoki ro'yxatga olishning o'zi bekor bo'lib ketmasligi kerak.
 */
class StaffNotificationService
{
    public function notifyAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = [],
        ?int $actorId = null
    ): void {
        try {
            $actorId ??= auth()->id();

            // role() Spatie team orqali joriy markaz bilan cheklangan.
            $admins = User::role('admin')
                ->inCurrentCentre()
                ->when($actorId, fn ($q) => $q->where('users.id', '!=', $actorId))
                ->pluck('users.id');

            foreach ($admins as $adminId) {
                StaffNotification::create([
                    'user_id'  => $adminId,
                    'actor_id' => $actorId,
                    'type'     => $type,
                    'title'    => $title,
                    'body'     => $body,
                    'url'      => $url,
                    'data'     => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('StaffNotificationService@notifyAdmins error: ' . $e->getMessage());
        }
    }
}
