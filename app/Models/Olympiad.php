<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Talabalarni saralab olish olimpiadasi.
 *
 * sections — [{key, label, max}] (bitta bo'lim ham mumkin);
 * rules    — [{from, to, percent}] o'rin bo'yicha TAVSIYA chegirma; admin
 *            har bir ishtirokchida qo'lda o'zgartira oladi.
 */
class Olympiad extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_HELD    = 'held';
    public const STATUS_CLOSED  = 'closed';

    protected $fillable = ['name', 'held_on', 'status', 'sections', 'rules', 'notes', 'created_by'];

    protected $casts = [
        'held_on'  => 'date',
        'sections' => 'array',
        'rules'    => 'array',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Rejalashtirilgan',
            self::STATUS_HELD    => 'O‘tkazildi',
            self::STATUS_CLOSED  => 'Yakunlandi',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_HELD   => 'info',
            self::STATUS_CLOSED => 'secondary',
            default             => 'warning',
        };
    }

    public function participants(): HasMany
    {
        return $this->hasMany(OlympiadParticipant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Barcha bo'limlar maksimumi yig'indisi. */
    public function maxTotal(): int
    {
        return (int) collect($this->sections ?? [])->sum('max');
    }

    /** O'rin uchun tavsiya chegirma foizi (qoida bo'lmasa — null). */
    public function suggestedPercentFor(?int $rank): ?int
    {
        if ($rank === null) {
            return null;
        }

        foreach ((array) $this->rules as $rule) {
            if ($rank >= (int) ($rule['from'] ?? 0) && $rank <= (int) ($rule['to'] ?? 0)) {
                return (int) $rule['percent'];
            }
        }

        return null;
    }
}
