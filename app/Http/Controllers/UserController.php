<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserService;
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
                $q->whereLike('name', "%{$search}%")
                  ->orWhereLike('nip', "%{$search}%")
                  ->orWhereLike('username', "%{$search}%")
                  ->orWhereLike('email', "%{$search}%");
            });
        }

        if ($unitId = $request->input('unit_id')) {
            if ($currentUser->isAdministrator()) {
                if ($unitId === 'none') {
                    $query->whereNull('unit_id');
                } else {
                    $query->where('unit_id', $unitId);
                }
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

        // Statistics (scoped by current user role/unit)
        $statsQuery = User::query();
        if ($currentUser->isAdmin()) {
            $statsQuery->where('unit_id', $currentUser->unit_id);
        }
        $userStats = $statsQuery->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN is_active THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN NOT is_active THEN 1 ELSE 0 END), 0) as inactive
        ')->first();

        return view('users.index', compact('users', 'units', 'currentUser', 'userStats'));
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
    public function store(StoreUserRequest $request, UserService $userService): RedirectResponse
    {
        $currentUser = Auth::user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $user = $userService->createUser($currentUser, $validated, $isActive);

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
    public function update(UpdateUserRequest $request, User $user, UserService $userService): RedirectResponse
    {
        Gate::authorize('update', $user);

        $currentUser = Auth::user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $userService->updateUser($user, $currentUser, $validated, $isActive);

        return redirect()->route('admin.users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user, UserService $userService): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $name = $user->name;

        try {
            $userService->deleteUser($user, Auth::user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle active/inactive user account status.
     */
    public function toggleStatus(User $user, UserService $userService): RedirectResponse
    {
        Gate::authorize('update', $user);

        try {
            $statusLabel = $userService->toggleStatus($user, Auth::user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Akun '{$user->name}' berhasil {$statusLabel}.");
    }
}
