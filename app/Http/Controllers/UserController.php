<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users scoped by role and unit.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $currentUser = Auth::user();
        $query = User::with('unit');

        // Scoping: Admin Unit is restricted to their own unit only
        if ($currentUser->isAdmin()) {
            $query->where('unit_id', $currentUser->unit_id);
        }

        // Search by Name, NIP, Username, or Email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($unitId = $request->input('unit_id')) {
            if ($currentUser->isAdministrator()) {
                $query->where('unit_id', $unitId);
            }
        }

        // Role Filter
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $units = $currentUser->isAdministrator() ? Unit::active()->orderBy('nama_unit')->get() : collect();

        return view('users.index', compact('users', 'units', 'currentUser'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        $currentUser = Auth::user();
        $units = $currentUser->isAdministrator() 
            ? Unit::active()->orderBy('nama_unit')->get() 
            : Unit::where('id', $currentUser->unit_id)->get();

        return view('users.create', compact('units', 'currentUser'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $currentUser = Auth::user();
        $validated = $request->validated();

        // Enforce unit_id for Admin Unit
        if ($currentUser->isAdmin()) {
            $validated['unit_id'] = $currentUser->unit_id;
            if ($validated['role'] === 'administrator') {
                $validated['role'] = 'staff';
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create($validated);

            ActivityLogger::log(
                type: 'CREATE_USER',
                description: "Pengguna baru '{$user->name}' (NIP: {$user->nip}, Role: {$user->role}) berhasil didaftarkan.",
                targetModel: User::class,
                targetId: $user->id,
                properties: $user->only(['name', 'nip', 'username', 'email', 'role', 'unit_id', 'is_active'])
            );

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$user->name}' berhasil ditambahkan.");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $currentUser = Auth::user();
        $units = $currentUser->isAdministrator() 
            ? Unit::active()->orderBy('nama_unit')->get() 
            : Unit::where('id', $currentUser->unit_id)->get();

        $recentAttendances = $user->attendances()
            ->with('agenda')
            ->latest('signed_at')
            ->paginate(5, ['*'], 'page_attendances')
            ->withQueryString();

        return view('users.edit', compact('user', 'units', 'currentUser', 'recentAttendances'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $currentUser = Auth::user();
        $oldData = $user->only(['name', 'nip', 'username', 'email', 'role', 'unit_id', 'is_active', 'phone']);
        $validated = $request->validated();

        // If password is not provided, keep current password
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Scoping for Admin Unit
        if ($currentUser->isAdmin()) {
            $validated['unit_id'] = $currentUser->unit_id;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($user, $validated, $oldData) {
            $user->update($validated);

            ActivityLogger::log(
                type: 'UPDATE_USER',
                description: "Data pengguna '{$user->name}' (NIP: {$user->nip}) diperbarui.",
                targetModel: User::class,
                targetId: $user->id,
                properties: ['old' => $oldData, 'new' => $user->only(array_keys($oldData))]
            );
        });

        return redirect()->route('admin.users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        // Check if user has attendance history
        if ($user->attendances()->count() > 0) {
            return back()->with('error', "Pengguna '{$user->name}' memiliki riwayat presensi rapat kedinasan. Non-aktifkan akun alih-alih menghapusnya.");
        }

        $name = $user->name;
        $nip = $user->nip;
        $userId = $user->id;

        DB::transaction(function () use ($user, $name, $nip, $userId) {
            $user->delete();

            ActivityLogger::log(
                type: 'DELETE_USER',
                description: "Pengguna '{$name}' (NIP: {$nip}, ID: {$userId}) dihapus dari sistem.",
                targetModel: User::class,
                targetId: $userId
            );
        });

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle active/inactive user account status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $statusLabel = DB::transaction(function () use ($user) {
            $user->is_active = !$user->is_active;
            $user->save();

            $label = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLogger::log(
                type: 'TOGGLE_USER_STATUS',
                description: "Akun pengguna '{$user->name}' {$label}.",
                targetModel: User::class,
                targetId: $user->id,
                properties: ['is_active' => $user->is_active]
            );

            return $label;
        });

        return back()->with('success', "Akun '{$user->name}' berhasil {$statusLabel}.");
    }
}
