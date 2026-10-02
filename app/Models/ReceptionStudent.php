<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionStudent extends Model
{
    use \App\Models\Concerns\BelongsToCentre;
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_TESTED = 'tested';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ARCHIVED = 'archived';

    public const LEVEL_PRE = 'pre';
    public const LEVEL_IELTS = 'ielts';
    public const LEVEL_ZERO = 'nol';

    protected $fillable = [
        'name',
        'phone',
        'parent_name',
        'parent_phone',
        'source',
        'test_taken_at',
        'test_type',
        'score',
        'test_image_path',
        'level',
        'recommended_group_id',
        'status',
        'notes',
        'registered_by',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'test_taken_at' => 'datetime',
        'assigned_at'   => 'datetime',
    ];

    public static function levels(): array
    {
        return [
            self::LEVEL_PRE   => 'Pre',
            self::LEVEL_IELTS => 'IELTS',
            self::LEVEL_ZERO  => 'Nol',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW      => 'Yangi',
            self::STATUS_TESTED   => 'Test topshirgan',
            self::STATUS_ASSIGNED => 'Guruhga ajratilgan',
            self::STATUS_ARCHIVED => 'Arxiv',
        ];
    }

    public function levelLabel(): string
    {
        return self::levels()[$this->level] ?? 'Belgilanmagan';
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? 'Yangi';
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::STATUS_TESTED => 'info',
            self::STATUS_ASSIGNED => 'success',
            self::STATUS_ARCHIVED => 'secondary',
            default => 'warning',
        };
    }

    public function recommendedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'recommended_group_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
