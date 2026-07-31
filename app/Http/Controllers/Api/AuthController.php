<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Token authentication for the mobile app (Laravel Sanctum).
 *
 * Login mirrors the web form exactly: a name OR a phone number in any local
 * format, matched against the normalised 998XXXXXXXXX stored on the row.
 *
 * Only teachers and students may sign in here — the mobile app has no admin
 * surface, and letting an admin token exist on a phone would put full
 * financial access one stolen device away.
 */
class AuthController extends ApiController
{
    /** Roles the mobile app serves. */
    private const ALLOWED_ROLES = ['user', 'student'];

    public function login(Request $request)
    {
        $data = $request->validate([
            'login'       => ['required', 'string'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'login.required'    => 'Login kiritilmadi.',
            'password.required' => 'Parol kiritilmadi.',
        ]);

        // Same throttle as the web login, keyed per login+IP.
        $key = 'api-login:' . Str::lower($data['login']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->fail(
                'Juda ko‘p urinish. ' . RateLimiter::availableIn($key) . ' soniyadan so‘ng qayta urining.',
                429
            );
        }

        $normalised = User::normalizePhone($data['login']);

        $user = User::where('name', $data['login'])
            ->orWhere('phone', $data['login'])
            ->when($normalised, fn($q) => $q->orWhere('phone', $normalised))
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key);

            return $this->fail('Login yoki parol noto‘g‘ri.', 401);
        }

        if (! $user->hasAnyRole(self::ALLOWED_ROLES)) {
            return $this->fail(
                'Bu ilovadan faqat o‘qituvchi va talabalar foydalana oladi.',
                403
            );
        }

        // Mirror the web exactly: verification only blocks sign-in when the
        // centre has switched it on. Hardcoding the check here would let a
        // teacher into the website but lock them out of the phone app, which
        // reads as "the app is broken".
        $enforced = (bool) config('grading.require_email_verification')
            && $user->hasAnyRole((array) config('grading.verification_roles', []));

        if ($enforced && ! $user->hasVerifiedEmail()) {
            return $this->fail(
                'Pochta manzilingiz tasdiqlanmagan. Xatdagi havolani bosing.',
                403
            );
        }

        RateLimiter::clear($key);

        $device = $data['device_name'] ?? 'mobile';

        // One token per device name: signing in again from the same handset
        // replaces the old token instead of piling them up forever.
        $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device)->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user'  => $this->profile($user),
        ], 'Xush kelibsiz, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Hisobdan chiqdingiz.');
    }

    public function me(Request $request)
    {
        return $this->ok($this->profile($request->user()));
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user): array
    {
        $role = $user->getRoleNames()->first();

        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'phone'    => $user->phone,
            'email'    => $user->email,
            'photo'    => $user->photo ? asset('storage/' . $user->photo) : null,
            'role'     => $role,
            'role_label' => match ($role) {
                'user'    => "O‘qituvchi",
                'student' => 'Talaba',
                default   => 'Foydalanuvchi',
            },
            // The Flutter router branches on this.
            'is_teacher' => $user->hasRole('user'),
            'is_student' => $user->hasRole('student'),
        ];
    }
}
