<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    private const INACTIVE_MESSAGE = 'Akun Anda berstatus non-aktif. Silakan hubungi Administrator Kepegawaian LLDIKTI Wilayah X.';

    /**
     * Reject or terminate requests for deactivated user accounts across Web and API guards.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $token = method_exists($user, 'currentAccessToken')
                    ? $user->currentAccessToken()
                    : null;

                if ($token && method_exists($token, 'delete')) {
                    $token->delete();
                }

                return response()->json([
                    'success' => false,
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => self::INACTIVE_MESSAGE,
                ], 403);
            }

            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->with('error', self::INACTIVE_MESSAGE)
                ->withErrors(['login' => self::INACTIVE_MESSAGE]);
        }

        return $next($request);
    }
}
