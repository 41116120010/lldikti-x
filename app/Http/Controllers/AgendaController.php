<?php

namespace App\Http\Controllers;

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
                    $query->whereHas('units', function ($sub) use ($unitId) {
                        $sub->where('units.id', $unitId);
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
    public function store(StoreAgendaRequest $request, AgendaService $agendaService, SuratEdaranService $suratEdaranService): RedirectResponse
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

        if ($suratEdaranType === 'digital') {
            $agenda->update(['surat_edaran_type' => 'digital']);
            $suratEdaranService->saveDigitalSurat($agenda, $agenda->getDefaultSuratEdaranConfig());

            if ($request->boolean('open_surat_edaran_after_save', true)) {
                return redirect()->route('admin.agendas.surat-edaran', $agenda)
                    ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil dibuat. Silakan sesuaikan isi naskah surat undangan resmi.");
            }
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
            'invitedUsers.unit',
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
    public function edit(Agenda $agenda): View|RedirectResponse
    {
        try {
            Gate::authorize('update', $agenda);

            $agenda->load([
                'creator.unit',
                'units',
                'invitedUsers',
                'pimpinan.unit',
                'notulis.unit',
            ]);
            $agenda->loadCount(['attendances', 'documentations']);
            $currentUser = Auth::user();
            $currentUser->load('unit');
            $units = Unit::active()->orderBy('nama_unit')->get();
            $users = User::query()->where('is_active', true)->with('unit')->orderBy('name')->get();

            return view('agendas.edit', compact('agenda', 'units', 'currentUser', 'users'));
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
            $agendaService->updateAgenda(
                agenda: $agenda,
                actor: Auth::user(),
                validated: $validated,
                isAllUnits: $request->boolean('is_all_units', true),
                suratEdaran: $request->file('surat_edaran'),
            );

            if ($request->input('surat_edaran_type') === 'digital') {
                $agenda->update(['surat_edaran_type' => 'digital']);
                $agenda->load([
                    'creator.unit',
                    'pimpinan.unit',
                    'notulis.unit',
                    'units',
                    'invitedUsers.unit',
                ]);
                if (empty($agenda->surat_edaran_config)) {
                    app(SuratEdaranService::class)->saveDigitalSurat($agenda, $agenda->getDefaultSuratEdaranConfig());
                } else {
                    app(SuratEdaranService::class)->generateAndStorePdf($agenda);
                }

                if ($request->boolean('open_surat_edaran_after_save')) {
                    return redirect()->route('admin.agendas.surat-edaran', $agenda)
                        ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil diperbarui. Silakan sesuaikan isi naskah surat undangan resmi.");
                }
            } elseif ($request->hasFile('surat_edaran')) {
                $agenda->update(['surat_edaran_type' => 'upload']);
            } elseif ($agenda->is_surat_edaran_digital) {
                $agenda->load([
                    'creator.unit',
                    'pimpinan.unit',
                    'notulis.unit',
                    'units',
                    'invitedUsers.unit',
                ]);
                app(SuratEdaranService::class)->generateAndStorePdf($agenda);
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
            ->with('success', "Agenda rapat '{$agenda->judul_rapat}' berhasil diperbarui.");
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
            $statusText = $agendaService->updateStatus($agenda, $validated['status'], Auth::user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Status agenda rapat berhasil diubah menjadi {$statusText}.");
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

        $config = $agenda->resolved_report_config;

        $documentations = $agenda->documentations()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('agendas.notulen', compact('agenda', 'documentations', 'config', 'attendances'));
    }

    /**
     * Update Notulensi, Kesimpulan, and Upload Documentation Photos & Sync Document Configuration (Single Action).
     */
    public function updateNotulen(UpdateMinutesRequest $request, Agenda $agenda, AgendaService $agendaService): RedirectResponse
    {
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
    public function updateSuratEdaran(UpdateSuratEdaranRequest $request, Agenda $agenda, SuratEdaranService $suratEdaranService): RedirectResponse
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

            return back()->withInput()->with('error', 'Terjadi kendala saat menyimpan naskah surat dinas: '.$e->getMessage());
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
     * Export digital circular letter / meeting invitation as Microsoft Word (.docx).
     */
    public function exportSuratEdaranWord(Agenda $agenda, SuratEdaranService $suratEdaranService): Response
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

        return $suratEdaranService->downloadWordResponse($agenda);
    }
}
