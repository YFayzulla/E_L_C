<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Olimpiada ishtirokchisi: yangi odam, reception yozuvi yoki markazdagi
 * talaba. Guruhga biriktirilgach user_id, group_id, discount_id to'ladi.
 */
class OlympiadParticipant extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_RESERVE  = 'reserve';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'olympiad_id', 'user_id', 'reception_student_id', 'name', 'phone', 'parent_name',
        'parent_phone', 'school', 'scores', 'total', 'percent', 'rank', 'status',
        'discount_percent', 'group_id', 'discount_id', 'enrolled_at', 'notes',
    ];

    protected $casts = [
        'scores'           => 'array',
        'total'            => 'integer',
        'percent'          => 'integer',
        'rank'             => 'integer',
        'discount_percent' => 'integer',
        'enrolled_at'      => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING  => 'Kutilmoqda',
            self::STATUS_ACCEPTED => 'Qabul qilindi',
            self::STATUS_RESERVE  => 'Zaxira',
            self::STATUS_REJECTED => 'O‘tmadi',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_RESERVE  => 'info',
            self::STATUS_REJECTED => 'danger',
            default               => 'secondary',
        };
    }

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(StudentDiscount::class, 'discount_id');
    }

    public function isEnrolled(): bool
    {
        return $this->enrolled_at !== null;
    }

    /** Markazda allaqachon o'qiyotgan talaba (ro'yxatga shundan qo'shilgan). */
    public function isExistingStudent(): bool
    {
        return $this->user_id !== null && ! $this->isEnrolled();
    }
}
