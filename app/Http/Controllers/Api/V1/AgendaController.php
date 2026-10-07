<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaStatusRequest;
use App\Http\Requests\Agenda\UpdateMinutesRequest;
use App\Http\Requests\Agenda\UpdateRolesRequest;
use App\Http\Resources\AgendaResource;
use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Services\AgendaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AgendaController extends Controller
{
    /**
     * Display a listing of agendas visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Agenda::visibleTo($user)
            ->with([
                'creator.unit',
                'pimpinan.unit',
                'notulis.unit',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances');

        // Staff should not see draft or cancelled agendas unless designated
        if ($user->isStaff()) {
            $query->whereNotIn('status', ['draft', 'cancelled']);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter: Tipe Rapat (daring, luring, hybrid)
        if ($request->filled('tipe_rapat')) {
            $query->where('tipe_rapat', $request->query('tipe_rapat'));
        }

        // Filter: Jenis Rapat
        if ($request->filled('jenis_rapat')) {
            $query->where('jenis_rapat', $request->query('jenis_rapat'));
        }

        // Filter: Search title or location
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('judul_rapat', 'like', "%{$search}%")
                    ->orWhere('lokasi_ruang', 'like', "%{$search}%");
            });
        }

        // Filter: Unit (for Administrator)
        if ($request->filled('unit_id') && $user->isAdministrator()) {
            $unitId = (int) $request->query('unit_id');
            $query->whereHas('units', fn ($q) => $q->where('units.id', $unitId));
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $agendas = $query->latest('waktu_mulai')->paginate($perPage);

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

        $agenda = $agendaService->updateAgenda(
            agenda: $agenda,
            actor: $request->user(),
            validated: $request->validated(),
            isAllUnits: $request->boolean('is_all_units', true),
            suratEdaran: $request->file('surat_edaran')
        );

        $agenda->load(['creator.unit', 'pimpinan.unit', 'notulis.unit', 'units', 'documentations']);

        return response()->json([
            'success' => true,
            'message' => "Agenda rapat '{$agenda->judul_rapat}' berhasil diperbarui.",
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
        $statusText = $agendaService->updateStatus($agenda, $validated['status']);

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

