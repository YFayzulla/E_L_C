<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A homework assignment set for a whole group.
 */
class Homework extends Model
{
    use HasFactory;

    /**
     * "Homework" is an uncountable noun, so Eloquent's default naming resolves
     * this model to the table `homework`. The migration creates `homeworks`.
     * Without this line every Homework query — and every eager load of the
     * `homework` relation — dies with "no such table: homework".
     */
    protected $table = 'homeworks';

    protected $fillable = [
        'group_id',
        'lesson_id',
        'title',
        'description',
        'attachment',
        'due_date',
        'max_score',
        'allow_file',
        'created_by',
    ];

    protected $casts = [
        'due_date'   => 'date',
        'allow_file' => 'boolean',
        'max_score'  => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LessonAndHistory::class, 'lesson_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'homework_id');
    }

    /**
     * Students currently enrolled in this homework's group.
     */
    public function roster()
    {
        return User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $this->group_id))
            ->orderBy('name');
    }

    public function isOverdue(): bool
    {
        // endOfDay(), not the raw cast value: `due_date` is a DATE, so the cast
        // hands back 00:00:00 and a homework due TODAY would read as overdue
        // from one minute past midnight — stamping on-time hand-ins as
        // STATUS_LATE and contradicting the index filter, which treats "due
        // today" as still active (whereDate due_date < today).
        return $this->due_date !== null && $this->due_date->copy()->endOfDay()->isPast();
    }

    /**
     * Deadline as a human label, e.g. "3 kun qoldi" / "2 kun kechikdi".
     */
    public function dueLabel(): string
    {
        if (! $this->due_date) {
            return 'Muddatsiz';
        }

        $days = now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);

        return match (true) {
            $days > 1  => "{$days} kun qoldi",
            $days === 1 => 'Ertaga',
            $days === 0 => 'Bugun',
            $days === -1 => 'Kecha tugadi',
            default    => abs($days) . ' kun kechikdi',
        };
    }
}
