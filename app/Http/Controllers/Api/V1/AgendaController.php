<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaStatusRequest;
use App\Http\Requests\Agenda\UpdateMinutesRequest;
use App\Http\Requests\Agenda\UpdateRolesRequest;
use App\Http\Resources\AgendaResource;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\DocumentationResource;
use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Services\AgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AgendaController extends Controller
{
    /**
     * Display a listing of agendas visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Agenda::visibleTo($user);

        // Staff should not see draft or cancelled agendas unless designated
        if ($user->isStaff()) {
            $query->whereNotIn('status', ['draft', 'cancelled']);
        }

        // Filter: Tipe Rapat (daring/online, luring/offline, hybrid)
        if ($request->filled('tipe_rapat')) {
            $query->where('tipe_rapat', $request->query('tipe_rapat'));
        }

        // Filter: Jenis Rapat
        if ($request->filled('jenis_rapat')) {
            $query->where('jenis_rapat', $request->query('jenis_rapat'));
        }

        // Filter: Search title, location, or meeting category
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->whereLike('judul_rapat', "%{$search}%")
                    ->orWhereLike('lokasi_ruang', "%{$search}%")
                    ->orWhereLike('jenis_rapat', "%{$search}%");
            });
        }

        // Filter: Unit (for Administrator & Admin Unit, including 'all_units' pleno filter)
        if ($request->filled('unit_id')) {
            $unitId = $request->query('unit_id');
            if ($unitId === 'all_units') {
                $query->where('is_all_units', true);
            } elseif ($user->isAdministrator()) {
                $query->whereHas('units', fn ($q) => $q->where('units.id', (int) $unitId));
            }
        }

        // Calculate status counts before applying status filter and withCount (mirrors Web AgendaController::index)
        $rawCounts = (clone $query)
            ->reorder()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = [
            'all' => (int) $rawCounts->sum(),
            'draft' => (int) ($rawCounts['draft'] ?? 0),
            'ongoing' => (int) ($rawCounts['ongoing'] ?? 0),
            'scheduled' => (int) ($rawCounts['scheduled'] ?? 0),
            'completed' => (int) ($rawCounts['completed'] ?? 0),
            'cancelled' => (int) ($rawCounts['cancelled'] ?? 0),
        ];

        // Filter: Status
        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $agendas = $query
            ->with([
                'creator.unit',
                'pimpinan.unit',
                'notulis.unit',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances')
            ->latest('waktu_mulai')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar agenda rapat berhasil diambil.',
            'data' => AgendaResource::collection($agendas),
            'meta' => [
                'current_page' => $agendas->currentPage(),
                'last_page' => $agendas->lastPage(),
                'per_page' => $agendas->perPage(),
                'total' => $agendas->total(),
            ],
            'summary' => [
                'status_counts' => $statusCounts,
            ],
        ]);
    }

    /**
     * Store a newly created agenda in storage.
     */
    public function store(StoreAgendaRequest $request, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('create', Agenda::class);

        $agenda = $agendaService->createAgenda(
            creator: $request->user(),
            validated: $request->validated(),
            isAllUnits: $request->boolean('is_all_units', true),
            suratEdaran: $request->file('surat_edaran')
        );

        $agenda->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units']);

        return response()->json([
            'success' => true,
            'message' => "Agenda rapat '{$agenda->judul_rapat}' berhasil dibuat.",
            'data' => new AgendaResource($agenda),
        ], 201);
    }

    /**
     * Display the specified agenda.
     */
    public function show(Agenda $agenda, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check view permissions (Admin view or Staff view)
        $canView = Gate::allows('view', $agenda) || Gate::allows('viewStaff', $agenda);
        if (!$canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat agenda rapat ini.');
        }

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'documentations',
            'attendances' => fn ($q) => $q->where('user_id', $user->id),
        ]);
        $agenda->loadCount('attendances');

        return response()->json([
            'success' => true,
            'message' => 'Detail agenda rapat berhasil diambil.',
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Update the specified agenda in storage.
     */
    public function update(UpdateAgendaRequest $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('update', $agenda);

        try {
            $agenda = $agendaService->updateAgenda(
                agenda: $agenda,
                actor: $request->user(),
                validated: $request->validated(),
                isAllUnits: $request->boolean('is_all_units', true),
                suratEdaran: $request->file('surat_edaran')
            );
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $agenda->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units', 'documentations']);

        return response()->json([
            'success' => true,
            'message' => "Agenda rapat '{$agenda->judul_rapat}' berhasil diperbarui.",
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Stream protected circular letter (Surat Edaran) document for authorized mobile clients.
     */
    public function streamSuratEdaran(Agenda $agenda, Request $request): Response
    {
        $canView = Gate::allows('view', $agenda) || Gate::allows('viewStaff', $agenda);
        if (! $canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh surat edaran agenda rapat ini.');
        }

        if (! $agenda->surat_edaran_path || ! Storage::disk('public')->exists($agenda->surat_edaran_path)) {
            abort(404, 'Berkas surat edaran tidak ditemukan.');
        }

        return Storage::disk('public')->response(
            $agenda->surat_edaran_path,
            headers: [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }

    /**
     * Upload or replace circular letter document independently.
     */
    public function updateSuratEdaran(Request $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('update', $agenda);

        $request->validate([
            'surat_edaran' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $agenda = $agendaService->updateSuratEdaran(
            agenda: $agenda,
            suratEdaran: $request->file('surat_edaran')
        );

        $agenda->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units', 'documentations']);

        return response()->json([
            'success' => true,
            'message' => 'Berkas surat edaran rapat berhasil diunggah.',
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Remove the specified agenda from storage.
     */
    public function destroy(Agenda $agenda, Request $request, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('delete', $agenda);

        try {
            $agendaService->deleteAgenda($agenda, $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Agenda rapat '{$agenda->judul_rapat}' berhasil dihapus.",
        ]);
    }

    /**
     * Update status of the agenda (e.g. Mulai Rapat / ongoing, Selesai / completed).
     */
    public function updateStatus(UpdateAgendaStatusRequest $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('manageStatus', $agenda);

        $validated = $request->validated();

        try {
            $statusText = $agendaService->updateStatus($agenda, $validated['status'], $request->user());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $agenda->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units']);

        return response()->json([
            'success' => true,
            'message' => "Status agenda rapat berhasil diubah menjadi {$statusText}.",
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Update meeting leader and minute taker dynamically.
     */
    public function updateRoles(UpdateRolesRequest $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('manageStatus', $agenda);

        $agendaService->updateRoles($agenda, $request->roles());
        $agenda->refresh()->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units']);

        return response()->json([
            'success' => true,
            'message' => 'Penugasan Pemimpin Rapat dan Notulis berhasil diperbarui.',
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Retrieve complete Notulensi & Documentation workstation payload for mobile editor.
     */
    public function notulen(Agenda $agenda, Request $request): JsonResponse
    {
        Gate::authorize('manageMinutes', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'documentations',
        ]);
        $agenda->loadCount('attendances');

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data editor notulensi dan konfigurasi dokumen berhasil diambil.',
            'data' => [
                'agenda' => new AgendaResource($agenda),
                'notulensi_raw' => $agenda->notulensi,
                'kesimpulan_raw' => $agenda->kesimpulan,
                'report_config' => $agenda->resolved_report_config,
                'documentations' => DocumentationResource::collection($agenda->documentations),
                'attendances' => AttendanceResource::collection($attendances),
            ],
        ]);
    }

    /**
     * Update minutes of meeting, conclusions, photos, and optional document configurations.
     */
    public function updateNotulen(UpdateMinutesRequest $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        $validated = $request->validated();

        $agendaService->updateNotulen(
            agenda: $agenda,
            data: [
                'notulensi' => $validated['notulensi'] ?? null,
                'kesimpulan' => $validated['kesimpulan'] ?? null,
                'photos' => $request->file('photos'),
                'captions' => $request->input('captions', []),
            ],
            request: $request,
        );

        $agenda->refresh()->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'documentations',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notulensi dan dokumentasi rapat berhasil disimpan.',
            'data' => new AgendaResource($agenda),
        ]);
    }

    /**
     * Upload one or more documentation photos directly to an agenda from mobile camera/gallery.
     */
    public function uploadDocumentation(Request $request, Agenda $agenda, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('manageMinutes', $agenda);

        $request->validate([
            'photos' => ['required_without:photo', 'nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'photo' => ['required_without:photos', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'captions' => ['nullable', 'array', 'max:10'],
            'captions.*' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'photos.required_without' => 'Pilih minimal satu berkas foto dokumentasi kegiatan.',
            'photos.*.image' => 'Berkas dokumentasi wajib berupa gambar.',
            'photos.*.mimes' => 'Format foto dokumentasi harus JPG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran setiap foto dokumentasi maksimal 4 MB.',
            'photo.image' => 'Berkas dokumentasi wajib berupa gambar.',
            'photo.mimes' => 'Format foto dokumentasi harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto dokumentasi maksimal 4 MB.',
        ]);

        $photos = $request->file('photos') ?? ($request->hasFile('photo') ? [$request->file('photo')] : []);
        $captions = $request->input('captions') ?? ($request->filled('caption') ? [$request->input('caption')] : []);

        $agendaService->updateNotulen(
            agenda: $agenda,
            data: [
                'photos' => $photos,
                'captions' => $captions,
            ],
        );

        $agenda->refresh()->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'documentations',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto dokumentasi kegiatan berhasil diunggah.',
            'data' => new AgendaResource($agenda),
        ], 201);
    }

    /**
     * Remove a specific documentation photo from the agenda.
     */
    public function deleteDocumentation(Agenda $agenda, AgendaDocumentation $documentation, AgendaService $agendaService): JsonResponse
    {
        Gate::authorize('manageMinutes', $agenda);

        try {
            $agendaService->deleteDocumentation($agenda, $documentation);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto dokumentasi kegiatan berhasil dihapus.',
        ]);
    }
}


