<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    protected $fillable = [
        'user_id', 'get_mark', 'skill', 'group', 'overall_result',
        'for_what', 'rec_group', 'history_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * What this mark measured, ready to show.
     *
     * A skill when one was chosen; otherwise the old free-text note, so rows
     * written before the skill column existed still read sensibly. "Umumiy"
     * when there is neither.
     */
    public function skillLabel(): string
    {
        if (filled($this->skill)) {
            $labels = (array) config('grading.skill_labels');

            return $labels[$this->skill] ?? $this->skill;
        }

        return filled($this->for_what) ? $this->for_what : 'Umumiy';
    }

    /** Only the marks that measured a named skill. */
    public function scopeForSkill($query, string $skill)
    {
        return $query->where('skill', $skill);
    }
}
