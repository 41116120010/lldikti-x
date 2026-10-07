<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgendaService
{
    public function __construct(
        protected ReportConfigService $reportConfigService
    ) {}

    /**
     * Create an agenda under room advisory lock and database transaction.
     */
    public function createAgenda(User $creator, array $validated, bool $isAllUnits, ?UploadedFile $suratEdaran = null): Agenda
    {
        /** @var Agenda $agenda */
        $agenda = AgendaConflictService::runUnderRoomLock(
            data: $validated,
            ignoreAgendaId: null,
            user: $creator,
            callback: function () use ($creator, $validated, $isAllUnits, $suratEdaran) {
                return DB::transaction(function () use ($creator, $validated, $isAllUnits, $suratEdaran) {
                    $waktuMulai = $validated['waktu_mulai'];
                    $waktuSelesai = !empty($validated['waktu_selesai']) ? $validated['waktu_selesai'] : null;

                    $agendaData = [
                        'created_by' => $creator->id,
                        'pimpinan_id' => $validated['pimpinan_id'] ?? null,
                        'notulis_id' => $validated['notulis_id'] ?? null,
                        'judul_rapat' => $validated['judul_rapat'],
                        'slug' => Str::slug($validated['judul_rapat']) . '-' . Str::lower(Str::random(6)),
                        'jenis_rapat' => $validated['jenis_rapat'],
                        'tipe_rapat' => $validated['tipe_rapat'],
                        'lokasi_ruang' => $validated['lokasi_ruang'] ?? null,
                        'link_meeting' => $validated['link_meeting'] ?? null,
                        'waktu_mulai' => $waktuMulai,
                        'waktu_selesai' => $waktuSelesai,
                        'is_all_units' => $isAllUnits,
                        'status' => $validated['status'] ?? 'scheduled',
                    ];

                    if ($suratEdaran) {
                        $path = $suratEdaran->store('surat_edaran', 'public');
                        $agendaData['surat_edaran_path'] = $path;
                        $agendaData['surat_edaran_name'] = $suratEdaran->getClientOriginalName();
                    }

                    $agenda = Agenda::create($agendaData);

                    // Sync units
                    if ($isAllUnits) {
                        $allUnitIds = Unit::active()->pluck('id')->toArray();
                        $agenda->units()->sync($allUnitIds);
                    } else {
                        $unitIds = $validated['unit_ids'] ?? ($validated['units'] ?? []);
                        if ($creator->isAdmin() && empty($unitIds)) {
                            $unitIds = [$creator->unit_id];
                        }
                        $agenda->units()->sync($unitIds);
                    }

                    return $agenda;
                });
            }
        );

        ActivityLogger::log(
            type: 'CREATE_AGENDA',
            description: "Agenda rapat baru '{$agenda->judul_rapat}' ({$agenda->jenis_rapat}, {$agenda->tipe_rapat}) berhasil dibuat.",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: $agenda->only(['judul_rapat', 'jenis_rapat', 'tipe_rapat', 'waktu_mulai', 'waktu_selesai', 'is_all_units'])
        );

        return $agenda;
    }

    /**
     * Update an agenda under room advisory lock and database transaction.
     */
    public function updateAgenda(Agenda $agenda, User $actor, array $validated, bool $isAllUnits, ?UploadedFile $suratEdaran = null): Agenda
    {
        $oldData = $agenda->toArray();
        $newCircularPath = null;
        $supersededCircularPath = null;

        if ($suratEdaran) {
            $newCircularPath = $suratEdaran->store('circulars', 'public');
            $supersededCircularPath = $agenda->surat_edaran_path;
        }

        AgendaConflictService::runUnderRoomLock(
            data: $validated,
            ignoreAgendaId: (int) $agenda->getKey(),
            user: $actor,
            callback: function () use ($validated, $agenda, $oldData, $newCircularPath, $isAllUnits) {
                DB::transaction(function () use ($validated, $agenda, $oldData, $newCircularPath, $isAllUnits) {
                    $waktuMulai = $validated['waktu_mulai'];
                    $waktuSelesai = !empty($validated['waktu_selesai']) ? $validated['waktu_selesai'] : null;

                    $updateData = [
                        'pimpinan_id' => $validated['pimpinan_id'] ?? null,
                        'notulis_id' => $validated['notulis_id'] ?? null,
                        'judul_rapat' => $validated['judul_rapat'],
                        'jenis_rapat' => $validated['jenis_rapat'],
                        'tipe_rapat' => $validated['tipe_rapat'],
                        'lokasi_ruang' => $validated['lokasi_ruang'] ?? null,
                        'link_meeting' => $validated['link_meeting'] ?? null,
                        'waktu_mulai' => $waktuMulai,
                        'waktu_selesai' => $waktuSelesai,
                        'is_all_units' => $isAllUnits,
                        'status' => $validated['status'] ?? $agenda->status,
                    ];

                    if ($newCircularPath !== null) {
                        $updateData['surat_edaran_path'] = $newCircularPath;
                    }

                    $agenda->update($updateData);

                    if ($isAllUnits) {
                        $allUnitIds = Unit::active()->pluck('id')->toArray();
                        $agenda->units()->sync($allUnitIds);
                    } elseif (!empty($validated['unit_ids'])) {
                        $agenda->units()->sync($validated['unit_ids']);
                    }

                    ActivityLogger::log(
                        type: 'UPDATE_AGENDA',
                        description: "Agenda rapat '{$agenda->judul_rapat}' diperbarui.",
                        targetModel: Agenda::class,
                        targetId: $agenda->id,
                        properties: ['old' => $oldData, 'new' => $agenda->toArray()]
                    );
                });
            }
        );

        if ($supersededCircularPath && $supersededCircularPath !== $newCircularPath) {
            Storage::disk('public')->delete($supersededCircularPath);
        }

        return $agenda;
    }

    /**
     * Delete an agenda safely.
     *
     * @throws \DomainException If agenda has attendances.
     */
    public function deleteAgenda(Agenda $agenda, User $actor): void
    {
        $judul = $agenda->judul_rapat;
        $id = $agenda->id;

        if ($agenda->attendances()->exists()) {
            throw new \DomainException("Agenda rapat '{$judul}' telah memiliki catatan presensi kehadiran pegawai. Agenda tidak dapat dihapus demi integritas arsip kegiatan. Ubah status agenda menjadi 'Dibatalkan' jika agenda batal terlaksana.");
        }

        $documentations = $agenda->documentations()->pluck('file_path')->all();
        $attendances = $agenda->attendances()->get(['selfie_path', 'signature_path']);

        $filesToDelete = array_values(array_filter(array_merge(
            [$agenda->surat_edaran_path],
            [$agenda->report_config['custom_logo_path'] ?? null],
            $documentations,
            $attendances->pluck('selfie_path')->all(),
            $attendances->pluck('signature_path')->all(),
        )));

        $agendaId = (int) $agenda->getKey();

        DB::transaction(function () use ($agenda) {
            $agenda->delete();
        });

        if ($filesToDelete !== []) {
            Storage::disk('public')->delete($filesToDelete);
        }

        Storage::disk('public')->deleteDirectory("documentations/{$agendaId}");
        Storage::disk('public')->deleteDirectory("attendances/{$agendaId}");

        ActivityLogger::log(
            type: 'DELETE_AGENDA',
            description: "Agenda rapat '{$judul}' (ID: {$id}) berhasil dihapus oleh " . $actor->name . ".",
            targetModel: Agenda::class,
            targetId: $id
        );
    }

    /**
     * Update agenda status.
     */
    public function updateStatus(Agenda $agenda, string $newStatus): string
    {
        $statusLabels = [
            'draft' => 'Draft',
            'scheduled' => 'Terjadwal',
            'ongoing' => 'Sedang Berlangsung (Presensi Dibuka)',
            'completed' => 'Selesai (Presensi Ditutup)',
            'cancelled' => 'Dibatalkan',
        ];

        $statusText = $statusLabels[$newStatus] ?? $newStatus;

        DB::transaction(function () use ($agenda, $newStatus, $statusText) {
            $agenda->status = $newStatus;
            $agenda->save();

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_STATUS',
                description: "Status agenda rapat '{$agenda->judul_rapat}' diubah menjadi {$statusText}.",
                targetModel: Agenda::class,
                targetId: $agenda->id,
                properties: ['status' => $newStatus]
            );
        });

        return $statusText;
    }

    /**
     * Update agenda roles (pimpinan & notulis).
     */
    public function updateRoles(Agenda $agenda, array $roles): void
    {
        DB::transaction(function () use ($agenda, $roles) {
            $before = [
                $agenda->nama_pimpinan,
                $agenda->nama_notulis,
            ];

            $agenda->update($roles);
            $agenda->refresh();

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_ROLES',
                description: "Penugasan pimpinan rapat ('{$agenda->nama_pimpinan}') dan notulis ('{$agenda->nama_notulis}') untuk rapat '{$agenda->judul_rapat}' diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id,
                properties: [
                    'old' => ['pimpinan' => $before[0], 'notulis' => $before[1]],
                    'new' => ['pimpinan' => $agenda->nama_pimpinan, 'notulis' => $agenda->nama_notulis],
                ]
            );
        });
    }

    /**
     * Update notulensi, kesimpulan, and optional documentation photos.
     *
     * @param  array{
     *     notulensi?: ?string,
     *     kesimpulan?: ?string,
     *     photos?: ?array<UploadedFile>,
     *     captions?: ?array<string>
     * } $data
     * @param  \Illuminate\Http\Request|null $request Optional request for ReportConfigService
     */
    public function updateNotulen(Agenda $agenda, array $data, ?Request $request = null): void
    {
        DB::transaction(function () use ($agenda, $data, $request) {
            $agenda->update([
                'notulensi' => $data['notulensi'] ?? null,
                'kesimpulan' => $data['kesimpulan'] ?? null,
            ]);

            if ($request && ($request->boolean('has_document_config') || $request->filled('document_title') || $request->hasFile('custom_logo') || $request->boolean('reset_custom_logo'))) {
                $this->reportConfigService->syncFromRequest($request, $agenda, persist: true);
            }

            if (!empty($data['photos']) && is_array($data['photos'])) {
                $captions = $data['captions'] ?? [];
                $baseSortOrder = (int) $agenda->documentations()->max('sort_order');

                foreach ($data['photos'] as $index => $photo) {
                    if ($photo instanceof UploadedFile) {
                        $path = $photo->store('documentations/' . $agenda->id, 'public');

                        AgendaDocumentation::create([
                            'agenda_id' => $agenda->id,
                            'file_path' => $path,
                            'caption' => $captions[$index] ?? null,
                            'sort_order' => $baseSortOrder + $index + 1,
                        ]);
                    }
                }
            }

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_MINUTES',
                description: "Notulensi, kesimpulan, dan konfigurasi dokumen rapat '{$agenda->judul_rapat}' diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id
            );
        });
    }

    /**
     * Delete a single documentation image.
     */
    public function deleteDocumentation(Agenda $agenda, AgendaDocumentation $documentation): void
    {
        if ($documentation->agenda_id !== $agenda->id) {
            throw new \InvalidArgumentException('Foto dokumentasi tidak terkait dengan agenda rapat ini.');
        }

        DB::transaction(function () use ($agenda, $documentation) {
            if (Storage::disk('public')->exists($documentation->file_path)) {
                Storage::disk('public')->delete($documentation->file_path);
            }

            $documentation->delete();

            ActivityLogger::log(
                type: 'DELETE_AGENDA_DOCUMENTATION',
                description: "Foto dokumentasi pada agenda rapat '{$agenda->judul_rapat}' dihapus.",
                targetModel: AgendaDocumentation::class,
                targetId: $documentation->id
            );
        });
    }
}
