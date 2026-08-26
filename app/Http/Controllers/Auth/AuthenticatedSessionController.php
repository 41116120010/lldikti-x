<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Audit Log
        ActivityLogger::log(
            type: 'AUTH_LOGIN',
            description: "Pengguna {$user->name} ({$user->role}) berhasil masuk ke sistem.",
            targetModel: get_class($user),
            targetId: $user->id,
            user: $user
        );

        return redirect()->intended(route('dashboard'))
            ->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogger::log(
                type: 'AUTH_LOGOUT',
                description: "Pengguna {$user->name} keluar dari sistem.",
                targetModel: get_class($user),
                targetId: $user->id,
                user: $user
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
