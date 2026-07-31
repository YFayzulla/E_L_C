<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * NOTE: only accounts that carry an e-mail address can reset this way. Most
     * accounts in this centre are phone-only (created by an administrator with a
     * blank address) — those users must ask the administrator for a new password,
     * which is what the copy on the form tells them.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Pochta manzilini kiriting.',
            'email.email' => 'Pochta manzili noto‘g‘ri kiritilgan.',
        ]);

        // Pre-check so the user gets an Uzbek sentence instead of the broker's
        // English "We can't find a user with that email address."
        if (! User::where('email', $request->input('email'))->exists()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Bu manzil bo‘yicha hisob topilmadi.']);
        }

        // The mailer is synchronous (QUEUE_CONNECTION=sync); a dead SMTP host would
        // otherwise surface as a 500 page.
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('PasswordResetLinkController@store error: ' . $e->getMessage());

            return back()->withInput($request->only('email'))
                ->with('error', 'Xatni yuborib bo‘lmadi. Keyinroq qayta urinib ko‘ring.');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Parolni tiklash havolasi pochtangizga yuborildi.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => $this->uzbekStatus($status)]);
    }

    /**
     * Translate a broker status key into Uzbek.
     */
    private function uzbekStatus(string $status): string
    {
        return match ($status) {
            Password::RESET_THROTTLED => 'Juda tez-tez so‘ralmoqda. Bir necha daqiqadan so‘ng urinib ko‘ring.',
            Password::INVALID_USER => 'Bu manzil bo‘yicha hisob topilmadi.',
            default => 'Havolani yuborib bo‘lmadi. Keyinroq qayta urinib ko‘ring.',
        };
    }
}
