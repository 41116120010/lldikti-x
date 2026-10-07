<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\ActivityLogger;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Display a listing of the units with associated user and agenda statistics.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Unit::class);

        $query = Unit::withCount(['users', 'agendas']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_unit', 'like', "%{$search}%")
                  ->orWhere('kode_unit', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $units = $query->orderBy('nama_unit', 'asc')->paginate(10)->withQueryString();

        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new unit.
     */
    public function create(): View
    {
        Gate::authorize('create', Unit::class);

        return view('units.create');
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(StoreUnitRequest $request, UnitService $unitService): RedirectResponse
    {
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $unit = $unitService->createUnit($validated, $isActive);

        return redirect()->route('admin.units.index')
            ->with('success', "Unit kerja '{$unit->nama_unit}' berhasil ditambahkan.");
    }

    /**
     * Show the form for editing the specified unit.
     */
    public function edit(Unit $unit): View
    {
        Gate::authorize('update', $unit);

        $unit->loadCount(['users', 'agendas']);

        $unitUsers = $unit->users()
            ->orderBy('name', 'asc')
            ->paginate(5, ['*'], 'page_users')
            ->withQueryString();

        return view('units.edit', compact('unit', 'unitUsers'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit, UnitService $unitService): RedirectResponse
    {
        $validated = $request->validated();
        $isActive = $request->boolean('is_active', true);

        $unitService->updateUnit($unit, $validated, $isActive);

        return redirect()->route('admin.units.index')
            ->with('success', "Unit kerja '{$unit->nama_unit}' berhasil diperbarui.");
    }

    /**
     * Remove the specified unit from storage.
     * Prevents deletion if the unit has associated users or meeting agendas.
     */
    public function destroy(Unit $unit, UnitService $unitService): RedirectResponse
    {
        Gate::authorize('delete', $unit);
        $namaUnit = $unit->nama_unit;

        try {
            $unitService->deleteUnit($unit);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.units.index')
            ->with('success', "Unit kerja '{$namaUnit}' berhasil dihapus.");
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(Unit $unit, UnitService $unitService): RedirectResponse
    {
        Gate::authorize('update', $unit);

        $statusLabel = $unitService->toggleStatus($unit);

        return back()->with('success', "Unit kerja '{$unit->nama_unit}' berhasil {$statusLabel}.");
    }
}
