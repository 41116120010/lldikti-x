<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::withCount(['users', 'agendas']);

        // Search: Name, Code, Description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('nama_unit', "%{$search}%")
                    ->orWhereLike('kode_unit', "%{$search}%")
                    ->orWhereLike('deskripsi', "%{$search}%");
            });
        }

        // Filter: Status
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Option to fetch all active units for dropdown without pagination
        if ($request->boolean('all')) {
            $units = $query->orderBy('nama_unit', 'asc')->get();
            return response()->json([
                'success' => true,
                'message' => 'Daftar semua unit kerja berhasil diambil.',
                'data' => UnitResource::collection($units),
            ]);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $units = $query->orderBy('nama_unit', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar unit kerja berhasil diambil.',
            'data' => UnitResource::collection($units),
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(StoreUnitRequest $request, UnitService $unitService): JsonResponse
    {
        Gate::authorize('create', Unit::class);

        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $unit = $unitService->createUnit($validated, $isActive);

        return response()->json([
            'success' => true,
            'message' => "Unit kerja '{$unit->nama_unit}' berhasil ditambahkan.",
            'data' => new UnitResource($unit),
        ], 201);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit, Request $request): JsonResponse
    {
        Gate::authorize('view', $unit);

        $unit->loadCount(['users', 'agendas']);

        return response()->json([
            'success' => true,
            'message' => 'Detail unit kerja berhasil diambil.',
            'data' => new UnitResource($unit),
        ]);
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit, UnitService $unitService): JsonResponse
    {
        Gate::authorize('update', $unit);

        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $unitService->updateUnit($unit, $validated, $isActive);

        return response()->json([
            'success' => true,
            'message' => "Unit kerja '{$unit->nama_unit}' berhasil diperbarui.",
            'data' => new UnitResource($unit->fresh()),
        ]);
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit, Request $request, UnitService $unitService): JsonResponse
    {
        Gate::authorize('delete', $unit);

        $namaUnit = $unit->nama_unit;

        try {
            $unitService->deleteUnit($unit);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Unit kerja '{$namaUnit}' berhasil dihapus.",
        ]);
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(Unit $unit, Request $request, UnitService $unitService): JsonResponse
    {
        Gate::authorize('update', $unit);

        $statusLabel = $unitService->toggleStatus($unit);

        return response()->json([
            'success' => true,
            'message' => "Unit kerja '{$unit->nama_unit}' berhasil {$statusLabel}.",
            'data' => new UnitResource($unit->fresh()),
        ]);
    }
}
