<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * NOTE: reachable only for accounts that carry an e-mail address. Phone-only
     * accounts cannot reset by e-mail and are handled by the administrator.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.required' => 'Pochta manzilini kiriting.',
            'email.email' => 'Pochta manzili noto‘g‘ri kiritilgan.',
            'password.required' => 'Yangi parolni kiriting.',
            'password.confirmed' => 'Parol tasdiqlanmadi — ikkala maydon bir xil bo‘lishi kerak.',
        ]);

        // Pre-check so a wrong address gets an Uzbek sentence rather than the
        // broker's English "We can't find a user with that email address."
        //
        // inCurrentCentre(): havola qaysi markazda so'ralgan bo'lsa, o'sha
        // markazda tugallanishi kerak. Aks holda boshqa subdomenga
        // ko'chirilgan havola bilan parol almashtirib bo'lardi.
        if (! User::inCurrentCentre()->where('email', $request->input('email'))->exists()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Bu manzil bo‘yicha hisob topilmadi.']);
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );
        } catch (\Throwable $e) {
            Log::error('NewPasswordController@store error: ' . $e->getMessage());

            return back()->withInput($request->only('email'))
                ->with('error', 'Parolni yangilashda xatolik yuz berdi. Keyinroq qayta urinib ko‘ring.');
        }

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Parol yangilandi. Endi yangi parol bilan kiring.');
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
            Password::INVALID_TOKEN => 'Havola eskirgan yoki noto‘g‘ri. Yangi havola so‘rang.',
            Password::INVALID_USER => 'Bu manzil bo‘yicha hisob topilmadi.',
            Password::RESET_THROTTLED => 'Juda tez-tez so‘ralmoqda. Bir necha daqiqadan so‘ng urinib ko‘ring.',
            default => 'Parolni yangilab bo‘lmadi. Keyinroq qayta urinib ko‘ring.',
        };
    }
}
