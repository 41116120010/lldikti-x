<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\ActivityLogger;
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
    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $unit = DB::transaction(function () use ($validated) {
            $unit = Unit::create($validated);

            ActivityLogger::log(
                type: 'CREATE_UNIT',
                description: "Unit kerja baru '{$unit->nama_unit}' ({$unit->kode_unit}) berhasil ditambahkan.",
                targetModel: Unit::class,
                targetId: $unit->id,
                properties: $unit->toArray()
            );

            return $unit;
        });

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

        return view('units.edit', compact('unit'));
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $oldData = $unit->toArray();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($unit, $validated, $oldData) {
            $unit->update($validated);

            ActivityLogger::log(
                type: 'UPDATE_UNIT',
                description: "Data unit kerja '{$unit->nama_unit}' ({$unit->kode_unit}) diperbarui.",
                targetModel: Unit::class,
                targetId: $unit->id,
                properties: ['old' => $oldData, 'new' => $unit->toArray()]
            );
        });

        return redirect()->route('admin.units.index')
            ->with('success', "Unit kerja '{$unit->nama_unit}' berhasil diperbarui.");
    }

    /**
     * Remove the specified unit from storage.
     * Prevents deletion if the unit has associated users or meeting agendas.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        if ($unit->users()->count() > 0) {
            return back()->with('error', "Tidak dapat menghapus unit kerja '{$unit->nama_unit}' karena masih memiliki {$unit->users()->count()} pegawai terdaftar.");
        }

        if ($unit->agendas()->count() > 0) {
            return back()->with('error', "Tidak dapat menghapus unit kerja '{$unit->nama_unit}' karena masih terhubung dengan riwayat agenda rapat kedinasan. Anda dapat menonaktifkan status unit ini.");
        }

        $namaUnit = $unit->nama_unit;
        $unitId = $unit->id;

        DB::transaction(function () use ($unit, $namaUnit, $unitId) {
            $unit->delete();

            ActivityLogger::log(
                type: 'DELETE_UNIT',
                description: "Unit kerja '{$namaUnit}' (ID: {$unitId}) dihapus.",
                targetModel: Unit::class,
                targetId: $unitId
            );
        });

        return redirect()->route('admin.units.index')
            ->with('success', "Unit kerja '{$namaUnit}' berhasil dihapus.");
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(Unit $unit): RedirectResponse
    {
        Gate::authorize('update', $unit);

        $statusLabel = DB::transaction(function () use ($unit) {
            $unit->is_active = !$unit->is_active;
            $unit->save();

            $label = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLogger::log(
                type: 'TOGGLE_UNIT_STATUS',
                description: "Status unit kerja '{$unit->nama_unit}' diubah menjadi {$label}.",
                targetModel: Unit::class,
                targetId: $unit->id,
                properties: ['is_active' => $unit->is_active]
            );

            return $label;
        });

        return back()->with('success', "Unit kerja '{$unit->nama_unit}' berhasil {$statusLabel}.");
    }
}
