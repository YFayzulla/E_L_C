<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Eskiz (notify.eskiz.uz) SMS gateway client.
 *
 * The contract that matters: NOTHING is sent until ESKIZ_EMAIL and
 * ESKIZ_PASSWORD are in .env. isConfigured() is checked before any network
 * call, so an unconfigured install simply logs "skipped" — it never throws,
 * never blocks a save, and never leaves a half-finished HTTP request in a
 * request cycle that is also holding a database transaction open.
 *
 * Everything here returns a result instead of throwing, because sending an SMS
 * is a side effect of saving attendance or a grade: a dead gateway must not
 * roll back the thing the user actually came to do.
 */
class MessageService
{
    /** Recipient keys the SMS form offers. */
    public const AUDIENCES = ['ota', 'ona', 'vasiy', 'student'];

    public const AUDIENCE_LABELS = [
        'ota'     => 'Otasiga',
        'ona'     => 'Onasiga',
        'vasiy'   => 'Vasiysiga',
        'student' => 'Talabaning o‘ziga',
    ];

    /**
     * Who would actually receive an SMS about this student, for the chosen
     * audiences. Returns one entry per REAL phone number, de-duplicated — a
     * father and mother who share a number get one message, not two.
     *
     * @param  array<int, string>  $audiences  subset of self::AUDIENCES
     * @return array<string, array{name: string, phone: string, label: string}>  keyed by phone
     */
    public function recipientsFor(User $student, array $audiences): array
    {
        $audiences = array_values(array_intersect($audiences, self::AUDIENCES));
        $out = [];

        if (in_array('student', $audiences, true) && filled($student->phone)) {
            $out[(string) $student->phone] = [
                'name'  => $student->name,
                'phone' => (string) $student->phone,
                'label' => self::AUDIENCE_LABELS['student'],
            ];
        }

        $wanted = array_diff($audiences, ['student']);

        if (empty($wanted)) {
            return $out;
        }

        foreach ($student->guardians()->get() as $guardian) {
            $relation = $guardian->pivot->relation ?: 'ota';

            if (! in_array($relation, $wanted, true) || blank($guardian->phone)) {
                continue;
            }

            // Keyed by phone: two guardians on one number receive one SMS.
            $out[(string) $guardian->phone] = [
                'name'  => $guardian->name,
                'phone' => (string) $guardian->phone,
                'label' => self::AUDIENCE_LABELS[$relation] ?? $relation,
            ];
        }

        return $out;
    }

    /**
     * Send one message about a student to the chosen audiences.
     *
     * @param  array<int, string>  $audiences
     * @return array{sent: int, skipped: int, recipients: array<int, string>}
     */
    public function sendAboutStudent(User $student, string $message, array $audiences): array
    {
        $recipients = $this->recipientsFor($student, $audiences);

        $sent = 0;
        $delivered = [];
        $reasons = [];

        foreach ($recipients as $recipient) {
            $result = $this->send($recipient['phone'], $message);

            if ($result['sent']) {
                $sent++;
                $delivered[] = $recipient['name'] . ' (+' . $recipient['phone'] . ')';
                continue;
            }

            // Report the real reason rather than a generic failure — "Eskiz
            // sozlanmagan" and "raqam yo'q" need very different fixes.
            $reasons[$result['reason']] = ($reasons[$result['reason']] ?? 0) + 1;
        }

        return [
            'sent'       => $sent,
            'skipped'    => count($recipients) - $sent,
            'total'      => count($recipients),
            'recipients' => $delivered,
            'reasons'    => $reasons,
        ];
    }

    /* ==================================================================
     | Gateway state
     ================================================================== */

    /**
     * Are the Eskiz credentials present in .env?
     *
     * This is the gate the whole feature hangs on: false means not a single
     * HTTP request is attempted anywhere in the app.
     */
    public function isConfigured(): bool
    {
        return filled(config('eskiz.email')) && filled(config('eskiz.password'));
    }

    /** Configured AND not switched off AND not in dry-run. */
    public function canSend(): bool
    {
        return $this->isConfigured()
            && (bool) config('eskiz.enabled', true)
            && ! (bool) config('eskiz.dry_run', false);
    }

