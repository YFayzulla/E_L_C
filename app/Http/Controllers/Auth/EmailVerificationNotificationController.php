<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // TRUE for blank addresses too, so phone-only accounts simply bounce home.
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Hisobingiz allaqachon tasdiqlangan.');
        }

        // The mailer runs synchronously (QUEUE_CONNECTION=sync); an unreachable SMTP
        // host must show an Uzbek message instead of a 500 page.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('EmailVerificationNotificationController@store error: ' . $e->getMessage());

            return back()->with('error', 'Xatni yuborib bo‘lmadi. Keyinroq qayta urinib ko‘ring.');
        }

        return back()->with('success', 'Tasdiqlash xati pochtangizga qayta yuborildi.');
    }
}
