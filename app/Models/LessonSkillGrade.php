<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-skill mark (reading / listening / writing / speaking) given to one
 * student during one lesson.
 *
 * Always hangs off an EXISTING lesson_and_histories row with data = 1. Creating
 * a lesson row from the grading screen would add a lesson day to the attendance
 * grid, the Excel export and every attendance percentage in the app.
 */
class LessonSkillGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'group_id',
        'user_id',
        'skill',
        'score',
        'comment',
        'graded_by',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LessonAndHistory::class, 'lesson_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function skillLabel(): string
    {
        return config('grading.skill_labels')[$this->skill] ?? ucfirst($this->skill);
    }

    public static function tone(?int $score): string
    {
        if ($score === null) {
            return 'secondary';
        }

        return $score >= config('grading.bands.good', 80)
            ? 'success'
            : ($score >= config('grading.bands.ok', 60) ? 'warning' : 'danger');
    }
}
