<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'beginning', 'start_time', 'finish_time', 'level', 'monthly_payment'];

    public function teacherhasGroup(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function users()
    {
//        return $this->hasMany(User::class, 'group_id', 'id');
        return User::query()->where('group_id', $this->id)->get();
    }

    public function level(): BelongsTo
    {
        return Level::where('id', 'level');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'group_id');
    }
}
