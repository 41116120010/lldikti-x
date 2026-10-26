<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
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
        $newStatus = $validated['status'] ?? $agenda->status;

        if ($newStatus !== $agenda->status) {
            if (! $agenda->canTransitionTo($newStatus)) {
                throw new \DomainException("Perubahan status agenda dari '{$agenda->status}' menjadi '{$newStatus}' tidak diizinkan oleh sistem.");
            }

            if ($agenda->status === 'scheduled' && $newStatus === 'ongoing') {
                if (! $agenda->can_start_now) {
                    if ($agenda->waktu_mulai && now()->lt($agenda->earliest_start_at)) {
                        $earliestStr = $agenda->earliest_start_at->translatedFormat('H:i');
                        throw new \DomainException("Sesi rapat belum dapat dimulai. Tombol mulai baru dapat diakses 15 menit sebelum jadwal rapat ({$earliestStr} WIB).");
                    }

                    if ($agenda->effective_end_at && now()->gt($agenda->effective_end_at)) {
                        $effectiveEndStr = $agenda->effective_end_at->translatedFormat('H:i');
                        throw new \DomainException("Sesi rapat tidak dapat dimulai karena telah melewati batas akhir pelaksanaan ({$effectiveEndStr} WIB).");
                    }
                }
            }

            if ($agenda->status === 'ongoing' && $newStatus === 'scheduled') {
                if ($agenda->attendances()->count() > 0) {
                    throw new \DomainException("Sesi rapat yang telah memiliki catatan presensi kehadiran pegawai tidak dapat dikembalikan ke status Terjadwal demi integritas Berita Acara.");
                }
            }

            if ($agenda->status === 'draft' && $newStatus === 'scheduled') {
                $checkMulai = Carbon::parse($validated['waktu_mulai'] ?? $agenda->waktu_mulai);
                if ($checkMulai->isPast()) {
                    throw new \DomainException("Agenda konsep dengan jadwal pelaksanaan di masa lampau ({$checkMulai->translatedFormat('d F Y, H:i')} WIB) tidak dapat diterbitkan ke status Terjadwal. Silakan perbarui waktu mulai terlebih dahulu.");
                }
            }
        }

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
     * Upload or replace circular letter file independently.
     */
    public function updateSuratEdaran(Agenda $agenda, UploadedFile $suratEdaran): Agenda
    {
        $newCircularPath = $suratEdaran->store('circulars', 'public');
        $supersededCircularPath = $agenda->surat_edaran_path;

        DB::transaction(function () use ($agenda, $newCircularPath) {
            $agenda->update(['surat_edaran_path' => $newCircularPath]);

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_CIRCULAR',
                description: "Berkas surat edaran rapat '{$agenda->judul_rapat}' diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id
            );
        });

        if ($supersededCircularPath && $supersededCircularPath !== $newCircularPath) {
            Storage::disk('public')->delete($supersededCircularPath);
        }

        return $agenda->fresh();
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
     * Update agenda status with state machine verification and conflict prevention.
     *
     * @throws \DomainException If status transition is prohibited or time preconditions fail.
     */
    public function updateStatus(Agenda $agenda, string $newStatus, ?User $actor = null): string
    {
        $statusLabels = [
            'draft' => 'Draft',
            'scheduled' => 'Terjadwal',
            'ongoing' => 'Sedang Berlangsung (Presensi Dibuka)',
            'completed' => 'Selesai (Presensi Ditutup)',
            'cancelled' => 'Dibatalkan',
        ];

        $statusText = $statusLabels[$newStatus] ?? $newStatus;

        if ($agenda->status === $newStatus) {
            return $statusText;
        }

        // 1. Validasi Matriks Transisi Resmi (State Machine)
        if (! $agenda->canTransitionTo($newStatus)) {
            throw new \DomainException("Perubahan status agenda dari '{$agenda->status}' menjadi '{$newStatus}' tidak diizinkan oleh sistem.");
        }

        // 2. Transisi scheduled -> ongoing: Penegakan Jendela Waktu Mulai (15 Menit)
        if ($agenda->status === 'scheduled' && $newStatus === 'ongoing') {
            if (! $agenda->can_start_now) {
                if ($agenda->waktu_mulai && now()->lt($agenda->earliest_start_at)) {
                    $earliestStr = $agenda->earliest_start_at->translatedFormat('H:i');
                    throw new \DomainException("Sesi rapat belum dapat dimulai. Tombol mulai baru dapat diakses 15 menit sebelum jadwal rapat ({$earliestStr} WIB).");
                }

                if ($agenda->effective_end_at && now()->gt($agenda->effective_end_at)) {
                    $effectiveEndStr = $agenda->effective_end_at->translatedFormat('H:i');
                    throw new \DomainException("Sesi rapat tidak dapat dimulai karena telah melewati batas akhir pelaksanaan ({$effectiveEndStr} WIB).");
                }
            }
        }

        // 3. Transisi ongoing -> scheduled: Hanya diizinkan jika belum ada presensi
        if ($agenda->status === 'ongoing' && $newStatus === 'scheduled') {
            if ($agenda->attendances()->count() > 0) {
                throw new \DomainException("Sesi rapat yang telah memiliki catatan presensi kehadiran pegawai tidak dapat dikembalikan ke status Terjadwal demi integritas Berita Acara.");
            }
        }

        // 4. Transisi draft -> scheduled: Penguncian Ruangan, Validasi Konflik, dan Anti-Waktu Lampau
        if ($agenda->status === 'draft' && $newStatus === 'scheduled') {
            if ($agenda->waktu_mulai && $agenda->waktu_mulai->isPast()) {
                throw new \DomainException("Agenda konsep dengan jadwal pelaksanaan di masa lampau ({$agenda->waktu_mulai->translatedFormat('d F Y, H:i')} WIB) tidak dapat diterbitkan ke status Terjadwal. Silakan perbarui waktu mulai terlebih dahulu.");
            }

            $data = [
                'lokasi_ruang' => $agenda->lokasi_ruang,
                'tipe_rapat' => $agenda->tipe_rapat,
                'waktu_mulai' => $agenda->waktu_mulai?->format('Y-m-d H:i:s'),
                'waktu_selesai' => $agenda->waktu_selesai?->format('Y-m-d H:i:s'),
                'pimpinan_id' => $agenda->pimpinan_id,
                'notulis_id' => $agenda->notulis_id,
                'is_all_units' => $agenda->is_all_units,
                'unit_ids' => $agenda->units()->pluck('units.id')->all(),
                'status' => 'scheduled',
            ];

            return AgendaConflictService::runUnderRoomLock(
                data: $data,
                ignoreAgendaId: (int) $agenda->getKey(),
                user: $actor,
                callback: function () use ($agenda, $newStatus, $statusText) {
                    return $this->applyStatusUpdate($agenda, $newStatus, $statusText);
                }
            );
        }

        return $this->applyStatusUpdate($agenda, $newStatus, $statusText);
    }

    /**
     * Terapkan update status agenda dan catat activity log.
     */
    private function applyStatusUpdate(Agenda $agenda, string $newStatus, string $statusText): string
    {
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
            $updatePayload = [];
            if (array_key_exists('notulensi', $data)) {
                $updatePayload['notulensi'] = $data['notulensi'];
            }
            if (array_key_exists('kesimpulan', $data)) {
                $updatePayload['kesimpulan'] = $data['kesimpulan'];
            }
            if (!empty($updatePayload)) {
                $agenda->update($updatePayload);
            }

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
