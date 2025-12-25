<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'finish_time',
        'monthly_payment'
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_teachers', 'group_id', 'teacher_id');
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }
}
