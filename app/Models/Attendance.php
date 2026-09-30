<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'who_checked',
        'status',
        'lesson_id',
        'reason',
        'reason_written_by',
        'reason_written_at',
    ];

    protected $casts = [
        'reason_written_at' => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function teacher()
    {
        // Agar bazada ustun nomi 'who_checked' bo'lsa:
        return $this->belongsTo(User::class, 'who_checked');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lesson()
    {
        return $this->belongsTo(LessonAndHistory::class, 'lesson_id');
    }

    public function reasonWriter()
    {
        return $this->belongsTo(User::class, 'reason_written_by');
    }

    public function followUps()
    {
        return $this->hasMany(AbsenceFollowUp::class, 'attendance_id');
    }

    public function latestFollowUp()
    {
        return $this->hasOne(AbsenceFollowUp::class, 'attendance_id')->latestOfMany();
    }

}
