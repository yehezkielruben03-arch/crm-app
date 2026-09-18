<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

        // BUGFIX (Routing Leak): 'url.intended' bisa ternodai oleh request latar belakang
        // ke endpoint /api/* (mis. polling notifikasi '/api/notifications/poll') yang dicegat
        // middleware 'auth' saat user masih guest — middleware tersebut menyimpan URL request
        // tsb sebagai intented. Akibatnya redirect()->intended() mendaratkan user ke endpoint
        // JSON (menampilkan teks mentah) alih-alih dashboard. Solusi: injak intended yang
        // mengarah ke '/api/...' dan fallback ke dashboard.
        $intended = $request->session()->get('url.intended');

        if (is_string($intended)) {
            $path = parse_url($intended, PHP_URL_PATH) ?: $intended;

            if (str_starts_with($path, '/api/')) {
                $request->session()->forget('url.intended');
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
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
