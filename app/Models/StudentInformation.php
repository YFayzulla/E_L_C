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
    use HasFactory;

    protected $fillable = ['user_id', 'group_id', 'group', 'action'];

    protected $casts = [
        'action' => 'integer',
    ];

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
