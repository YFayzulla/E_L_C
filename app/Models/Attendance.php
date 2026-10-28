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
        'excused',
        'reason_written_by',
        'reason_written_at',
    ];

    protected $casts = [
        'reason_written_at' => 'datetime',
        'excused'           => 'boolean',
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

    /*
    |--------------------------------------------------------------------------
    | Yo'qlama formasidagi to'rt tugma
    |--------------------------------------------------------------------------
    | Bazada `status` ikki qiymat oladi (0 — kelmadi, 2 — kechikdi; kelgan
    | talaba uchun qator yozilmaydi). "Kelmadi" ikkiga bo'linadi: sababli va
    | sababsiz — farq `excused` ustunida. Formada (web va API) to'rttasi bitta
    | raqam bilan yuboriladi.
    */
    public const FORM_UNEXCUSED = 0;   // kelmadi, sababsiz
    public const FORM_PRESENT   = 1;   // keldi
    public const FORM_LATE      = 2;   // kechikdi
    public const FORM_EXCUSED   = 3;   // kelmadi, sababli

    public const FORM_VALUES = [
        self::FORM_PRESENT, self::FORM_EXCUSED, self::FORM_UNEXCUSED, self::FORM_LATE,
    ];

    /**
     * Formadagi tanlovni bazaga yoziladigan maydonlarga aylantiradi.
     * "Keldi" uchun null — qator yozilmaydi (bor bo'lsa o'chiriladi).
     *
     * Izoh sababli, sababsiz va kechikdi uchun yoziladi.
     *
     * @return array{status: int, excused: ?bool, reason: ?string, reason_written_by: ?int, reason_written_at: mixed}|null
     */
    public static function attributesFromForm(int $formValue, ?string $comment, ?int $writerId): ?array
    {
        if (! in_array($formValue, [self::FORM_EXCUSED, self::FORM_UNEXCUSED, self::FORM_LATE], true)) {
            return null;
        }

        $comment = trim((string) $comment);
        $hasComment = $comment !== '';

        return [
            'status'            => $formValue === self::FORM_LATE ? 2 : 0,
            'excused'           => $formValue === self::FORM_LATE ? null : $formValue === self::FORM_EXCUSED,
            'reason'            => $hasComment ? $comment : null,
            'reason_written_by' => $hasComment ? $writerId : null,
            'reason_written_at' => $hasComment ? now() : null,
        ];
    }

    /** Shu yozuv formada qaysi tugma bo'lib ko'rinadi. */
    public function formValue(): int
    {
        if ((int) $this->status === 2) {
            return self::FORM_LATE;
        }

        return $this->isExcused() ? self::FORM_EXCUSED : self::FORM_UNEXCUSED;
    }

    /** Sababli "kelmadi". Ustun bo'sh bo'lgan eski qatorda — sabab yozilganmi. */
    public function isExcused(): bool
    {
        if ((int) $this->status !== 0) {
            return false;
        }

        return $this->excused ?? filled($this->reason);
    }

    /** Jurnallardagi belgi: Sababli, Sababsiz yoki Kechikdi. */
    public function kindLabel(): string
    {
        if ((int) $this->status === 2) {
            return 'Kechikdi';
        }

        return $this->isExcused() ? 'Sababli' : 'Sababsiz';
    }

    /** Talabaning o'z sahifalari uchun — birinchi shaxsda. */
    public function kindLabelForStudent(): string
    {
        if ((int) $this->status === 2) {
            return 'Kechikdim';
        }

        return $this->isExcused() ? 'Kelmadim · sababli' : 'Kelmadim · sababsiz';
    }

    public function kindTone(): string
    {
        if ((int) $this->status === 2) {
            return 'warning';
        }

        return $this->isExcused() ? 'primary' : 'danger';
    }

    public function hasComment(): bool
    {
        return filled($this->reason);
    }
}
