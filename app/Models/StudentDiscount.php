<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Talabaga berilgan chegirma: foizda, doimiy (ends_on = null) yoki muddatli.
 *
 * group_id = null — talabaning barcha guruhlariga. Bir vaqtda bir nechta
 * chegirma amalda bo'lsa, ular qo'shilmaydi — eng kattasi olinadi.
 */
class StudentDiscount extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    protected $fillable = [
        'user_id',
        'group_id',
        'percent',
        'starts_on',
        'ends_on',
        'reason',
        'batch',
        'created_by',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'percent'      => 'integer',
        'starts_on'    => 'date',
        'ends_on'      => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Shu kuni amalda bo'lganlar (bekor qilinmagan, muddat ichida). */
    public function scopeActiveOn(Builder $query, $date = null): Builder
    {
        $day = Carbon::parse($date ?? today())->toDateString();

        return $query->whereNull('cancelled_at')
            ->whereDate('starts_on', '<=', $day)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day));
    }

    public function appliesToGroup(int $groupId): bool
    {
        return $this->group_id === null || (int) $this->group_id === $groupId;
    }

    public function isPermanent(): bool
    {
        return $this->ends_on === null;
    }

    /** Hozirgi holat: amalda, kutilmoqda, tugagan yoki bekor qilingan. */
    public function state(): string
    {
        if ($this->cancelled_at) {
            return 'cancelled';
        }

        if ($this->starts_on->isAfter(today())) {
            return 'upcoming';
        }

        if ($this->ends_on && $this->ends_on->isBefore(today())) {
            return 'ended';
        }

        return 'active';
    }

    public function stateLabel(): string
    {
        return match ($this->state()) {
            'active'    => 'Amalda',
            'upcoming'  => 'Kutilmoqda',
            'ended'     => 'Tugagan',
            default     => 'Bekor qilingan',
        };
    }

    public function stateTone(): string
    {
        return match ($this->state()) {
            'active'   => 'success',
            'upcoming' => 'info',
            default    => 'secondary',
        };
    }

    /** "Doimiy" yoki "01.12.2026 – 31.12.2026". */
    public function periodLabel(): string
    {
        return $this->isPermanent()
            ? $this->starts_on->format('d.m.Y') . ' dan doimiy'
            : $this->starts_on->format('d.m.Y') . ' – ' . $this->ends_on->format('d.m.Y');
    }
}
