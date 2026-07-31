<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class Group extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    /**
     * Kutish zali — the bucket for students not yet in a real group.
     *
     * Historically this was the literal id 1, hardcoded in a dozen places.
     * Each centre needs its own, so it is a lookup — and a foreign key on
     * `centres` rather than a flag on `groups`, because "exactly one per
     * centre" is unique by construction that way and cannot be expressed as a
     * portable unique index on a boolean.
     *
     * Never compare against a bare 1 — always go through here.
     */
    public static function waitingRoomId(): ?int
    {
        return Centre::current()?->waiting_room_group_id;
    }

    public const STATUS_ACTIVE   = 0;   // faol
    public const STATUS_FINISHED = 1;   // tugagan

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'finish_time',
        'monthly_payment',
        'status',
        'finished_at',
    ];

    protected $casts = [
        'status'      => 'integer',
        'finished_at' => 'date',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
            ->withPivotValue('centre_id', Centre::currentId());
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_teachers', 'group_id', 'teacher_id')
            ->withPivotValue('centre_id', Centre::currentId());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINISHED);
    }

    public function isFinished(): bool
    {
        return (int) $this->status === self::STATUS_FINISHED;
    }

    public function isWaitingRoom(): bool
    {
        $id = self::waitingRoomId();

        return $id !== null && (int) $this->id === $id;
    }

    /** Everything except the waiting room — the real teaching groups. */
    public function scopeTeaching(Builder $query): Builder
    {
        $id = self::waitingRoomId();

        return $id === null ? $query : $query->where($query->getModel()->getTable() . '.id', '!=', $id);
    }

    public function statusLabel(): string
    {
        return $this->isFinished() ? 'Tugagan' : 'Faol';
    }

    public function statusTone(): string
    {
        return $this->isFinished() ? 'secondary' : 'success';
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'group_id');
    }

    public function skillGrades(): HasMany
    {
        return $this->hasMany(LessonSkillGrade::class, 'group_id');
    }

    /**
     * Lessons recorded for this group. `lesson_and_histories.group` holds the
     * group id despite the singular name; data = 1 marks an attendance lesson.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(LessonAndHistory::class, 'group')->where('data', 1);
    }

    /**
     * CAUTION: this accessor shadows withCount('students') — Eloquent would
     * write into `students_count` and this magic getter wins on read. Always
     * alias the count instead: withCount(['students as members_count']).
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Server-side filtering for the groups index.
     *
     * @param  array{q?:string,teacher_id?:mixed,room_id?:mixed,has_students?:mixed}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                filled($filters['q'] ?? null),
                fn(Builder $q) => $q->where('name', 'like', '%' . $filters['q'] . '%')
            )
            ->when(
                filled($filters['teacher_id'] ?? null),
                fn(Builder $q) => $q->whereHas(
                    'teachers',
                    fn(Builder $t) => $t->where('users.id', $filters['teacher_id'])
                )
            )
            ->when(
                filled($filters['room_id'] ?? null),
                fn(Builder $q) => $q->where('room_id', $filters['room_id'])
            )
            ->when(
                ($filters['has_students'] ?? null) === '0',
                fn(Builder $q) => $q->doesntHave('students')
            )
            ->when(
                ($filters['has_students'] ?? null) === '1',
                fn(Builder $q) => $q->has('students')
            );
    }
}
