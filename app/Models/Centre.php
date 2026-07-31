<?php

namespace App\Models;

use App\Tenancy\CentreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * O'quv markazi — a tenant.
 *
 * This model is NOT itself tenant-scoped: it is the thing everything else is
 * scoped BY. Together with User it forms the platform layer.
 */
class Centre extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE    = 0;
    public const STATUS_SUSPENDED = 1;
    public const STATUS_ARCHIVED  = 2;

    /** centre_user.status — membership, which is not the same as having a role. */
    public const MEMBER_INVITED   = 0;
    public const MEMBER_ACTIVE    = 1;
    public const MEMBER_SUSPENDED = 2;

    /** Grading keys a centre may override. Anything else stays global. */
    public const OVERRIDABLE_SETTINGS = [
        'bands',
        'progress_weights',
        'late_penalty',
        'progress_months',
        'require_email_verification',
    ];

    protected $fillable = [
        'slug', 'name', 'legal_name', 'status', 'phone', 'address',
        'locale', 'timezone', 'logo_path', 'brand_color',
        'certificate_prefix', 'waiting_room_group_id',
        'sms_email', 'sms_password', 'sms_from', 'sms_enabled',
        'settings',
    ];

    protected $casts = [
        'status'              => 'integer',
        'sms_enabled'         => 'boolean',
        'certificate_counter' => 'integer',
        'settings'            => 'array',
        // Nobody reading the database should see the SMS gateway password.
        'sms_password'        => 'encrypted',
    ];

    /** Never let the credential reach a view or a JSON response by accident. */
    protected $hidden = ['sms_password'];

    /* ============================================================ relations */

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'centre_user')
            ->withPivot(['status', 'percent', 'is_default', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /** Members who may actually sign in here. */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', self::MEMBER_ACTIVE);
    }

    public function waitingRoom(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'waiting_room_group_id');
    }

    /* =============================================================== scopes */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /* ================================================================ state */

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return match ((int) $this->status) {
            self::STATUS_SUSPENDED => 'To‘xtatilgan',
            self::STATUS_ARCHIVED  => 'Arxiv',
            default                => 'Faol',
        };
    }

    public function host(): string
    {
        return $this->slug . '.' . config('app.domain');
    }

    public function url(string $path = '/'): string
    {
        return rtrim(config('app.scheme', 'https') . '://' . $this->host(), '/')
            . '/' . ltrim($path, '/');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * A grading key, this centre's value winning over the global default.
     *
     * Only OVERRIDABLE_SETTINGS are honoured — letting a centre redefine
     * `skills` would make every historical lesson_skill_grades row unreadable
     * without knowing which set was in force when it was written.
     */
    public function setting(string $key, $default = null)
    {
        $root = explode('.', $key)[0];

        if (in_array($root, self::OVERRIDABLE_SETTINGS, true)) {
            $value = data_get($this->settings, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return config("grading.{$key}", $default);
    }

    /* ============================================== the ambient current one */

    public static function current(): ?self
    {
        return app(CentreContext::class)->centre();
    }

    public static function currentId(): ?int
    {
        return app(CentreContext::class)->id();
    }

    /** Run something as a given centre, restoring the previous one afterwards. */
    public static function for($centre, callable $callback)
    {
        return app(CentreContext::class)->for($centre, $callback);
    }

    /** Run something with no centre filter at all. Use sparingly and visibly. */
    public static function withoutScope(callable $callback)
    {
        return app(CentreContext::class)->withoutScope($callback);
    }

    /** Run something once per active centre, each inside its own context. */
    public static function each(callable $callback): void
    {
        app(CentreContext::class)->each($callback);
    }
}
