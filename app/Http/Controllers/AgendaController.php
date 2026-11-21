<?php

namespace App\Http\Controllers;

use App\Http\Requests\Agenda\AddAgendaParticipantsRequest;
use App\Http\Requests\Agenda\StoreAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaRequest;
use App\Http\Requests\Agenda\UpdateAgendaStatusRequest;
use App\Http\Requests\Agenda\UpdateMinutesRequest;
use App\Http\Requests\Agenda\UpdateRolesRequest;
use App\Http\Requests\Agenda\UpdateSuratEdaranRequest;
use App\Models\Agenda;
use App\Models\AgendaDocumentation;
use App\Models\Unit;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\SuratEdaranService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
            ->with(['creator.unit', 'units'])
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
                    $uid = (int) $unitId;
                    $query->where(function ($q) use ($uid) {
                        $q->where('is_all_units', true)
                            ->orWhereHas('units', function ($sub) use ($uid) {
                                $sub->where('units.id', $uid);
                            })
                            ->orWhereHas('invitedUsers', function ($sub) use ($uid) {
                                $sub->where('users.unit_id', $uid);
                            });
                    });
                }
            }
        }

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('judul_rapat', "%{$search}%")
                    ->orWhereLike('lokasi_ruang', "%{$search}%");
            });
        }

        $agendas = $query->orderBy('waktu_mulai', 'desc')->paginate(9)->withQueryString();
        if (method_exists(Agenda::class, 'preloadTotalInvited')) {
            Agenda::preloadTotalInvited($agendas->getCollection());
        }
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
            'cancelled' => (int) $rawCounts->get('cancelled', 0),
        ];

        return view('agendas.index', compact('agendas', 'currentUser', 'statusCounts', 'units'));
    }

    /**
     * Async paginated candidate search for Web ASN role & participant pickers.
     */
    public function candidates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $agendaId = $request->query('agenda_id');
        $targetAgenda = $agendaId ? Agenda::find((int) $agendaId) : null;

        $canManageForAgenda = $targetAgenda !== null && Gate::allows('manageParticipants', $targetAgenda);
        $isActivePimpinan = $user !== null && Agenda::query()
            ->where('pimpinan_id', $user->id)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->exists();

        if (! Gate::allows('create', Agenda::class) && ! $canManageForAgenda && ! $isActivePimpinan) {
            Gate::authorize('create', Agenda::class);
        }

        $query = User::query()->where('is_active', true)->with('unit');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nip', "%{$search}%")
                    ->orWhereLike('username', "%{$search}%")
                    ->orWhereHas('unit', function ($uq) use ($search) {
                        $uq->whereLike('nama_unit', "%{$search}%")
                            ->orWhereLike('kode_unit', "%{$search}%");
                    });
            });
        }

        if ($unitId = $request->query('unit_id')) {
            $query->where('unit_id', (int) $unitId);
        }

        $selectedIds = collect((array) $request->query('selected_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($request->boolean('only_selected')) {
            $selectedUsers = $selectedIds->isEmpty()
                ? collect()
                : (clone $query)->whereIn('id', $selectedIds)->orderBy('name')->limit(100)->get();

            return response()->json([
                'success' => true,
                'data' => $selectedUsers->map(fn (User $u) => $this->formatCandidatePayload($u))->values(),
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $selectedUsers->count(),
                    'total' => $selectedUsers->count(),
                    'has_more' => false,
                ],
            ]);
        }

        $perPage = min(max((int) $request->query('per_page', 25), 1), 25);
        $paginator = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (User $u) => $this->formatCandidatePayload($u))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
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

        $priorityIds = collect([
            $currentUser->id,
            old('pimpinan_id'),
            old('notulis_id'),
            ...((array) old('user_ids', [])),
        ]);
        $users = $this->loadInitialCandidateUsers($priorityIds);
        $totalActiveUsers = User::query()->where('is_active', true)->count();

        return view('agendas.create', compact('units', 'currentUser', 'users', 'totalActiveUsers'));
    }

    /**
     * Store a newly created agenda in storage.
     */
    public function store(StoreAgendaRequest $request, AgendaService $agendaService): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        $suratEdaranType = $request->input('surat_edaran_type', 'upload');
        $suratEdaranFile = ($suratEdaranType === 'upload') ? $request->file('surat_edaran') : null;

        $agenda = $agendaService->createAgenda(
            creator: $user,
            validated: $validated,
            isAllUnits: $request->boolean('is_all_units', true),
            suratEdaran: $suratEdaranFile,
        );

        if ($agenda->surat_edaran_type === 'digital' && $request->boolean('open_surat_edaran_after_save', true)) {
            return redirect()->route('admin.agendas.surat-edaran', $agenda)
                ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil dibuat. Silakan sesuaikan isi naskah surat undangan resmi.");
        }

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
            'invitedUsers.unit',
        ]);
        $agenda->loadCount('attendances');

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'desc')
            ->paginate(8, ['*'], 'page_attendees')
            ->withQueryString();
        $attendances->getCollection()->each(fn ($att) => $att->setRelation('agenda', $agenda));

        // Preload pimpinan & notulis attendance without firing extra queries if missing from current page
        $roleUserIds = array_values(array_unique(array_filter([
            $agenda->effective_pimpinan?->id,
            $agenda->effective_notulis?->id,
        ])));
        if ($roleUserIds !== []) {
            $loadedFromPage = $attendances->getCollection()->whereIn('user_id', $roleUserIds)->keyBy('user_id');
            $missingIds = array_values(array_diff($roleUserIds, $loadedFromPage->keys()->all()));
            $extraRecords = $missingIds !== []
                ? $agenda->attendances()->whereIn('user_id', $missingIds)->get()->keyBy('user_id')
                : collect();
            $roleAttendances = $loadedFromPage->merge($extraRecords)->values();
            $roleAttendances->each(fn ($att) => $att->setRelation('agenda', $agenda));
            $agenda->setRelation('attendances', $roleAttendances);
        } else {
            $agenda->setRelation('attendances', collect());
        }

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        $currentUser = Auth::user();
        $myAttendance = $agenda->attendances()->where('user_id', $currentUser->id)->first();
        $myAttendance?->setRelation('agenda', $agenda);
        $myAttendance?->setRelation('agenda', $agenda);

        $canUpdateAgenda = Gate::allows('update', $agenda);
        $totalActiveUsers = $canUpdateAgenda ? User::query()->where('is_active', true)->count() : 0;
        $users = $canUpdateAgenda
            ? $this->loadInitialCandidateUsers(collect([
                $currentUser->id,
                $agenda->created_by,
                $agenda->pimpinan_id,
                $agenda->notulis_id,
                old('pimpinan_id'),
                old('notulis_id'),
                ...$agenda->invitedUsers->pluck('id')->all(),
            ]))
            : collect();

        $backUrl = $this->resolveSafeAgendaIndexBackUrl();

        return view('agendas.show', compact(
            'agenda',
            'attendances',
            'documentations',
            'currentUser',
            'myAttendance',
            'users',
            'totalActiveUsers',
            'backUrl'
        ));
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
            'invitedUsers.unit',
        ]);

        $documentations = $agenda->documentations()
            ->latest('id')
            ->paginate(6, ['*'], 'page_docs')
            ->withQueryString();

        $currentUser = Auth::user();
        $myAttendance = $agenda->attendances()->where('user_id', $currentUser->id)->first();
        $myAttendance?->setRelation('agenda', $agenda);

        return view('agendas.staff_show', compact('agenda', 'documentations', 'currentUser', 'myAttendance'));
    }

    /**
     * Show the form for editing the specified agenda.
     */
    public function edit(Agenda $agenda): View|RedirectResponse
    {
        try {
            Gate::authorize('update', $agenda);

            $agenda->load([
                'creator.unit',
                'units',
                'invitedUsers.unit',
                'pimpinan.unit',
                'notulis.unit',
            ]);
            $agenda->loadCount(['attendances', 'documentations']);
            $currentUser = Auth::user();
            $currentUser->load('unit');
            $units = Unit::active()->orderBy('nama_unit')->get();

            $priorityIds = collect([
                $currentUser->id,
                $agenda->created_by,
                $agenda->pimpinan_id,
                $agenda->notulis_id,
                old('pimpinan_id'),
                old('notulis_id'),
                ...((array) old('user_ids', $agenda->invitedUsers->pluck('id')->all())),
            ]);
            $users = $this->loadInitialCandidateUsers($priorityIds);
            $totalActiveUsers = User::query()->where('is_active', true)->count();

            return view('agendas.edit', compact('agenda', 'units', 'currentUser', 'users', 'totalActiveUsers'));
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Gagal memuat formulir edit agenda rapat', [
                'agenda_id' => $agenda->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.agendas.show', $agenda)
                ->with('error', 'Terjadi kendala saat memuat formulir edit agenda. Silakan coba kembali beberapa saat lagi.');
        }
    }

    /**
     * Update the specified agenda in storage.
     */
    public function update(UpdateAgendaRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $updatedAgenda = $agendaService->updateAgenda(
                agenda: $agenda,
                actor: Auth::user(),
                validated: $validated,
                isAllUnits: $request->boolean('is_all_units', true),
                suratEdaran: $request->file('surat_edaran'),
            );

            if ($updatedAgenda->surat_edaran_type === 'digital' && $request->boolean('open_surat_edaran_after_save')) {
                return redirect()->route('admin.agendas.surat-edaran', $updatedAgenda)
                    ->with('success', "Agenda rapat '{$updatedAgenda->judul_rapat}' berhasil diperbarui. Silakan sesuaikan isi naskah surat undangan resmi.");
            }
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui agenda rapat', [
                'agenda_id' => $agenda->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Terjadi kendala pada pemrosesan server saat memperbarui agenda: '.$e->getMessage());
        }

        return redirect()->route('admin.agendas.show', $agenda)
            ->with('success', "Agenda rapat '{$updatedAgenda->judul_rapat}' berhasil diperbarui.");
    }

    /**
     * Load initial minimal active ASN collection (selected/relevant + first 25) to avoid DOM bloat.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $priorityIds
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function loadInitialCandidateUsers(\Illuminate\Support\Collection $priorityIds)
    {
        $ids = $priorityIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $priorityUsers = $ids->isEmpty()
            ? collect()
            : User::query()->where('is_active', true)->whereIn('id', $ids)->with('unit')->orderBy('name')->get();

        $initialPageUsers = User::query()
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->limit(25)
            ->get();

        return $priorityUsers->merge($initialPageUsers)->unique('id')->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Format single candidate payload for async picker JSON responses.
     *
     * @return array<string, mixed>
     */
    private function formatCandidatePayload(User $u): array
    {
        return [
            'id' => (int) $u->id,
            'name' => (string) $u->name,
            'nip' => (string) ($u->nip ?? '-'),
            'unit_id' => $u->unit_id ? (int) $u->unit_id : null,
            'unit_code' => (string) ($u->unit?->kode_unit ?? 'Pusat'),
            'unit_name' => (string) ($u->unit?->nama_unit ?? 'Pusat'),
            'initials' => strtoupper(substr((string) $u->name, 0, 2)),
        ];
    }

    /**
     * Resolve safe back URL preserving /admin/agendas filters without open redirect risk.
     */
    private function resolveSafeAgendaIndexBackUrl(): string
    {
        $indexUrl = route('admin.agendas.index');
        $previousUrl = url()->previous();
        if (! $previousUrl) {
            return $indexUrl;
        }

        $parsedPrev = parse_url($previousUrl);
        $parsedIndex = parse_url($indexUrl);
        if (! is_array($parsedPrev) || ! is_array($parsedIndex)) {
            return $indexUrl;
        }

        $samePath = ($parsedPrev['path'] ?? '') === ($parsedIndex['path'] ?? '');
        $allowedHosts = array_filter([
            $parsedIndex['host'] ?? null,
            request()->getHost(),
        ]);
        $sameHost = empty($parsedPrev['host']) || in_array($parsedPrev['host'], $allowedHosts, true);

        return ($samePath && $sameHost) ? $previousUrl : $indexUrl;
    }

    /**
     * Remove the specified agenda from storage.
     * Dapat dilakukan oleh Administrator (semua unit) atau Admin Unit yang berkaitan.
     */
    public function destroy(Agenda $agenda, AgendaService $agendaService): RedirectResponse
    {
        Gate::authorize('delete', $agenda);

        $judul = $agenda->judul_rapat;

        try {
            $agendaService->deleteAgenda($agenda, Auth::user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.agendas.index')
            ->with('success', "Agenda rapat '{$judul}' berhasil dihapus.");
    }

    /**
     * Update status of the agenda (e.g. start meeting, complete meeting).
     */
    public function updateStatus(UpdateAgendaStatusRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse
    {
        Gate::authorize('manageStatus', $agenda);

        $validated = $request->validated();

        try {
            $statusText = $agendaService->updateStatus(
                $agenda,
                $validated['status'],
                Auth::user(),
                (bool) ($validated['allow_schedule_overlap'] ?? false)
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Status agenda rapat berhasil diubah menjadi {$statusText}.");
    }

    /**
     * Add ad-hoc ASN participants to an agenda (e.g. during active session).
     */
    public function addParticipants(AddAgendaParticipantsRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse|JsonResponse
    {
        try {
            $updated = $agendaService->addParticipants(
                $agenda,
                $request->validated('user_ids'),
                Auth::user()
            );
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Peserta ASN tambahan berhasil ditambahkan ke agenda.',
                'data' => [
                    'agenda_id' => $updated->id,
                    'total_invited' => $updated->total_invited,
                    'invited_users' => $updated->invitedUsers->map(fn (User $u) => $this->formatCandidatePayload($u))->values(),
                ],
            ]);
        }

        return back()->with('success', 'Peserta ASN tambahan berhasil ditambahkan ke agenda.');
    }

    /**
     * Update pimpinan and notulis roles dynamically (e.g. during meeting or from show page).
     */
    public function updateRoles(UpdateRolesRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse
    {
        $roles = $request->roles();
        $agendaService->updateRoles($agenda, $roles);

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
            'invitedUsers.unit',
        ]);

        $attendances = $agenda->attendances()
            ->with('user.unit')
            ->orderBy('signed_at', 'asc')
            ->get();
        $attendances->each(fn ($att) => $att->setRelation('agenda', $agenda));
        $agenda->setRelation('attendances', $attendances);

        $config = $agenda->resolved_report_config;

        $documentations = $agenda->documentations()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $signerCandidates = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nip']);

        $signer3SigUrl = null;
        if (! empty($config['signer3_user_id'])) {
            $signer3Att = $attendances->firstWhere('user_id', (int) $config['signer3_user_id']);
            if ($signer3Att && $signer3Att->signature_path) {
                $signer3SigUrl = route('attendances.signature', $signer3Att);
            }
        }

        return view('agendas.notulen', compact(
            'agenda',
            'documentations',
            'config',
            'attendances',
            'signerCandidates',
            'signer3SigUrl',
        ));
    }

    /**
     * Update Notulensi, Kesimpulan, and Upload Documentation Photos & Sync Document Configuration (Single Action).
     */
    public function updateNotulen(UpdateMinutesRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse|JsonResponse
    {
        $expectedUpdatedAt = $request->header('X-Expected-Updated-At');
        $forceOverwrite = $request->boolean('force_overwrite') || $request->header('X-Force-Overwrite') === '1';
        $currentUpdatedAt = $agenda->updated_at?->toIso8601String();

        if (is_string($expectedUpdatedAt) && $expectedUpdatedAt !== '' && ! $forceOverwrite && $currentUpdatedAt !== null && $expectedUpdatedAt !== $currentUpdatedAt) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notulensi atau pengaturan dokumen telah diperbarui oleh pengguna lain. Muat ulang versi terbaru atau pilih Timpa Paksa.',
                    'current_updated_at' => $currentUpdatedAt,
                ], 409);
            }

            return back()
                ->withInput()
                ->with('error', 'Notulensi atau pengaturan dokumen telah diperbarui oleh pengguna lain sebelum Anda menyimpan.');
        }

        $validated = $request->validated();

        $agendaService->updateNotulen(
            agenda: $agenda,
            data: [
                'notulensi' => $validated['notulensi'] ?? null,
                'kesimpulan' => $validated['kesimpulan'] ?? null,
                'photos' => $request->file('photos'),
                'captions' => $request->input('captions', []),
                'existing_captions' => $request->input('existing_captions', []),
            ],
            request: $request,
        );

        if ($request->expectsJson()) {
            $agenda->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Notulensi dan pengaturan dokumen rapat berhasil disimpan.',
                'updated_at' => $agenda->updated_at?->toIso8601String(),
                'saved_at_label' => now()->format('H:i'),
            ]);
        }

        $redirectRoute = Auth::user()?->isStaff()
            ? route('agendas.show', $agenda)
            : route('admin.agendas.show', $agenda);

        return redirect($redirectRoute)
            ->with('success', 'Notulensi dan pengaturan dokumen rapat berhasil disimpan.');
    }

    /**
     * Delete a single photo documentation item.
     */
    public function deleteDocumentation(Request $request, Agenda $agenda, AgendaDocumentation $documentation, AgendaService $agendaService): RedirectResponse|JsonResponse
    {
        Gate::authorize('manageMinutes', $agenda);

        try {
            $agendaService->deleteDocumentation($agenda, $documentation);
        } catch (\InvalidArgumentException $e) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Foto dokumentasi berhasil dihapus.',
                'documentation_id' => $documentation->id,
                'remaining_count' => $agenda->documentations()->count(),
            ]);
        }

        return back()->with('success', 'Foto dokumentasi berhasil dihapus.');
    }

    /**
     * Show digital circular letter / meeting invitation workstation.
     */
    public function suratEdaran(Agenda $agenda): View
    {
        Gate::authorize('manageCircular', $agenda);

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'invitedUsers.unit',
        ]);

        $config = $agenda->resolved_surat_edaran_config;

        return view('agendas.surat_edaran', compact('agenda', 'config'));
    }

    /**
     * Update digital circular letter / meeting invitation.
     */
    public function updateSuratEdaran(UpdateSuratEdaranRequest $request, Agenda $agenda, SuratEdaranService $suratEdaranService): RedirectResponse|JsonResponse
    {
        try {
            $agenda->load([
                'creator.unit',
                'pimpinan.unit',
                'notulis.unit',
                'units',
                'invitedUsers.unit',
            ]);

            $suratEdaranService->saveDigitalSurat($agenda, $request->validated(), $request);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan naskah surat dinas rapat', [
                'agenda_id' => $agenda->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kendala saat menyimpan naskah surat dinas: '.$e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan naskah surat dinas: '.$e->getMessage());
        }

        if ($request->expectsJson()) {
            $agenda->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Naskah surat dinas resmi berhasil disimpan.',
                'updated_at' => $agenda->updated_at?->toIso8601String(),
                'saved_at_label' => now()->format('H:i'),
                'config' => $agenda->resolved_surat_edaran_config,
            ]);
        }

        $redirectRoute = Auth::user()?->isStaff()
            ? route('agendas.show', $agenda)
            : route('admin.agendas.show', $agenda);

        return redirect($redirectRoute)
            ->with('success', 'Naskah surat dinas resmi berhasil disimpan dan siap didistribusikan.');
    }

    /**
     * Export digital circular letter / meeting invitation as PDF.
     */
    public function exportSuratEdaranPdf(Request $request, Agenda $agenda, SuratEdaranService $suratEdaranService): Response
    {
        if (Auth::user()?->isStaff()) {
            Gate::authorize('viewStaff', $agenda);
        } else {
            Gate::authorize('view', $agenda);
        }

        $agenda->load([
            'creator.unit',
            'pimpinan.unit',
            'notulis.unit',
            'units',
            'invitedUsers.unit',
        ]);

        return $suratEdaranService->downloadPdfResponse($agenda, $request->boolean('download'));
    }

    /**
     * Export digital circular letter / meeting invitation as Microsoft Word (.docx with .doc fallback).
     */
    public function exportSuratEdaranWord(Request $request, Agenda $agenda, SuratEdaranService $suratEdaranService): Response|RedirectResponse
    {
        if (Auth::user()?->isStaff()) {
            Gate::authorize('viewStaff', $agenda);
        } else {
            Gate::authorize('view', $agenda);
        }

        try {
            $agenda->load([
                'creator.unit',
                'pimpinan.unit',
                'notulis.unit',
                'units',
                'invitedUsers.unit',
            ]);

            return $suratEdaranService->downloadWordResponse($agenda);
        } catch (\Throwable $e) {
            try {
                Log::warning('Gagal mengekspor Surat Dinas OpenXML Word (.docx), mencoba fallback format Word (.doc): '.$e->getMessage(), [
                    'agenda_id' => $agenda->id,
                    'exception' => $e,
                ]);
            } catch (\Throwable) {
                // Suppress logger I/O errors so fallback still runs
            }

            try {
                return $suratEdaranService->downloadLegacyWordResponse($agenda);
            } catch (\Throwable $fallbackException) {
                try {
                    Log::error('Gagal mengekspor Surat Dinas Word fallback (.doc): '.$fallbackException->getMessage(), [
                        'agenda_id' => $agenda->id,
                        'exception' => $fallbackException,
                    ]);
                } catch (\Throwable) {
                    // Suppress logger I/O errors so safe redirect still runs
                }

                $errorMsg = 'Gagal mengekspor dokumen Word Surat Dinas. Silakan coba kembali atau gunakan format PDF.';
                $referer = (string) $request->headers->get('referer', '');
                if ($referer !== '') {
                    $refererHost = parse_url($referer, PHP_URL_HOST);
                    $refererPath = (string) parse_url($referer, PHP_URL_PATH);

                    if (
                        ($refererHost === null || $refererHost === $request->getHost())
                        && ! str_contains($refererPath, '/surat-edaran/export/word')
                        && ! str_contains($refererPath, '/surat-edaran/word')
                    ) {
                        return back()->with('error', $errorMsg);
                    }
                }

                if (Auth::user()?->isStaff()) {
                    return redirect()->route('agendas.show', $agenda)->with('error', $errorMsg);
                }

                return redirect()->route('admin.agendas.surat-edaran', $agenda)->with('error', $errorMsg);
            }
        }
    }
}
