<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile edit form.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $user->load('unit');

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information and password.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];

        $isPasswordChanged = false;
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
            $isPasswordChanged = true;
        }

        DB::transaction(function () use ($user, $updateData, $isPasswordChanged) {
            $user->update($updateData);

            // Activity log audit trail
            $description = $isPasswordChanged
                ? "Pengguna {$user->name} memperbarui profil dan kata sandi akun"
                : "Pengguna {$user->name} memperbarui informasi profil akun";

            ActivityLogger::log(
                type: 'UPDATE_PROFILE',
                description: $description,
                targetModel: User::class,
                targetId: $user->id,
                properties: [
                    'updated_fields' => array_keys($updateData),
                    'password_changed' => $isPasswordChanged,
                ]
            );
        });

        return redirect()->route('profile.edit')->with('success', 'Profil akun Anda berhasil diperbarui.');
    }

    /**
     * Display the authenticated user's activity logs.
     */
    public function logs(Request $request): View
    {
        $user = Auth::user();
        $query = ActivityLog::where('user_id', $user->id)->latest('id');

        // Filter by Activity Type
        if ($type = $request->input('type')) {
            $query->where('activity_type', $type);
        }

        // Filter by Date Range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Search in description or IP
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(10)->withQueryString();
        $activityTypes = ActivityLog::where('user_id', $user->id)->distinct()->pluck('activity_type');

        return view('profile.logs', compact('user', 'logs', 'activityTypes'));
    }
}
