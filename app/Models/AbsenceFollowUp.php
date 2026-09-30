<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceFollowUp extends Model
{
    use \App\Models\Concerns\BelongsToCentre;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CALLED = 'called';
    public const STATUS_NO_ANSWER = 'no_answer';
    public const STATUS_FOLLOW_UP = 'follow_up';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'attendance_id',
        'student_id',
        'group_id',
        'contacted_by',
        'contacted_at',
        'contact_person',
        'status',
        'note',
        'next_follow_up_at',
    ];

    protected $casts = [
        'contacted_at'      => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => 'Kutilmoqda',
            self::STATUS_CALLED    => 'Gaplashildi',
            self::STATUS_NO_ANSWER => 'Javob bermadi',
            self::STATUS_FOLLOW_UP => 'Qayta bog‘lanish',
            self::STATUS_RESOLVED  => 'Yopildi',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? 'Kutilmoqda';
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_CALLED, self::STATUS_RESOLVED => 'success',
            self::STATUS_NO_ANSWER => 'danger',
            self::STATUS_FOLLOW_UP => 'warning',
            default => 'secondary',
        };
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
