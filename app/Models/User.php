<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'passport',
        'date_born',
        'location',
        'parents_name',
        'parents_tel',
        'photo',
        'should_pay',
        'description',
        'status',
        'percent',
        'mark',
    ];

    public function teacherHasStudents()
    {
        $groupIds = $this->teacherGroups()->pluck('groups.id');
        return User::role('student')->whereHas('studentGroups', function ($q) use ($groupIds) {
            $q->whereIn('groups.id', $groupIds);
        })->count();
    }

    public function teacherPayment()
    {
        $groups = $this->teacherGroups()->withCount('students')->get();

        $totalPayment = $groups->sum(function ($group) {
            if ($group) {
                return $group->monthly_payment * $group->students_count;
            }
            return 0;
        });

        return $totalPayment * $this->percent / 100;
    }

    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id');
    }

    public function teacherGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_teachers', 'teacher_id', 'group_id');
    }

    public function studentinformation()
    {
        return $this->hasMany(StudentInformation::class);
    }

    public function studenthistory()
    {
        return $this->hasMany(HistoryPayments::class);
    }

    public function assessment()
    {
        return $this->hasMany(Assessment::class);
    }

    public function deptStudent()
    {
        return $this->hasOne(DeptStudent::class, 'user_id', 'id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function teacherHasGroup()
    {
        return $this->teacherGroups()->count();
    }

    public function checkAttendanceStatus()
    {
        return $this->attendances()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->exists();
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function studentsGroup()
    {
        $groups = $this->studentGroups;

        if ($groups->isEmpty()) {
            return 'students without a group';
        }

        return $groups->map(function ($group) {
            return $group->name;
        })->implode(', ');
    }
}
