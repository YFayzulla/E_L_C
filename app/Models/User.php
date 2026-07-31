<?php

namespace App\Models;

use App\Notifications\VerifyEmailUz;
use App\Services\EnrollmentHistoryService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{

    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /*
    | Enrolment lifecycle. Deliberately NOT `status` — that column is the
    | paid-months counter (decremented monthly by user:status:update; negative
    | means "owes N months") and every debt screen reads it.
    */
    public const STUDY_ACTIVE    = 0;   // faol
    public const STUDY_GRADUATED = 1;   // bitirgan
    public const STUDY_STOPPED   = 2;   // to'xtatgan

    protected $fillable = [
        'name',
        'phone',
        'email',
        'study_status',
        'graduated_at',
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
    // NOTE: 'email_verified_at' is deliberately NOT fillable — a stray
    // ->update($request->all()) must never let an account verify itself.

    public function teacherHasStudents()
    {
        $groupIds = $this->teacherGroups()->pluck('groups.id');
        return User::role('student')->whereHas('groups', function ($q) use ($groupIds) {
            $q->whereIn('groups.id', $groupIds);
        })->count();
    }

    public function teacherPayment()
    {
        // Sum payments from the pivot `group_user.payment` for each group this teacher teaches.
        $groupIds = $this->teacherGroups()->pluck('groups.id')->toArray();

        if (empty($groupIds)) {
            return 0;
        }

        // Use DB to sum payments from pivot table for these groups
        $groupTotal = \Illuminate\Support\Facades\DB::table('group_user')
            ->whereIn('group_id', $groupIds)
            ->sum('payment');

        return ($groupTotal ?: 0) * ($this->percentHere() / 100);
    }

    /* ================================================== centre membership */

    public function centres(): BelongsToMany
    {
        return $this->belongsToMany(Centre::class, 'centre_user')
            ->withPivot(['status', 'percent', 'is_default', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function activeCentres(): BelongsToMany
    {
        return $this->centres()->wherePivot('status', Centre::MEMBER_ACTIVE);
    }

    public function belongsToCentre(?int $centreId): bool
    {
        return $centreId !== null
            && $this->centres()
                ->whereKey($centreId)
                ->wherePivot('status', Centre::MEMBER_ACTIVE)
                ->exists();
    }

    /**
     * This teacher's payout share AT THE CURRENT CENTRE.
     *
     * A teacher can work at two centres on different terms, so the share is a
     * fact about the membership, not about the person. `users.percent` remains
     * as the fallback for rows not yet migrated.
     */
    public function percentHere(): int
    {
        $centreId = Centre::currentId();

        if ($centreId !== null) {
            $pivot = $this->centres()->whereKey($centreId)->first()?->pivot;

            if ($pivot?->percent !== null) {
                return (int) $pivot->percent;
            }
        }

        return (int) $this->percent;
    }

    /**
     * Users of the current centre.
     *
     * `users` carries no centre_id — membership is many-to-many — so no global
     * scope protects a bare User::find(). Anywhere an id arrives from a URL,
     * go through here.
     */
    public function scopeInCurrentCentre(Builder $query): Builder
    {
        $centreId = Centre::currentId();

        if ($centreId === null) {
            return $query;
        }

        return $query->whereHas('centres', fn(Builder $q) => $q
            ->whereKey($centreId)
            ->where('centre_user.status', Centre::MEMBER_ACTIVE));
    }

    public function groups(): BelongsToMany
    {
        // withPivot('payment') matters: admin/student/edit reads
        // $student->groups->pluck('pivot.payment', 'id') and silently got all
        // nulls without it, falling back to groups.monthly_payment.
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id')
            ->withPivot('payment')
            // The pivot is not a model, so nothing else stamps its centre.
            ->withPivotValue('centre_id', Centre::currentId());
    }

    public function teacherGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_teachers', 'teacher_id', 'group_id')
            ->withPivotValue('centre_id', Centre::currentId());
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
        'study_status'      => 'integer',
        'graduated_at'      => 'date',
        'is_super_admin'    => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Enrolment lifecycle
    |--------------------------------------------------------------------------
    */

    public function isGraduated(): bool
    {
        return (int) $this->study_status === self::STUDY_GRADUATED;
    }

    public function isStudying(): bool
    {
        return (int) $this->study_status === self::STUDY_ACTIVE;
    }

    public function studyStatusLabel(): string
    {
        return match ((int) $this->study_status) {
            self::STUDY_GRADUATED => 'Bitirgan',
            self::STUDY_STOPPED   => "To‘xtatgan",
            default               => 'Faol',
        };
    }

    public function studyStatusTone(): string
    {
        return match ((int) $this->study_status) {
            self::STUDY_GRADUATED => 'info',
            self::STUDY_STOPPED   => 'secondary',
            default               => 'success',
        };
    }

    /**
     * True when the student sits in no real group — only the Kutish zali, or
     * nothing at all. These are not billed.
     */
    public function isWaitingOnly(): bool
    {
        return ! $this->groups()
            ->where('groups.id', '!=', Group::waitingRoomId())
            ->exists();
    }

    /** Students who should appear on the billing screens. */
    public function scopeBillable(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->whereNull('users.study_status')
                    ->orWhere('users.study_status', self::STUDY_ACTIVE);
            })
            ->whereHas('groups', fn(Builder $g) => $g->where('groups.id', '!=', Group::waitingRoomId()));
    }

    public function studentsGroup()
    {
        $groups = $this->groups;

        if ($groups->isEmpty()) {
            return 'students without a group';
        }

        return $groups->map(function ($group) {
            return $group->name;
        })->implode(', ');
    }

    /*
    |--------------------------------------------------------------------------
    | E-mail verification
    |--------------------------------------------------------------------------
    | Login is by phone number and most accounts have no e-mail at all, so the
    | stock MustVerifyEmail behaviour (no address => never verified) would lock
    | out the entire centre. An account without an address is treated as
    | verified; only accounts that actually have one can be unverified.
    */

    public function hasVerifiedEmail(): bool
    {
        return blank($this->email) ? true : ! is_null($this->email_verified_at);
    }

    public function getEmailForVerification(): ?string
    {
        return $this->email;
    }

    public function sendEmailVerificationNotification(): void
    {
        if (blank($this->email)) {
            return; // nothing to send to, and no exception either
        }

        $this->notify(new VerifyEmailUz());
    }

    /**
     * True when this account has an address that still needs confirming.
     * Drives the banner in the layout and the badges in the admin lists.
     */
    public function needsEmailVerification(): bool
    {
        return filled($this->email) && is_null($this->email_verified_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Homework, skills
    |--------------------------------------------------------------------------
    */

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'user_id');
    }

    public function skillGrades(): HasMany
    {
        return $this->hasMany(LessonSkillGrade::class, 'user_id');
    }

    public function homeworksCreated(): HasMany
    {
        return $this->hasMany(Homework::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Parent <-> student
    |--------------------------------------------------------------------------
    */

    /**
     * Students this user (a parent) is allowed to follow.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * Parent accounts attached to this user (a student).
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    /**
     * Normalise any locally-entered number to the stored 998XXXXXXXXX format.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return '998' . substr($digits, -9);
    }

    /**
     * Monthly payment status shown to students and parents.
     */
    public function isPaidThisMonth(): bool
    {
        return (int) $this->status > 0;
    }

    /**
     * Absence / late records. A row is only written when a student was NOT
     * present (0 = absent, 2 = late) — see TeacherAdminPanel@attendance_submit.
     */
    public function absences()
    {
        return $this->attendances()->whereIn('status', [0, 2]);
    }

    /**
     * Attendance percentage over the given window.
     * Lessons held = LessonAndHistory rows (data = 1) for the student's groups;
     * missed = the student's absence rows. Present is implicit.
     */
    public function attendanceRate(int $days = 30): int
    {
        $since = now()->subDays($days);

        // The denominator is "lessons this student was ENROLLED FOR", replayed
        // from their join/leave history — not "lessons in the groups they are in
        // today". The latter erases the past on every transfer: the old group's
        // lessons leave the denominator while the absences collected there stay
        // in the numerator, so a student who missed 2 of 10 lessons jumps to
        // 100% the moment they change group.
        $lessons = app(EnrollmentHistoryService::class)->lessonCountSince($this->id, $since);

        if ($lessons <= 0) {
            // No lessons on record for the window — nothing to be absent from.
            return $this->absences()->where('created_at', '>=', $since)->exists() ? 0 : 100;
        }

        $missed = $this->absences()->where('created_at', '>=', $since)->count();

        return (int) round(max(0, $lessons - $missed) / $lessons * 100);
    }
}
