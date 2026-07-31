<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks students and teachers who have not confirmed their e-mail address.
 *
 * Two guards keep this from bricking the centre:
 *
 *  1. It does nothing at all unless config('grading.require_email_verification')
 *     is true (env REQUIRE_EMAIL_VERIFICATION). Ships OFF so the addresses can
 *     be collected first.
 *  2. An account with NO e-mail address is never blocked — User::hasVerifiedEmail()
 *     returns true for a blank address. Only accounts that actually have an
 *     address can be unverified.
 *
 * Admins are never blocked, so there is always a way back into the app.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('grading.require_email_verification')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $enforcedRoles = (array) config('grading.verification_roles', []);

        if (! $user->hasAnyRole($enforcedRoles)) {
            return $next($request);
        }

        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Pochta manzilingiz tasdiqlanmagan.');
        }

        return redirect()->route('verification.notice');
    }
}
