<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Two things this used to get wrong:
     *  - the phone was stored exactly as typed, while every other write path stores
     *    the 998-prefixed form, so a self-registered account could never sign in;
     *  - no role was assigned, which dropped the new account into the app with zero
     *    permissions. Self-registration now always produces a `student`.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Compare and store the same shape the rest of the app uses: 998XXXXXXXXX.
        $request->merge([
            'phone' => User::normalizePhone($request->input('phone')),
            'email' => $request->filled('email') ? trim((string) $request->input('email')) : null,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits:12', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:191', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'Ism familiyani kiriting.',
            'phone.required' => 'Telefon raqamini kiriting.',
            'phone.digits' => 'Telefon raqamini to‘liq kiriting (9 ta raqam).',
            'phone.unique' => 'Bu telefon raqami allaqachon ro‘yxatdan o‘tgan.',
            'email.email' => 'Pochta manzili noto‘g‘ri kiritilgan.',
            'email.unique' => 'Bu pochta manzili boshqa hisobga biriktirilgan.',
            'password.required' => 'Parolni kiriting.',
            'password.confirmed' => 'Parol tasdiqlanmadi — ikkala maydon bir xil bo‘lishi kerak.',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email ?: null,
                'password' => Hash::make($request->password),
            ]);

            // Without a role the account reaches the dashboard with no permissions.
            $user->assignRole('student');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RegisteredUserController@store error: ' . $e->getMessage());

            // Never flash the plaintext password back into the session.
            return redirect()->back()->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Ro‘yxatdan o‘tishda xatolik yuz berdi. Keyinroq qayta urinib ko‘ring.');
        }

        // Registered => SendEmailVerificationNotification (see EventServiceProvider).
        // Fired AFTER the commit and swallowed on failure: the mailer is synchronous
        // and an unreachable SMTP host must not undo a completed registration.
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('RegisteredUserController@store mail error: ' . $e->getMessage());
        }

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Hisobingiz yaratildi. Xush kelibsiz!');
    }
}
