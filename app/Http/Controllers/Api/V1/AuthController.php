<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Authenticate user via multi-identifier (NIP/Username) and issue Bearer token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->resolveUser();

        $deviceName = (string) $request->input('device_name', 'Mobile App');
        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLogger::log(
            type: 'AUTH_API_LOGIN',
            description: "Pengguna {$user->name} berhasil login melalui API Mobile ({$deviceName}).",
            targetModel: get_class($user),
            targetId: $user->id,
            properties: [
                'device_name' => $deviceName,
                'ip' => $request->ip(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in_days' => 30,
                'user' => new UserResource($user->load('unit')),
            ],
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('unit');

        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Revoke active mobile token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        ActivityLogger::log(
            type: 'AUTH_API_LOGOUT',
            description: "Pengguna {$user->name} berhasil logout dari API Mobile.",
            targetModel: get_class($user),
            targetId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dan token dicabut.',
        ]);
    }
}
