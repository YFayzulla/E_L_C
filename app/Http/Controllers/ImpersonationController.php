<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Sign in as" — lets an admin see the app exactly as a student or teacher does.
 *
 * Rules, all enforced here rather than in middleware:
 *  - only an admin may start it, and only ONE level deep;
 *  - an admin may never be impersonated (no privilege laundering);
 *  - the original admin id lives in the session, so returning is a click;
 *  - both directions are written to the log, because this is an audit-relevant
 *    action: everything done while impersonating is attributed to the target.
 *
 * The session key is deliberately checked before every switch so a second
 * `/impersonate/x` while already impersonating cannot chain identities.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, int $user)
    {
        $admin = $request->user();

        abort_unless($admin && $admin->hasRole('admin'), 403);

        // Already impersonating: refuse rather than chain.
        abort_if(
            $request->session()->has(self::SESSION_KEY),
            403,
            'Avval joriy seansdan qayting.'
        );

        $target = User::inCurrentCentre()->findOrFail($user);

        abort_if($target->id === $admin->id, 400, 'O‘zingizga kira olmaysiz.');

        abort_if(
            $target->hasRole('admin'),
            403,
            'Administrator hisobiga kirib bo‘lmaydi.'
        );

        abort_unless(
            $target->hasAnyRole(['student', 'user', 'parent']),
            403,
            'Bu hisobga kirib bo‘lmaydi.'
        );

        Log::warning(sprintf(
            'Impersonation START: admin #%d (%s) -> user #%d (%s) from %s',
            $admin->id, $admin->name, $target->id, $target->name, $request->ip()
        ));

        $request->session()->put(self::SESSION_KEY, $admin->id);

        Auth::login($target);

        // Session id changes, but the impersonator key is carried over.
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $admin->id);

        return redirect()->route('dashboard')
            ->with('success', $target->name . ' hisobiga kirdingiz.');
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->get(self::SESSION_KEY);

        abort_unless($adminId, 403, 'Siz boshqa hisobda emassiz.');

        // Ataylab markazga cheklanmagan: id URL'dan emas, sessiyadan keladi va
        // impersonate boshlanganda allaqachon tekshirilgan. Bu yerga
        // inCurrentCentre() qo'yilsa, hech qaysi markazga a'zo bo'lmagan
        // super-admin o'z hisobiga qaytolmay qolardi.
        $admin = User::find($adminId);

        // The admin was deleted mid-session: fall back to a clean logout rather
        // than leaving the visitor stranded inside somebody else's account.
        if (! $admin || ! $admin->hasRole('admin')) {
            $request->session()->forget(self::SESSION_KEY);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Log::warning(sprintf(
            'Impersonation STOP: back to admin #%d (%s) from user #%d',
            $admin->id, $admin->name, $request->user()?->id ?? 0
        ));

        $request->session()->forget(self::SESSION_KEY);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'O‘z hisobingizga qaytdingiz.');
    }
}
