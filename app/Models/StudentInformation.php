<?php

namespace App\Models;

use App\Services\StudentGroupService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Group-membership history for a student.
 *
 * `action` (added in Phase 0) is what turns this from a join-only log into a
 * real audit trail: 0 = qo'shildi, 1 = chiqdi.
 */
class StudentInformation extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    protected $fillable = ['user_id', 'group_id', 'group', 'action', 'actor_id'];

    protected $casts = [
        'action' => 'integer',
    ];

    /**
     * Kim qildi. Null — eski qatorlar (ustun keyinroq qo'shilgan) yoki
     * autentifikatsiyasiz kontekst: konsol buyruqlari va seeder'lar.
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Tarix jadvalida ko'rsatish uchun. Aktyor o'chirilgan bo'lsa ham
     * (actor_id NULL ga o'tadi) qator o'z ma'nosini yo'qotmaydi.
     */
    public function actorLabel(): string
    {
        return $this->actor?->name ?? 'Noma’lum';
    }

    public function groups()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Uzbek label for the `action` column.
     */
    public function actionLabel(): string
    {
        return (int) $this->action === StudentGroupService::ACTION_LEFT
            ? 'Chiqdi'
            : 'Qo‘shildi';
    }

    /**
     * Badge tone matching actionLabel(): bg-label-{tone}.
     */
    public function actionTone(): string
    {
        return (int) $this->action === StudentGroupService::ACTION_LEFT
            ? 'danger'
            : 'success';
    }

    /**
     * Boxicon matching actionLabel().
     */
    public function actionIcon(): string
    {
        return (int) $this->action === StudentGroupService::ACTION_LEFT
            ? 'bx-log-out'
            : 'bx-log-in';
    }
}
