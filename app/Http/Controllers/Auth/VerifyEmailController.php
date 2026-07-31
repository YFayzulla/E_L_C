<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * There is no `home` route in this application — everything lands on `dashboard`,
     * which fans out per role. The old `?verified=1` query string was never read by
     * any view, so the outcome is reported with a normal Uzbek flash instead.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Pochta manzilingiz allaqachon tasdiqlangan.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Pochta manzilingiz muvaffaqiyatli tasdiqlandi.');
    }
}
