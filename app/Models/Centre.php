<?php

namespace App\Models;

use App\Tenancy\CentreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

    /**
     * Subdomenlar wildcard (*.domen.uz) bo'lgani uchun har qanday slug
     * avtomatik host'ga aylanadi — shu sababli bir nechtasi band.
     *
     * `www` markaz bo'lib qolsa apex bilan to'qnashadi; `mail`, `smtp`,
     * `ns1` va shu kabilar pochta/DNS yozuvlarini o'g'irlaydi; `api`,
     * `admin`, `app` esa keyinchalik platformaning o'ziga kerak bo'ladi.
     * Ro'yxat ikki joyda ishlatiladi — markaz yaratishda tekshiruv
     * sifatida va ResolveCentre da, chunki bazaga qo'lda yozib qo'yilgan
     * qator ham shu yerdan o'tishi kerak.
     */
    public const RESERVED_SLUGS = [
        'www', 'api', 'admin', 'app', 'mail', 'smtp', 'imap', 'pop', 'webmail',
        'ftp', 'cpanel', 'whm', 'ns', 'ns1', 'ns2', 'dns', 'mx',
        'static', 'assets', 'cdn', 'img', 'media', 'files', 'storage',
        'blog', 'docs', 'help', 'support', 'status', 'test', 'dev', 'staging',
        'super', 'superadmin', 'platform', 'panel', 'login', 'auth',
    ];

    /** Grading keys a centre may override. Anything else stays global. */
    public const OVERRIDABLE_SETTINGS = [
        'bands',
        'progress_weights',
        'late_penalty',
        'progress_months',
        'require_email_verification',
        // Ko'nikma baholash usuli: 'skills' yoki 'single'. Markaz uni
        // Sozlamalar sahifasidan o'zgartiradi.
        'skill_mode',
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

    /* ========================================================= slug va kesh */

    /**
     * ResolveCentre keshi shu kalitda turadi. Ikkala tomon ham shu yerdan
     * olishi kerak — aks holda kesh yozadigan va tozalaydigan kod ayri
     * kalitlar bilan ishlab, to'xtatilgan markaz hamon ochiq qolardi.
     */
    public static function cacheKey(string $slug): string
    {
        return 'centre.slug.' . Str::lower($slug);
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(Str::lower($slug), self::RESERVED_SLUGS, true);
    }

    protected static function booted(): void
    {
        // To'xtatish yoki nomni o'zgartirish darhol kuchga kirsin: aks holda
        // panelda "to'xtatdim, lekin markaz hamon ochiq" degan holat bo'lardi.
        static::saved(fn (self $centre) => $centre->forgetCache());
        static::deleted(fn (self $centre) => $centre->forgetCache());
    }

    public function forgetCache(): void
    {
        Cache::forget(self::cacheKey((string) $this->slug));

        // Slug o'zgargan bo'lsa eskisi ham osilib qolmasin.
        $previous = $this->getOriginal('slug');

        if ($previous && $previous !== $this->slug) {
            Cache::forget(self::cacheKey((string) $previous));
        }
    }

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

    /**
     * Markazning to'liq host nomi.
     *
     * Standart bo'lmagan port joriy so'rovdan olinadi: ishlanma serveri
     * 8000-portda turadi va portsiz havola 80-portga ketib ochilmay
     * qolardi. Konsolda port qo'shilmaydi — u yerda gap production
     * manzili haqida.
     *
     * ResolveCentre bu metodni ISHLATMAYDI — u `config('app.domain')` ni
     * to'g'ridan-to'g'ri o'qiydi va `$request->getHost()` portsiz keladi,
     * ya'ni bu yerdagi port markaz aniqlashga ta'sir qilmaydi.
     */
    public function host(): string
    {
        return $this->slug . '.' . config('app.domain') . $this->portSuffix();
    }

    public function url(string $path = '/'): string
    {
        return rtrim(config('app.scheme', 'https') . '://' . $this->host(), '/')
            . '/' . ltrim($path, '/');
    }

    private function portSuffix(): string
    {
        if (app()->runningInConsole()) {
            return '';
        }

        $port = request()?->getPort();

        return $port && ! in_array((int) $port, [80, 443], true) ? ':' . $port : '';
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Sahifalarda ko'rsatiladigan logotip.
     *
     * Markaz o'zinikini yuklamagan bo'lsa — platformaning standart
     * logotipi. Bitta joyda turishi muhim: aks holda o'nga yaqin
     * blade faylining biri eskisida qolib ketardi.
     */
    public static function brandLogo(): string
    {
        return static::current()?->logoUrl() ?? asset('logos/main.png');
    }

    /** Sahifalarda ko'rsatiladigan nom (alt matni va sarlavhalar uchun). */
    public static function brandName(): string
    {
        return static::current()?->name ?? (string) config('app.name', 'ALPHA');
    }

    /**
     * Markaz rangi asosidagi CSS token ustidan yozish.
     *
     * theme.css da asosiy rangdan kelib chiqadigan OLTITA token bor. Faqat
     * `--app-primary` ni almashtirish yarim bo'yalgan interfeys berardi —
     * tugma yangi rangda, uning hover holati va yumshoq foni esa eskisida.
     * Shuning uchun hammasi bitta hex dan hisoblanadi.
     *
     * Noto'g'ri qiymat — null, ya'ni standart mavzu. Chiqishga faqat
     * o'n oltilik raqamlar tushadi, ya'ni {!! !!} bilan chiqarish xavfsiz.
     */
    public function brandCssVariables(): ?string
    {
        $hex = ltrim((string) $this->brand_color, '#');

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $hover = sprintf(
            '#%02x%02x%02x',
            (int) round($r * .85),
            (int) round($g * .85),
            (int) round($b * .85)
        );

        // Yorug' rang ustida oq matn o'qilmaydi — shuning uchun yorqinlikka
        // qarab qora yoki oq tanlanadi.
        $luminance = (.299 * $r + .587 * $g + .114 * $b) / 255;
        $contrast = $luminance > .6 ? '#10131c' : '#ffffff';

        return "--app-primary:#{$hex};"
            . "--app-primary-hover:{$hover};"
            . "--app-primary-contrast:{$contrast};"
            . "--app-primary-soft:rgba({$r},{$g},{$b},.10);"
            . "--app-primary-soft-hover:rgba({$r},{$g},{$b},.16);"
            . "--app-primary-ring:rgba({$r},{$g},{$b},.28);";
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
