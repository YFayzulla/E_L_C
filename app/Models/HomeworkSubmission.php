<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (homework, student): what the student handed in, and how the
 * teacher graded it. Unique on (homework_id, user_id), so both submitting and
 * grading are idempotent upserts.
 */
class HomeworkSubmission extends Model
{
    use HasFactory;

    public const STATUS_MISSING = 0;   // Topshirmagan
    public const STATUS_DONE    = 1;   // Topshirgan
    public const STATUS_LATE    = 2;   // Kech topshirgan

    protected $fillable = [
        'homework_id',
        'user_id',
        'submission_text',
        'submission_file',
        'submitted_at',
        'status',
        'score',
        'comment',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
        'status'       => 'integer',
        'score'        => 'integer',
    ];

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class, 'homework_id');
    }

    // Named `student` to match the existing Assessment::student() idiom.
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isGraded(): bool
    {
        return $this->score !== null;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function statusLabel(): string
    {
        return config('grading.homework_status')[$this->status] ?? '—';
    }

    /**
     * Bootstrap contextual colour for the status badge.
     */
    public function statusTone(): string
    {
        return match ((int) $this->status) {
            self::STATUS_DONE => 'success',
            self::STATUS_LATE => 'warning',
            default           => 'danger',
        };
    }

    /**
     * Colour band for a score, using the shared 80/60 thresholds.
     */
    public function scoreTone(?int $max = null): string
    {
        if ($this->score === null) {
            return 'secondary';
        }

        $max = $max ?: ($this->homework->max_score ?? 100);
        $pct = $max > 0 ? ($this->score / $max) * 100 : 0;

        return $pct >= config('grading.bands.good', 80)
            ? 'success'
            : ($pct >= config('grading.bands.ok', 60) ? 'warning' : 'danger');
    }
}
