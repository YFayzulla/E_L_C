<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Xodimga (hozircha faqat adminga) ko'rsatiladigan ichki bildirishnoma.
 */
class StaffNotification extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    /** O'qituvchi talabani boshqa guruhga ko'chirdi. */
    public const TYPE_STUDENT_TRANSFERRED = 'student_transferred';

    /** Reception yoki o'qituvchi yangi talabani guruhga yozdi. */
    public const TYPE_STUDENT_ENROLLED = 'student_enrolled';

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'title',
        'body',
        'url',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeFor(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function icon(): string
    {
        return match ($this->type) {
            self::TYPE_STUDENT_TRANSFERRED => 'bx-transfer',
            self::TYPE_STUDENT_ENROLLED    => 'bx-user-plus',
            default                        => 'bx-bell',
        };
    }
}
