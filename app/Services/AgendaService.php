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
        protected ReportConfigService $reportConfigService,
        protected SuratEdaranService $suratEdaranService,
    ) {}

    /**
     * Create an agenda under room advisory lock and database transaction.
     */
    public function createAgenda(User $creator, array $validated, bool $isAllUnits, ?UploadedFile $suratEdaran = null): Agenda
    {
        $targetType = $validated['target_type'] ?? ($isAllUnits ? 'all_units' : (!empty($validated['user_ids']) ? 'selected_users' : 'selected_units'));
        $effectiveIsAllUnits = ($targetType === 'all_units');
        $suratEdaranType = $validated['surat_edaran_type'] ?? 'upload';
        $storedUploadPath = null;

        try {
            /** @var Agenda $agenda */
            $agenda = AgendaConflictService::runUnderRoomLock(
                data: $validated,
                ignoreAgendaId: null,
                user: $creator,
                callback: function () use ($creator, $validated, $targetType, $effectiveIsAllUnits, $suratEdaran, $suratEdaranType, &$storedUploadPath) {
                    return DB::transaction(function () use ($creator, $validated, $targetType, $effectiveIsAllUnits, $suratEdaran, $suratEdaranType, &$storedUploadPath) {
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
                            'target_type' => $targetType,
                            'is_all_units' => $effectiveIsAllUnits,
                            'surat_edaran_type' => $suratEdaranType,
                            'status' => $validated['status'] ?? 'scheduled',
                        ];

                        if ($suratEdaran && $suratEdaranType === 'upload') {
                            $storedUploadPath = $suratEdaran->store('surat_edaran', 'public');
                            $agendaData['surat_edaran_path'] = $storedUploadPath;
                            $agendaData['surat_edaran_name'] = $suratEdaran->getClientOriginalName();
                        }

                        $agenda = Agenda::create($agendaData);

                        // Sync participants according to target_type
                        if ($targetType === 'all_units') {
                            $allUnitIds = Unit::active()->pluck('id')->toArray();
                            $agenda->units()->sync($allUnitIds);
                            $agenda->invitedUsers()->sync([]);
                        } elseif ($targetType === 'selected_users') {
                            $userIds = $validated['user_ids'] ?? [];
                            $agenda->invitedUsers()->sync($userIds);
                            $agenda->units()->sync([]);
                        } else {
                            $unitIds = $validated['unit_ids'] ?? ($validated['units'] ?? []);
                            if ($creator->isAdmin() && empty($unitIds)) {
                                $unitIds = [$creator->unit_id];
                            }
                            $agenda->units()->sync($unitIds);
                            $agenda->invitedUsers()->sync([]);
                        }

                        if ($suratEdaranType === 'digital') {
                            $agenda->load([
                                'creator.unit',
                                'pimpinan.unit',
                                'notulis.unit',
                                'units',
                                'invitedUsers.unit',
                            ]);
                            $this->suratEdaranService->saveDigitalSurat($agenda, $agenda->getDefaultSuratEdaranConfig());
                        }

                        return $agenda;
                    });
                }
            );
        } catch (\Throwable $e) {
            if ($storedUploadPath && Storage::disk('public')->exists($storedUploadPath)) {
                Storage::disk('public')->delete($storedUploadPath);
            }
            throw $e;
        }

        ActivityLogger::log(
            type: 'CREATE_AGENDA',
            description: "Agenda rapat baru '{$agenda->judul_rapat}' ({$agenda->jenis_rapat}, {$agenda->tipe_rapat}, {$agenda->target_label}) berhasil dibuat.",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: $agenda->only(['judul_rapat', 'jenis_rapat', 'tipe_rapat', 'waktu_mulai', 'waktu_selesai', 'is_all_units', 'target_type'])
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
        $wasDigital = $agenda->is_surat_edaran_digital;
        $requestedType = $validated['surat_edaran_type'] ?? null;
        $targetSuratType = ($suratEdaran !== null && $requestedType !== 'digital')
            ? 'upload'
            : ($requestedType ?? ($agenda->surat_edaran_type ?? 'upload'));

        $oldCircularPath = $agenda->surat_edaran_path;
        $oldCustomLogoPath = $agenda->surat_edaran_config['custom_logo_path'] ?? null;
        $oldUnitIds = $agenda->units()->pluck('units.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $oldUserIds = $agenda->invitedUsers()->pluck('users.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $oldPdfSnapshot = [
            'judul_rapat' => $agenda->judul_rapat,
            'waktu_mulai' => $agenda->waktu_mulai?->format('Y-m-d H:i:s'),
            'waktu_selesai' => $agenda->waktu_selesai?->format('Y-m-d H:i:s'),
            'lokasi_ruang' => $agenda->lokasi_ruang,
            'link_meeting' => $agenda->link_meeting,
            'tipe_rapat' => $agenda->tipe_rapat,
            'target_type' => $agenda->target_type,
            'is_all_units' => (bool) $agenda->is_all_units,
            'pimpinan_id' => $agenda->pimpinan_id ? (int) $agenda->pimpinan_id : null,
            'notulis_id' => $agenda->notulis_id ? (int) $agenda->notulis_id : null,
            'surat_edaran_type' => $agenda->surat_edaran_type ?? 'upload',
        ];

        $newCircularPath = null;
        $filesToDeleteAfterCommit = [];

        if ($suratEdaran !== null && $targetSuratType === 'upload') {
            $newCircularPath = $suratEdaran->store('circulars', 'public');
        }

        $targetType = $validated['target_type'] ?? ($isAllUnits ? 'all_units' : (!empty($validated['user_ids']) ? 'selected_users' : 'selected_units'));
        $effectiveIsAllUnits = ($targetType === 'all_units');

        try {
            AgendaConflictService::runUnderRoomLock(
                data: $validated,
                ignoreAgendaId: (int) $agenda->getKey(),
                user: $actor,
                callback: function () use (
                    $validated,
                    $agenda,
                    $oldData,
                    $newCircularPath,
                    $suratEdaran,
                    $targetSuratType,
                    $wasDigital,
                    $oldCircularPath,
                    $oldCustomLogoPath,
                    $oldUnitIds,
                    $oldUserIds,
                    $oldPdfSnapshot,
                    $targetType,
                    $effectiveIsAllUnits,
                    &$filesToDeleteAfterCommit
                ) {
                    DB::transaction(function () use (
                        $validated,
                        $agenda,
                        $oldData,
                        $newCircularPath,
                        $suratEdaran,
                        $targetSuratType,
                        $wasDigital,
                        $oldCircularPath,
                        $oldCustomLogoPath,
                        $oldUnitIds,
                        $oldUserIds,
                        $oldPdfSnapshot,
                        $targetType,
                        $effectiveIsAllUnits,
                        &$filesToDeleteAfterCommit
                    ) {
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
                            'target_type' => $targetType,
                            'is_all_units' => $effectiveIsAllUnits,
                            'surat_edaran_type' => $targetSuratType,
                            'status' => $validated['status'] ?? $agenda->status,
                        ];

                        if ($targetSuratType === 'upload') {
                            if ($newCircularPath !== null) {
                                $updateData['surat_edaran_path'] = $newCircularPath;
                                $updateData['surat_edaran_name'] = $suratEdaran?->getClientOriginalName();

                                if ($oldCircularPath && $oldCircularPath !== $newCircularPath) {
                                    $filesToDeleteAfterCommit[] = $oldCircularPath;
                                }
                                if ($wasDigital || ! empty($agenda->surat_edaran_config) || ! empty($agenda->surat_edaran_content)) {
                                    $updateData['surat_edaran_config'] = null;
                                    $updateData['surat_edaran_content'] = null;
                                    if ($oldCustomLogoPath) {
                                        $filesToDeleteAfterCommit[] = $oldCustomLogoPath;
                                    }
                                }
                            } elseif ($wasDigital) {
                                $updateData['surat_edaran_path'] = null;
                                $updateData['surat_edaran_name'] = null;
                                $updateData['surat_edaran_config'] = null;
                                $updateData['surat_edaran_content'] = null;
                                if ($oldCircularPath) {
                                    $filesToDeleteAfterCommit[] = $oldCircularPath;
                                }
                                if ($oldCustomLogoPath) {
                                    $filesToDeleteAfterCommit[] = $oldCustomLogoPath;
                                }
                            }
                        } else {
                            // Switching upload -> digital: remove legacy uploaded circular path (keep digital PDF path slot)
                            $digitalPdfPath = "surat_edaran/surat_digital_{$agenda->id}.pdf";
                            if (! $wasDigital && $oldCircularPath && $oldCircularPath !== $digitalPdfPath) {
                                $filesToDeleteAfterCommit[] = $oldCircularPath;
                                $updateData['surat_edaran_path'] = null;
                                $updateData['surat_edaran_name'] = null;
                            }
                        }

                        $agenda->update($updateData);

                        // Sync participants according to target_type
                        if ($targetType === 'all_units') {
                            $allUnitIds = Unit::active()->pluck('id')->toArray();
                            $agenda->units()->sync($allUnitIds);
                            $agenda->invitedUsers()->sync([]);
                            $newUnitIds = collect($allUnitIds)->map(fn ($id) => (int) $id)->sort()->values()->all();
                            $newUserIds = [];
                        } elseif ($targetType === 'selected_users') {
                            $userIds = $validated['user_ids'] ?? [];
                            $agenda->invitedUsers()->sync($userIds);
                            $agenda->units()->sync([]);
                            $newUnitIds = [];
                            $newUserIds = collect($userIds)->map(fn ($id) => (int) $id)->sort()->values()->all();
                        } else {
                            $unitIds = $validated['unit_ids'] ?? ($validated['units'] ?? []);
                            $agenda->units()->sync($unitIds);
                            $newUnitIds = collect($unitIds)->map(fn ($id) => (int) $id)->sort()->values()->all();
                            $newUserIds = $agenda->invitedUsers()
                                ->pluck('users.id')
                                ->map(fn ($id) => (int) $id)
                                ->sort()
                                ->values()
                                ->all();
                        }

                        if ($targetSuratType === 'digital') {
                            $agenda->refresh();
                            $newPdfSnapshot = [
                                'judul_rapat' => $agenda->judul_rapat,
                                'waktu_mulai' => $agenda->waktu_mulai?->format('Y-m-d H:i:s'),
                                'waktu_selesai' => $agenda->waktu_selesai?->format('Y-m-d H:i:s'),
                                'lokasi_ruang' => $agenda->lokasi_ruang,
                                'link_meeting' => $agenda->link_meeting,
                                'tipe_rapat' => $agenda->tipe_rapat,
                                'target_type' => $agenda->target_type,
                                'is_all_units' => (bool) $agenda->is_all_units,
                                'pimpinan_id' => $agenda->pimpinan_id ? (int) $agenda->pimpinan_id : null,
                                'notulis_id' => $agenda->notulis_id ? (int) $agenda->notulis_id : null,
                                'surat_edaran_type' => $agenda->surat_edaran_type ?? 'upload',
                            ];

                            $relevantChanged = ($oldPdfSnapshot !== $newPdfSnapshot)
                                || ($oldUnitIds !== $newUnitIds)
                                || ($oldUserIds !== $newUserIds);

                            $hasCachedPdf = $agenda->surat_edaran_path
                                && Storage::disk('public')->exists($agenda->surat_edaran_path);

                            if ($relevantChanged || empty($agenda->surat_edaran_config) || ! $hasCachedPdf) {
                                $agenda->load([
                                    'creator.unit',
                                    'pimpinan.unit',
                                    'notulis.unit',
                                    'units',
                                    'invitedUsers.unit',
                                ]);

                                if (empty($agenda->surat_edaran_config)) {
                                    $this->suratEdaranService->saveDigitalSurat($agenda, $agenda->getDefaultSuratEdaranConfig());
                                } else {
                                    $this->suratEdaranService->generateAndStorePdf($agenda);
                                }
                            }
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
        } catch (\Throwable $e) {
            if ($newCircularPath && Storage::disk('public')->exists($newCircularPath)) {
                Storage::disk('public')->delete($newCircularPath);
            }
            throw $e;
        }

        $filesToDeleteAfterCommit = array_values(array_unique(array_filter($filesToDeleteAfterCommit)));
        if ($filesToDeleteAfterCommit !== []) {
            Storage::disk('public')->delete($filesToDeleteAfterCommit);
        }

        return $agenda->fresh();
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
    public function updateStatus(Agenda $agenda, string $newStatus, ?User $actor = null, bool $allowScheduleOverlap = false): string
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

        $expectedFromStatus = $agenda->status;

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
                'target_type' => $agenda->target_type,
                'unit_ids' => $agenda->units()->pluck('units.id')->all(),
                'status' => 'scheduled',
                'allow_schedule_overlap' => $allowScheduleOverlap,
            ];

            return AgendaConflictService::runUnderRoomLock(
                data: $data,
                ignoreAgendaId: (int) $agenda->getKey(),
                user: $actor,
                callback: function () use ($agenda, $newStatus, $statusText, $expectedFromStatus) {
                    return $this->applyStatusUpdate($agenda, $newStatus, $statusText, $expectedFromStatus);
                }
            );
        }

        return $this->applyStatusUpdate($agenda, $newStatus, $statusText, $expectedFromStatus);
    }

    /**
     * Terapkan update status agenda di bawah row lock (anti race condition) dan catat activity log.
     */
    private function applyStatusUpdate(Agenda $agenda, string $newStatus, string $statusText, ?string $expectedFromStatus = null): string
    {
        return DB::transaction(function () use ($agenda, $newStatus, $statusText, $expectedFromStatus) {
            /** @var Agenda $locked */
            $locked = Agenda::query()
                ->whereKey($agenda->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === $newStatus) {
                $agenda->setRawAttributes($locked->getAttributes(), true);

                return $statusText;
            }

            if ($expectedFromStatus !== null && $locked->status !== $expectedFromStatus) {
                throw new \DomainException("Status agenda telah berubah menjadi '{$locked->status}' oleh proses lain. Silakan muat ulang halaman.");
            }

            if (! $locked->canTransitionTo($newStatus)) {
                throw new \DomainException("Perubahan status agenda dari '{$locked->status}' menjadi '{$newStatus}' tidak diizinkan oleh sistem.");
            }

            $locked->status = $newStatus;
            $locked->save();
            $agenda->setRawAttributes($locked->getAttributes(), true);

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_STATUS',
                description: "Status agenda rapat '{$locked->judul_rapat}' diubah menjadi {$statusText}.",
                targetModel: Agenda::class,
                targetId: $locked->id,
                properties: ['status' => $newStatus]
            );

            return $statusText;
        });
    }

    /**
     * Add ad-hoc active ASN participants to an agenda (e.g. during live sessions).
     *
     * @param  array<int, int|string>  $userIds
     */
    public function addParticipants(Agenda $agenda, array $userIds, User $actor): Agenda
    {
        if ($agenda->target_type === 'all_units' || $agenda->is_all_units) {
            throw new \DomainException('Agenda rapat Pleno sudah mengundang seluruh pegawai ASN aktif tanpa perlu penambahan peserta khusus.');
        }

        $cleanIds = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $activeUserIds = empty($cleanIds)
            ? []
            : User::where('is_active', true)->whereIn('id', $cleanIds)->pluck('id')->all();

        if (empty($activeUserIds)) {
            throw new \DomainException('Pilih minimal satu pegawai ASN aktif untuk ditambahkan sebagai peserta rapat.');
        }

        return DB::transaction(function () use ($agenda, $activeUserIds, $actor) {
            /** @var Agenda $locked */
            $locked = Agenda::query()
                ->whereKey($agenda->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existingIds = $locked->invitedUsers()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
            $newlyAddedIds = array_values(array_diff($activeUserIds, $existingIds));

            $locked->invitedUsers()->syncWithoutDetaching($activeUserIds);

            $addedCount = count($newlyAddedIds);
            ActivityLogger::log(
                type: 'ADD_AGENDA_PARTICIPANTS',
                description: "Penambahan {$addedCount} peserta ASN susulan pada agenda '{$locked->judul_rapat}'.",
                targetModel: Agenda::class,
                targetId: $locked->id,
                properties: [
                    'added_user_ids' => $newlyAddedIds,
                    'requested_user_ids' => $activeUserIds,
                ],
                user: $actor
            );

            return $locked->fresh(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units', 'invitedUsers.unit']);
        });
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

            if (!empty($data['existing_captions']) && is_array($data['existing_captions'])) {
                foreach ($data['existing_captions'] as $docId => $captionText) {
                    $normalizedCaption = is_string($captionText) ? trim($captionText) : null;
                    $agenda->documentations()
                        ->whereKey((int) $docId)
                        ->update([
                            'caption' => $normalizedCaption !== '' ? $normalizedCaption : null,
                        ]);
                }
            }

            if (!empty($data['photos']) && is_array($data['photos'])) {
                $captions = $data['captions'] ?? [];
                $baseSortOrder = (int) $agenda->documentations()->max('sort_order');

                foreach ($data['photos'] as $index => $photo) {
                    if ($photo instanceof UploadedFile) {
                        $path = $photo->store('documentations/' . $agenda->id, 'public');
                        $captionVal = isset($captions[$index]) && is_string($captions[$index])
                            ? trim($captions[$index])
                            : null;

                        AgendaDocumentation::create([
                            'agenda_id' => $agenda->id,
                            'file_path' => $path,
                            'caption' => $captionVal !== '' ? $captionVal : null,
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
