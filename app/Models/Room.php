<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['room', 'teacher_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

//    public function groups(): HasMany
//    {
//        return $this->hasMany(Group::class, 'room_id');
//    }
    public function roomTeacher($id)
    {
        if (User::where('room_id', $id)->exists()) {
            return false;
        }
        return true;
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($room) {
            if ($room->isDirty('teacher_id') && !is_null($room->teacher_id)) {
                DB::transaction(function () use ($room) {
                    $groups = $room->groups()->get();
                    foreach ($groups as $group) {
                        GroupTeacher::updateOrCreate(
                            ['group_id' => $group->id],
                            ['teacher_id' => $room->teacher_id]
                        );
                    }
                });
            }
        });
    }
}
