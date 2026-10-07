<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user.
     */
    public function createUser(User $creator, array $validated, bool $isActive = true): User
    {
        // Enforce unit_id for Admin Unit
        if ($creator->isAdmin()) {
            $validated['unit_id'] = $creator->unit_id;
            if (($validated['role'] ?? '') === 'administrator') {
                $validated['role'] = 'staff';
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        $validated['is_active'] = $isActive;

        return DB::transaction(function () use ($validated) {
            $user = User::create($validated);

            ActivityLogger::log(
                type: 'CREATE_USER',
                description: "Pengguna baru '{$user->name}' (NIP: {$user->nip}, Role: {$user->role_label}) berhasil didaftarkan.",
                targetModel: User::class,
                targetId: $user->id,
                properties: $user->only(['name', 'nip', 'username', 'email', 'role', 'unit_id', 'is_active'])
            );

            return $user;
        });
    }

    /**
     * Update an existing user.
     */
    public function updateUser(User $user, User $actor, array $validated, ?bool $isActive = null): User
    {
        $oldData = $user->only(['name', 'nip', 'username', 'email', 'role', 'unit_id', 'is_active', 'phone']);
        $passwordChanged = false;

        // If password is not provided, keep current password
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
            $passwordChanged = true;
        }

        // Scoping for Admin Unit
        if ($actor->isAdmin()) {
            $validated['unit_id'] = $actor->unit_id;
        }

        if ($isActive !== null) {
            $validated['is_active'] = $isActive;
        }

        $deactivated = isset($validated['is_active']) && !$validated['is_active'];

        DB::transaction(function () use ($user, $validated, $oldData, $passwordChanged, $deactivated) {
            $user->update($validated);

            // Revoke active API tokens on critical changes (password change or deactivation)
            if (($passwordChanged || $deactivated) && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            ActivityLogger::log(
                type: 'UPDATE_USER',
                description: "Data pengguna '{$user->name}' (NIP: {$user->nip}) diperbarui.",
                targetModel: User::class,
                targetId: $user->id,
                properties: ['old' => $oldData, 'new' => $user->only(array_keys($oldData))]
            );
        });

        return $user;
    }

    /**
     * Delete an existing user safely.
     *
     * @throws \DomainException If user has attendances or created agendas.
     */
    public function deleteUser(User $user, User $actor): void
    {
        if ($user->attendances()->exists()) {
            throw new \DomainException("Pengguna '{$user->name}' memiliki riwayat presensi rapat kedinasan. Non-aktifkan akun alih-alih menghapusnya demi integritas data arsip.");
        }

        if ($user->createdAgendas()->exists()) {
            throw new \DomainException("Pengguna '{$user->name}' tercatat sebagai pembuat agenda rapat kedinasan. Non-aktifkan akun alih-alih menghapusnya agar riwayat agenda rapat tidak terhapus.");
        }

        $name = $user->name;
        $nip = $user->nip;
        $userId = $user->id;

        DB::transaction(function () use ($user, $name, $nip, $userId) {
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            $user->delete();

            ActivityLogger::log(
                type: 'DELETE_USER',
                description: "Pengguna '{$name}' (NIP: {$nip}, ID: {$userId}) dihapus dari sistem.",
                targetModel: User::class,
                targetId: $userId
            );
        });
    }

    /**
     * Toggle active/inactive user status.
     *
     * @throws \DomainException If trying to deactivate own account.
     */
    public function toggleStatus(User $user, User $actor): string
    {
        if ($user->id === $actor->id) {
            throw new \DomainException('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        return DB::transaction(function () use ($user) {
            /** @var User $lockedUser */
            $lockedUser = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $lockedUser->is_active = !$lockedUser->is_active;
            $lockedUser->save();

            // Revoke tokens if deactivated
            if (!$lockedUser->is_active && method_exists($lockedUser, 'tokens')) {
                $lockedUser->tokens()->delete();
            }

            $label = $lockedUser->is_active ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLogger::log(
                type: 'TOGGLE_USER_STATUS',
                description: "Akun pengguna '{$lockedUser->name}' {$label}.",
                targetModel: User::class,
                targetId: $lockedUser->id,
                properties: ['is_active' => $lockedUser->is_active]
            );

            return $label;
        });
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(User $user, array $validated): User
    {
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

            if ($isPasswordChanged && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

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

        return $user;
    }
}
