<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitService
{
    /**
     * Create a new unit.
     */
    public function createUnit(array $validated, bool $isActive = true): Unit
    {
        $validated['is_active'] = $isActive;

        return DB::transaction(function () use ($validated) {
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
    }

    /**
     * Update an existing unit.
     */
    public function updateUnit(Unit $unit, array $validated, ?bool $isActive = null): Unit
    {
        $oldData = $unit->toArray();

        if ($isActive !== null) {
            $validated['is_active'] = $isActive;
        }

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

        return $unit;
    }

    /**
     * Delete an existing unit safely.
     *
     * @throws \DomainException If unit has associated users or agendas.
     */
    public function deleteUnit(Unit $unit): void
    {
        if ($unit->users()->count() > 0) {
            throw new \DomainException("Tidak dapat menghapus unit kerja '{$unit->nama_unit}' karena masih memiliki {$unit->users()->count()} pegawai terdaftar.");
        }

        if ($unit->agendas()->count() > 0) {
            throw new \DomainException("Tidak dapat menghapus unit kerja '{$unit->nama_unit}' karena masih terhubung dengan riwayat agenda rapat kedinasan. Anda dapat menonaktifkan status unit ini.");
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
    }

    /**
     * Toggle active/inactive status of a unit.
     */
    public function toggleStatus(Unit $unit): string
    {
        return DB::transaction(function () use ($unit) {
            /** @var Unit $lockedUnit */
            $lockedUnit = Unit::whereKey($unit->getKey())->lockForUpdate()->firstOrFail();

            $lockedUnit->is_active = !$lockedUnit->is_active;
            $lockedUnit->save();

            $label = $lockedUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';

            ActivityLogger::log(
                type: 'TOGGLE_UNIT_STATUS',
                description: "Status unit kerja '{$lockedUnit->nama_unit}' diubah menjadi {$label}.",
                targetModel: Unit::class,
                targetId: $lockedUnit->id,
                properties: ['is_active' => $lockedUnit->is_active]
            );

            return $label;
        });
    }
}
