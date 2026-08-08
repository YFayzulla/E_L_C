<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Centre;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended($this->destinationFor($request->user()));
    }

    /**
     * Kirgandan keyin qayerga.
     *
     * Markaz subdomenida — o'sha markazning paneli (a'zolik LoginRequest da
     * allaqachon tekshirilgan). Apexda esa markaz yo'q: platforma egasi o'z
     * paneliga, qolganlar markaz tanlash oynasiga tushadi, va u bitta
     * markaz bo'lsa to'g'ridan-to'g'ri o'sha yerga o'tkazadi.
     */
    private function destinationFor(?User $user): string
    {
        if (Centre::current() !== null) {
            return RouteServiceProvider::HOME;
        }

        if ($user?->is_super_admin) {
            return route('super.centres.index');
        }

        return route('centres.choose');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
