<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
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
                targetId: $user->id
            );
        });

        return redirect()->route('profile.edit')->with('success', 'Profil akun Anda berhasil diperbarui.');
    }
}
