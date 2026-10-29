<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Agenda;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Retrieve active ASN employees across all units for Agenda Pimpinan & Notulis selection.
     * Accessible by Administrator and Admin Unit (mirrors Web AgendaController::create/edit/show).
     */
    public function candidates(Request $request): JsonResponse
    {
        Gate::authorize('create', Agenda::class);

        $query = User::where('is_active', true)->with('unit');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nip', "%{$search}%")
                    ->orWhereLike('jabatan', "%{$search}%");
            });
        }

        if ($unitId = $request->query('unit_id')) {
            $query->where('unit_id', (int) $unitId);
        }

        $candidates = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kandidat pejabat rapat (Pimpinan & Notulis) lintas unit berhasil diambil.',
            'data' => UserResource::collection($candidates),
            'meta' => [
                'total' => $candidates->count(),
            ],
        ]);
    }

    /**
     * Display a listing of users scoped by role and unit.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('for_agenda_assignment')) {
            return $this->candidates($request);
        }

        Gate::authorize('viewAny', User::class);

        $currentUser = $request->user();
        $query = User::with('unit');

        // Scoping: Admin Unit is restricted to their own unit only
        if ($currentUser->isAdmin()) {
            $query->where('unit_id', $currentUser->unit_id);
        }

        // Search: Name, NIP, Username, or Email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nip', "%{$search}%")
                    ->orWhereLike('username', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            });
        }

        // Filter: Unit (Administrator only)
        if ($unitId = $request->input('unit_id')) {
            if ($currentUser->isAdministrator()) {
                if ($unitId === 'none') {
                    $query->whereNull('unit_id');
                } else {
                    $query->where('unit_id', (int) $unitId);
                }
            }
        }

        // Filter: Role (supports direct enum values or friendly UI labels)
        if ($role = $request->input('role')) {
            $roleLower = strtolower(trim($role));
            $roleMap = [
                'administrator' => 'administrator',
                'admin unit' => 'admin',
                'admin' => 'admin',
                'staff' => 'staff',
                'pegawai' => 'staff',
                'pegawai asn' => 'staff',
            ];
            $targetRole = $roleMap[$roleLower] ?? $roleLower;
            $query->where('role', $targetRole);
        }

        // Filter: Status (supports active/inactive and Indonesian aktif/nonaktif)
        if ($status = $request->input('status')) {
            $statusLower = strtolower(trim($status));
            if (in_array($statusLower, ['active', 'aktif', '1', 'true'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($statusLower, ['inactive', 'nonaktif', 'tidak aktif', '0', 'false'], true)) {
                $query->where('is_active', false);
            }
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        // Telemetry Summary Calculation (scoped by current user permissions)
        $scopedBaseQuery = User::query();
        if ($currentUser->isAdmin()) {
            $scopedBaseQuery->where('unit_id', $currentUser->unit_id);
        }

        $totalUsers = (clone $scopedBaseQuery)->count();
        $activeUsers = (clone $scopedBaseQuery)->where('is_active', true)->count();
        $inactiveUsers = (clone $scopedBaseQuery)->where('is_active', false)->count();
        $adminUsers = (clone $scopedBaseQuery)->whereIn('role', ['administrator', 'admin'])->count();
        $staffUsers = (clone $scopedBaseQuery)->where('role', 'staff')->count();
        $activeUnits = \App\Models\Unit::where('is_active', true)->count();

        // Calculate institutional attendance rate
        $completedAgendas = \App\Models\Agenda::where('status', 'completed')->count();
        if ($currentUser->isAdministrator()) {
            $completedAttendances = \App\Models\Attendance::whereHas('agenda', fn ($q) => $q->where('status', 'completed'))->count();
            $expected = $completedAgendas * max($activeUsers, 1);
            $rate = ($completedAgendas === 0 || $activeUsers === 0)
                ? 100.0
                : round(min(($completedAttendances / max($expected, 1)) * 100, 100.0), 1);
            $rateSubtext = $completedAgendas > 0
                ? "Rata-rata Seluruh ASN ({$completedAgendas} Rapat)"
                : 'Belum ada rapat dinas selesai';
        } else {
            $unitCompletedAttendances = \App\Models\Attendance::whereHas('user', fn ($q) => $q->where('unit_id', $currentUser->unit_id))
                ->whereHas('agenda', fn ($q) => $q->where('status', 'completed'))
                ->count();
            $expected = $completedAgendas * max($totalUsers, 1);
            $rate = ($completedAgendas === 0 || $totalUsers === 0)
                ? 100.0
                : round(min(($unitCompletedAttendances / max($expected, 1)) * 100, 100.0), 1);
            $rateSubtext = $completedAgendas > 0
                ? "Kehadiran Unit ({$totalUsers} Pegawai)"
                : 'Belum ada rapat dinas selesai';
        }

        $summary = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'admin_users' => $adminUsers,
            'staff_users' => $staffUsers,
            'active_units' => $activeUnits,
            'attendance_rate' => $rate,
            'attendance_rate_label' => "{$rate}%",
            'attendance_rate_subtext' => $rateSubtext,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request, UserService $userService): JsonResponse
    {
        $currentUser = $request->user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $user = $userService->createUser($currentUser, $validated, $isActive);
        $user->load('unit');

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$user->name}' berhasil ditambahkan.",
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, Request $request): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => new UserResource($user->load('unit')),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UserService $userService): JsonResponse
    {
        Gate::authorize('update', $user);

        $currentUser = $request->user();
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $userService->updateUser($user, $currentUser, $validated, $isActive);

        return response()->json([
            'success' => true,
            'message' => "Data pengguna '{$user->name}' berhasil diperbarui.",
            'data' => new UserResource($user->fresh()->load('unit')),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user, Request $request, UserService $userService): JsonResponse
    {
        Gate::authorize('delete', $user);

        $name = $user->name;

        try {
            $userService->deleteUser($user, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$name}' berhasil dihapus.",
        ]);
    }

    /**
     * Toggle active/inactive user account status.
     */
    public function toggleStatus(User $user, Request $request, UserService $userService): JsonResponse
    {
        Gate::authorize('update', $user);

        try {
            $statusLabel = $userService->toggleStatus($user, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Akun '{$user->name}' berhasil {$statusLabel}.",
            'data' => new UserResource($user->fresh()->load('unit')),
        ]);
    }
}