    /**
     * Human-readable state for the admin screens.
     *
     * @return array{ok: bool, level: string, title: string, detail: string}
     */
    public function status(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false, 'level' => 'danger',
                'title' => 'Sozlanmagan',
                'detail' => 'ESKIZ_EMAIL va ESKIZ_PASSWORD .env faylida yo‘q — SMS yuborilmaydi.',
            ];
        }

        if (! config('eskiz.enabled', true)) {
            return [
                'ok' => false, 'level' => 'secondary',
                'title' => 'O‘chirilgan',
                'detail' => 'ESKIZ_ENABLED=false — sozlamalar bor, lekin yuborish o‘chirilgan.',
            ];
        }

        if (config('eskiz.dry_run', false)) {
            return [
                'ok' => false, 'level' => 'warning',
                'title' => 'Sinov rejimi',
                'detail' => 'ESKIZ_DRY_RUN=true — xabarlar faqat logga yoziladi, haqiqiy SMS ketmaydi.',
            ];
        }

        return [
            'ok' => true, 'level' => 'success',
            'title' => 'Ulangan',
            'detail' => 'Jo‘natuvchi nomi: ' . config('eskiz.from') . '.',
        ];
    }

    /* ==================================================================
     | Authentication
     ================================================================== */

    /**
     * A valid bearer token, from cache when possible.
     * Returns null when unconfigured or when Eskiz rejects the credentials.
     */
    public function getToken(bool $force = false): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $key = config('eskiz.token_cache_key', 'eskiz.token');

        if (! $force && ($cached = Cache::get($key))) {
            return $cached;
        }

        try {
            $response = Http::timeout(config('eskiz.timeout', 15))
                ->asForm()
                ->post($this->url('/auth/login'), [
                    'email'    => config('eskiz.email'),
                    'password' => config('eskiz.password'),
                ]);

            $token = $response->json('data.token');

            if (! $response->successful() || ! $token) {
                Log::error('Eskiz: login failed', [
                    'status' => $response->status(),
                    'body'   => Str::limit($response->body(), 300),
                ]);

                return null;
            }

            Cache::put($key, $token, now()->addDays((int) config('eskiz.token_ttl_days', 25)));

            return $token;
        } catch (\Throwable $e) {
            Log::error('Eskiz: login exception — ' . $e->getMessage());

            return null;
        }
    }

    /** Ask Eskiz to extend the current token. Falls back to a fresh login. */
    public function refreshToken(): ?string
    {
        $token = Cache::get(config('eskiz.token_cache_key', 'eskiz.token'));

        if (! $token) {
            return $this->getToken(true);
        }

        try {
            $response = Http::timeout(config('eskiz.timeout', 15))
                ->withToken($token)
                ->patch($this->url('/auth/refresh'));

            $fresh = $response->json('data.token');

            if ($response->successful() && $fresh) {
                Cache::put(
                    config('eskiz.token_cache_key', 'eskiz.token'),
                    $fresh,
                    now()->addDays((int) config('eskiz.token_ttl_days', 25))
                );

                return $fresh;
            }
        } catch (\Throwable $e) {
            Log::warning('Eskiz: refresh failed, falling back to login — ' . $e->getMessage());
        }

        return $this->getToken(true);
    }

    /**
     * Remaining SMS balance, or null when it cannot be read.
     */
    public function balance(): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $token = $this->getToken();

        if (! $token) {
            return null;
        }

        try {
            $response = Http::timeout(config('eskiz.timeout', 15))
                ->withToken($token)
                ->get($this->url('/user/get-limit'));

            $balance = $response->json('data.balance');

            return $balance === null ? null : (int) $balance;
        } catch (\Throwable $e) {
            Log::warning('Eskiz: balance check failed — ' . $e->getMessage());

            return null;
        }
    }

    /* ==================================================================
     | Sending
     ================================================================== */

    /**
     * Send one SMS.
     *
     * Never throws. The return value says what happened so callers can report
     * honestly instead of claiming success.
     *
     * @return array{sent: bool, reason: string, id: string|null}
     */
    public function send(?string $phone, string $message): array
    {
        $phone = User::normalizePhone((string) $phone);

        if (! $phone) {
            return $this->result(false, 'no_phone');
        }

        if (in_array($phone, (array) config('eskiz.blocklist', []), true)) {
            Log::info("Eskiz: {$phone} is blocklisted, skipped.");

            return $this->result(false, 'blocklisted');
        }

        if (! $this->isConfigured()) {
            // The headline rule: no credentials, no request.
            Log::warning("Eskiz: not configured, SMS to {$phone} not sent. Add ESKIZ_EMAIL and ESKIZ_PASSWORD to .env.");

            return $this->result(false, 'not_configured');
        }

        if (! config('eskiz.enabled', true)) {
            Log::info("Eskiz: disabled (ESKIZ_ENABLED=false), SMS to {$phone} not sent.");

            return $this->result(false, 'disabled');
        }

        if (config('eskiz.dry_run', false)) {
            Log::info("Eskiz DRY RUN -> {$phone}: {$message}");

            return $this->result(false, 'dry_run');
        }

        return $this->dispatch($phone, $message, false);
    }

    /**
     * Backwards-compatible wrapper — older callers expect void and pass a
     * retry flag positionally.
     */
    public function sendMessage($phone, $message, $retry = false): void
    {
        $this->send($phone, (string) $message);
    }

    /**
     * The actual HTTP call, with a single re-auth retry on 401.
     *
     * @return array{sent: bool, reason: string, id: string|null}
     */
    private function dispatch(string $phone, string $message, bool $isRetry): array
    {
        $token = $this->getToken($isRetry);

        if (! $token) {
            return $this->result(false, 'auth_failed');
        }

        $payload = [
            'mobile_phone' => $phone,
            'message'      => $message,
            'from'         => (string) config('eskiz.from', '4546'),
        ];

        if ($callback = config('eskiz.callback_url')) {
            $payload['callback_url'] = $callback;
        }

        try {
            $response = Http::timeout(config('eskiz.timeout', 15))
                ->withToken($token)
                ->asForm()
                ->post($this->url('/message/sms/send'), $payload);

            if ($response->successful()) {
                Log::info('Eskiz: sent', ['to' => $phone, 'id' => $response->json('id')]);

                return $this->result(true, 'sent', (string) $response->json('id'));
            }

            // Token expired or revoked — re-authenticate once, then give up.
            if ($response->status() === 401 && ! $isRetry) {
                Cache::forget(config('eskiz.token_cache_key', 'eskiz.token'));

                return $this->dispatch($phone, $message, true);
            }

            Log::error('Eskiz: send failed', [
                'to'     => $phone,
                'status' => $response->status(),
                'body'   => Str::limit($response->body(), 300),
            ]);

            return $this->result(false, 'gateway_error');
        } catch (\Throwable $e) {
            Log::error("Eskiz: send exception to {$phone} — " . $e->getMessage());

            return $this->result(false, 'exception');
        }
    }

    /* ==================================================================
     | Delivery reports
     ================================================================== */

    /**
     * Eskiz POSTs here with the final status of a message.
     * Always answers 200 — a non-2xx makes Eskiz retry forever.
     */
    public function receive(Request $request)
    {
        Log::channel(config('logging.default'))->info('Eskiz callback', $request->all());

        return response()->json(['ok' => true]);
    }

    /* ==================================================================
     | Helpers
     ================================================================== */

    private function url(string $path): string
    {
        return config('eskiz.base_url', 'https://notify.eskiz.uz/api') . $path;
    }

    /**
     * @return array{sent: bool, reason: string, id: string|null}
     */
    private function result(bool $sent, string $reason, ?string $id = null): array
    {
        return ['sent' => $sent, 'reason' => $reason, 'id' => $id];
    }

    /** Uzbek explanation for a `reason` code, for flash messages. */
    public static function reasonLabel(string $reason): string
    {
        return [
            'sent'           => 'yuborildi',
            'no_phone'       => 'telefon raqami yo‘q',
            'blocklisted'    => 'raqam ro‘yxatdan chiqarilgan',
            'not_configured' => 'Eskiz sozlanmagan (.env)',
            'disabled'       => 'yuborish o‘chirilgan',
            'dry_run'        => 'sinov rejimi — faqat logga yozildi',
            'auth_failed'    => 'Eskizga ulanib bo‘lmadi',
            'gateway_error'  => 'operator xatosi',
            'exception'      => 'tarmoq xatosi',
        ][$reason] ?? $reason;
    }
}