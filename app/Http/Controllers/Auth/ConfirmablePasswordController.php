<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     *
     * Breeze ships this as `Auth::guard('web')->validate(['email' => ..., 'password' => ...])`,
     * which cannot work here: this application authenticates by phone and almost every
     * account has a NULL e-mail address, so the credential lookup always failed and the
     * password could never be confirmed. We hash-check the signed-in user instead.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Parolni kiriting.',
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Parol noto‘g‘ri.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard'));
    }
}
