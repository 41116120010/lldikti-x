<?php

namespace App\Http\Controllers;

use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaStatusRequest;
use App\Http\Requests\Agenda\UpdateMinutesRequest;
use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgendaController extends Controller
{
    /**
     * Display a listing of agendas.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Agenda::class);

        $currentUser = Auth::user();
        $query = Agenda::visibleTo($currentUser)
            ->with(['creator.unit', 'units', 'attendances'])
            ->withCount('attendances');

        // Status Filter Tabs
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Meeting Type Filter
        if ($type = $request->input('tipe')) {
            $query->where('tipe_rapat', $type);
        }

        // Unit Filter (Administrator can filter by specific target unit or universal)
        if ($unitId = $request->input('unit_id')) {
            if ($currentUser->isAdministrator()) {
                if ($unitId === 'all_units') {
                    $query->where('is_all_units', true);
                } else {
                    $query->whereHas('units', function ($sub) use ($unitId) {
                        $sub->where('units.id', $unitId);
                    });
                }
            }
        }

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul_rapat', 'like', "%{$search}%")
                  ->orWhere('lokasi_ruang', 'like', "%{$search}%");
            });
        }

        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate(9)->withQueryString();
        $units = $currentUser->isAdministrator() ? Unit::active()->orderBy('nama_unit')->get() : collect();

        // Stat counts (single aggregated query for high performance & durability)
        $rawCounts = Agenda::visibleTo($currentUser)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusCounts = [
            'all' => (int) $rawCounts->sum(),
            'draft' => (int) $rawCounts->get('draft', 0),
            'ongoing' => (int) $rawCounts->get('ongoing', 0),
            'scheduled' => (int) $rawCounts->get('scheduled', 0),
            'completed' => (int) $rawCounts->get('completed', 0),
        ];

        return view('agendas.index', compact('agendas', 'currentUser', 'statusCounts', 'units'));
    }

    /**
     * Show the form for creating a new agenda.
     */
    public function create(): View
    {
        Gate::authorize('create', Agenda::class);

        $currentUser = Auth::user();
        $currentUser->load('unit');
        $units = Unit::active()->orderBy('nama_unit')->get();
        $users = User::query()->where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('agendas.create', compact('units', 'currentUser', 'users'));
    }

    /**
     * Store a newly created agenda in storage.
     */
    public function store(StoreAgendaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        $agenda = DB::transaction(function () use ($request, $validated, $user) {
            $isAllUnits = $request->boolean('is_all_units', true);
            $waktuMulai = $validated['waktu_mulai'];
            $waktuSelesai = !empty($validated['waktu_selesai']) 
                ? $validated['waktu_selesai'] 
                : null;

            $agendaData = [
                'created_by' => $user->id,
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

            if ($request->hasFile('surat_edaran')) {
                $file = $request->file('surat_edaran');
                $path = $file->store('surat_edaran', 'public');
                $agendaData['surat_edaran_path'] = $path;
                $agendaData['surat_edaran_name'] = $file->getClientOriginalName();
            }

            $agenda = Agenda::create($agendaData);

            // Sync units
            if ($isAllUnits) {
                // Attach all active units
                $allUnitIds = Unit::active()->pluck('id')->toArray();
                $agenda->units()->sync($allUnitIds);
            } else {
                $unitIds = $validated['unit_ids'] ?? ($validated['units'] ?? []);
                if ($user->isAdmin() && empty($unitIds)) {
                    $unitIds = [$user->unit_id];
                }
                $agenda->units()->sync($unitIds);
            }

            return $agenda;
        });

        ActivityLogger::log(
            type: 'CREATE_AGENDA',
            description: "Agenda rapat baru '{$agenda->judul_rapat}' ({$agenda->jenis_rapat}, {$agenda->tipe_rapat}) berhasil dibuat.",
            targetModel: Agenda::class,
            targetId: $agenda->id,
            properties: $agenda->only(['judul_rapat', 'jenis_rapat', 'tipe_rapat', 'waktu_mulai', 'waktu_selesai', 'is_all_units'])
        );

        return redirect()->route('admin.agendas.show', $agenda)
            ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil dibuat.");
    }

    /**
     * Display the specified agenda.
     */
    public function show(Agenda $agenda): View
    {
        Gate::authorize('view', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
        ]);
        $agenda->loadCount('attendances');

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'desc')
            ->paginate(8, ['*'], 'page_attendees')
            ->withQueryString();

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        $currentUser = Auth::user();
        $myAttendance = $agenda->attendances()->where('user_id', $currentUser->id)->first();
        $users = User::query()->where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('agendas.show', compact('agenda', 'attendances', 'documentations', 'currentUser', 'myAttendance', 'users'));
    }

    /**
     * Display meeting details for staff (non-admin route).
     * Strictly hides the list of other attendees.
     */
    public function staffShow(Agenda $agenda): View
    {
        Gate::authorize('viewStaff', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
        ]);

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        $currentUser = Auth::user();
        $myAttendance = $agenda->attendances()->where('user_id', $currentUser->id)->first();

        return view('agendas.staff_show', compact('agenda', 'documentations', 'currentUser', 'myAttendance'));
    }

    /**
     * Show the form for editing the specified agenda.
     */
    public function edit(Agenda $agenda): View
    {
        Gate::authorize('update', $agenda);

        $agenda->load(['units', 'pimpinan', 'notulis']);
        $agenda->loadCount('attendances');
        $currentUser = Auth::user();
        $currentUser->load('unit');
        $units = Unit::active()->orderBy('nama_unit')->get();
        $users = User::query()->where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('agendas.edit', compact('agenda', 'units', 'currentUser', 'users'));
    }

    /**
     * Update the specified agenda in storage.
     */
    public function update(UpdateAgendaRequest $request, Agenda $agenda): RedirectResponse
    {
        $validated = $request->validated();
        $oldData = $agenda->toArray();

        DB::transaction(function () use ($request, $validated, $agenda, $oldData) {
            $isAllUnits = $request->boolean('is_all_units', true);
            $waktuMulai = $validated['waktu_mulai'];
            $waktuSelesai = !empty($validated['waktu_selesai']) 
                ? $validated['waktu_selesai'] 
                : null;

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

            // Handle replacement of circular letter
            if ($request->hasFile('surat_edaran')) {
                if ($agenda->surat_edaran_path && Storage::disk('public')->exists($agenda->surat_edaran_path)) {
                    Storage::disk('public')->delete($agenda->surat_edaran_path);
                }

                $file = $request->file('surat_edaran');
                $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('circulars', $filename, 'public');
                $updateData['surat_edaran_path'] = $path;
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

        return redirect()->route('admin.agendas.show', $agenda)
            ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil diperbarui.");
    }

    /**
     * Remove the specified agenda from storage.
     * Dapat dilakukan oleh Administrator (semua unit) atau Admin Unit yang berkaitan.
     */
    public function destroy(Agenda $agenda): RedirectResponse
    {
        Gate::authorize('delete', $agenda);

        $judul = $agenda->judul_rapat;
        $id = $agenda->id;

        // Lindungi catatan presensi kegiatan kedinasan yang sudah terlaksana
        if ($agenda->attendances()->exists()) {
            return back()->with('error', "Agenda rapat '{$judul}' telah memiliki catatan presensi kehadiran pegawai. Agenda tidak dapat dihapus demi integritas arsip kegiatan. Ubah status agenda menjadi 'Dibatalkan' jika agenda batal terlaksana.");
        }

        DB::transaction(function () use ($agenda, $id) {
            // 1. Hapus berkas fisik surat edaran
            if ($agenda->surat_edaran_path && Storage::disk('public')->exists($agenda->surat_edaran_path)) {
                Storage::disk('public')->delete($agenda->surat_edaran_path);
            }

            // 2. Hapus berkas fisik custom logo kop jika ada
            if (!empty($agenda->report_config['custom_logo_path']) && Storage::disk('public')->exists($agenda->report_config['custom_logo_path'])) {
                Storage::disk('public')->delete($agenda->report_config['custom_logo_path']);
            }

            // 3. Hapus berkas fisik foto dokumentasi rapat
            foreach ($agenda->documentations as $doc) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }

            // 4. Hapus berkas fisik presensi peserta (foto selfie dan tanda tangan digital)
            foreach ($agenda->attendances as $attendance) {
                if ($attendance->selfie_path && Storage::disk('public')->exists($attendance->selfie_path)) {
                    Storage::disk('public')->delete($attendance->selfie_path);
                }
                if ($attendance->signature_path && Storage::disk('public')->exists($attendance->signature_path)) {
                    Storage::disk('public')->delete($attendance->signature_path);
                }
            }

            // 5. Bersihkan direktori agenda jika tersisa
            Storage::disk('public')->deleteDirectory("documentations/{$id}");
            Storage::disk('public')->deleteDirectory("attendances/{$id}");

            // 6. Hapus agenda (relasi attendances, documentations, agenda_units cascade otomatis)
            $agenda->delete();
        });

        ActivityLogger::log(
            type: 'DELETE_AGENDA',
            description: "Agenda rapat '{$judul}' (ID: {$id}) berhasil dihapus oleh " . Auth::user()->name . ".",
            targetModel: Agenda::class,
            targetId: $id
        );

        return redirect()->route('admin.agendas.index')
            ->with('success', "Agenda rapat '{$judul}' berhasil dihapus.");
    }

    /**
     * Update status of the agenda (e.g. start meeting, complete meeting).
     */
    public function updateStatus(UpdateAgendaStatusRequest $request, Agenda $agenda): RedirectResponse
    {
        Gate::authorize('manageStatus', $agenda);

        $validated = $request->validated();
        $newStatus = $validated['status'];

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

        return back()->with('success', "Status agenda rapat berhasil diubah menjadi {$statusText}.");
    }

    /**
     * Update pimpinan and notulis roles dynamically (e.g. during meeting or from show page).
     */
    public function updateRoles(Request $request, Agenda $agenda): RedirectResponse
    {
        Gate::authorize('update', $agenda);

        $validated = $request->validate([
            'pimpinan_id' => ['nullable', 'exists:users,id'],
            'notulis_id' => ['nullable', 'exists:users,id'],
        ], [], [
            'pimpinan_id' => 'Pemimpin Rapat',
            'notulis_id' => 'Notulis Rapat',
        ]);

        DB::transaction(function () use ($validated, $agenda) {
            $oldPimpinan = $agenda->nama_pimpinan;
            $oldNotulis = $agenda->nama_notulis;

            $agenda->update([
                'pimpinan_id' => !empty($validated['pimpinan_id']) ? $validated['pimpinan_id'] : null,
                'notulis_id' => !empty($validated['notulis_id']) ? $validated['notulis_id'] : null,
            ]);

            $agenda->refresh();
            $agenda->load(['pimpinan.unit', 'notulis.unit', 'creator.unit']);

            if (is_array($agenda->report_config)) {
                $currentConfig = $agenda->report_config;
                $currentConfig['signer1_name'] = $agenda->nama_pimpinan;
                $currentConfig['signer1_nip'] = ($agenda->nip_pimpinan && $agenda->nip_pimpinan !== '-') ? $agenda->nip_pimpinan : '-';
                $currentConfig['signer2_name'] = $agenda->nama_notulis;
                $currentConfig['signer2_nip'] = ($agenda->nip_notulis && $agenda->nip_notulis !== '-') ? $agenda->nip_notulis : '-';
                $agenda->update(['report_config' => $currentConfig]);
            }

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_ROLES',
                description: "Penugasan pimpinan rapat ('{$agenda->nama_pimpinan}') dan notulis ('{$agenda->nama_notulis}') untuk rapat '{$agenda->judul_rapat}' diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id,
                properties: [
                    'old' => ['pimpinan' => $oldPimpinan, 'notulis' => $oldNotulis],
                    'new' => ['pimpinan' => $agenda->nama_pimpinan, 'notulis' => $agenda->nama_notulis],
                ]
            );
        });

        return back()->with('success', 'Penugasan Pemimpin Rapat dan Notulis berhasil diperbarui.');
    }

    /**
     * Show Notulensi & Documentation editor view (Office Document WYSIWYG).
     */
    public function notulen(Agenda $agenda): View
    {
        Gate::authorize('manageMinutes', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
        ]);

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'asc')
            ->get();

        $config = $agenda->resolved_report_config;

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        return view('agendas.notulen', compact('agenda', 'documentations', 'config', 'attendances'));
    }

    /**
     * Update Notulensi, Kesimpulan, and Upload Documentation Photos & Sync Document Configuration (Single Action).
     */
    public function updateNotulen(UpdateMinutesRequest $request, Agenda $agenda, ReportConfigService $configService): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $agenda, $configService) {
            $agenda->update([
                'notulensi' => $validated['notulensi'] ?? null,
                'kesimpulan' => $validated['kesimpulan'] ?? null,
            ]);

            // Synchronize report configuration if provided from workstation
            if ($request->boolean('has_document_config') || $request->filled('document_title') || $request->hasFile('custom_logo') || $request->boolean('reset_custom_logo')) {
                $configService->syncFromRequest($request, $agenda, persist: true);
            }

            // Handle multi-photo documentations
            if ($request->hasFile('photos')) {
                $captions = $request->input('captions', []);
                foreach ($request->file('photos') as $index => $photo) {
                    $filename = Str::random(32) . '.' . $photo->getClientOriginalExtension();
                    $path = $photo->storeAs('documentations/' . $agenda->id, $filename, 'public');

                    AgendaDocumentation::create([
                        'agenda_id' => $agenda->id,
                        'file_path' => $path,
                        'caption' => $captions[$index] ?? null,
                        'sort_order' => $agenda->documentations()->count() + $index + 1,
                    ]);
                }
            }

            ActivityLogger::log(
                type: 'UPDATE_AGENDA_MINUTES',
                description: "Notulensi, kesimpulan, dan konfigurasi dokumen rapat '{$agenda->judul_rapat}' diperbarui.",
                targetModel: Agenda::class,
                targetId: $agenda->id
            );
        });

        $redirectRoute = Auth::user()?->isPegawai() 
            ? route('agendas.show', $agenda) 
            : route('admin.agendas.show', $agenda);

        return redirect($redirectRoute)
            ->with('success', "Notulensi dan pengaturan dokumen rapat berhasil disimpan.");
    }

    /**
     * Delete a single photo documentation item.
     */
    public function deleteDocumentation(Agenda $agenda, AgendaDocumentation $documentation): RedirectResponse
    {
        Gate::authorize('manageMinutes', $agenda);

        if ($documentation->agenda_id !== $agenda->id) {
            abort(404);
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

        return back()->with('success', "Foto dokumentasi berhasil dihapus.");
    }
}
