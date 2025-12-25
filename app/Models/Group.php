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
        'room_id',
        'monthly_payment'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id');
    }

    public function teachers(): BelongsToMany
    {
        // Assuming teachers are also users and linked via group_user or a separate table if needed.
        // If teachers are distinct in group_teachers table, keep it.
        // If you want to unify, you might use roles to distinguish.
        // For now, keeping group_teachers logic if it was separate, or merging if desired.
        // Based on previous migration list, group_teachers existed.
        // If we want to unify everything into group_user, we can filter by role.
        // But usually keeping teachers separate or using roles in pivot is cleaner.
        // Let's assume we keep group_teachers for teachers for now to avoid breaking too much,
        // OR if the request implies teachers are also just users in groups:
        // "user related to group is belongs to like one to one i wanna make it like one student should sign in to many group"
        // This specifically mentions students.
        
        return $this->belongsToMany(User::class, 'group_teachers', 'group_id', 'teacher_id');
    }

    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    protected static function boot()
    {
        parent::boot();

        // Logic for auto-assigning teachers based on room might need review if structure changes significantly.
        // Leaving as is for now unless requested to change.
        static::created(function ($group) {
            if (!is_null($group->room_id)) {
                $room = Room::find($group->room_id);
                if ($room && !is_null($room->teacher_id)) { // Assuming room has teacher_id, check Room model if needed
                     // This part depends on Room model having teacher_id which wasn't in the migration shown.
                     // If it was there before, fine.
                }
            }
        });
    }
}
