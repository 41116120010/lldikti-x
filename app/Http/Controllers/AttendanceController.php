<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Services\ActivityLogger;
use App\Services\AttendanceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Display the attendance portal (ongoing and available meetings).
     */
    public function portal(): View
    {
        $user = Auth::user();

        // The card needs the headcount and this user's own record for the "Bukti"
        // link, so the relation is constrained to the current user and holds at
        // most one row per agenda instead of every attendee.
        $ongoingAgendas = Agenda::visibleTo($user)
            ->where('status', 'ongoing')
            ->with([
                'creator',
                'units',
                'attendances' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->withCount('attendances')
            ->orderBy('waktu_mulai', 'desc')
            ->paginate(6, ['*'], 'page_ongoing')
            ->withQueryString();

        // Upcoming scheduled meetings that have not passed yet
        $scheduledAgendas = Agenda::visibleTo($user)
            ->upcoming()
            ->with(['creator', 'units'])
            ->orderBy('waktu_mulai', 'asc')
            ->paginate(4, ['*'], 'page_scheduled')
            ->withQueryString();

        // Personal recent attendances
        $recentAttendances = Attendance::where('user_id', $user->id)
            ->with('agenda')
            ->latest('signed_at')
            ->paginate(4, ['*'], 'page_recent')
            ->withQueryString();

        return view('attendances.portal', compact('user', 'ongoingAgendas', 'scheduledAgendas', 'recentAttendances'));
    }

    /**
     * Show the interactive check-in page (Selfie + Signature Pad).
     */
    public function create(Agenda $agenda): View|RedirectResponse
    {
        $user = Auth::user();

        // Check if already attended
        $existingAttendance = Attendance::where('agenda_id', $agenda->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAttendance) {
            return redirect()->route('attendances.success', [$agenda, $existingAttendance])
                ->with('info', 'Anda telah melakukan presensi pada agenda rapat ini.');
        }

        // Validate agenda status
        if ($agenda->status !== 'ongoing') {
            $redirectRoute = ($user->isAdministrator() || $user->isAdmin())
                ? route('admin.agendas.show', $agenda)
                : route('agendas.show', $agenda);

            return redirect($redirectRoute)
                ->with('error', 'Sesi presensi untuk agenda rapat ini belum dibuka atau telah selesai.');
        }

        // Validate user eligibility
        if (!$agenda->isUserEligible($user)) {
            abort(403, 'Unit kerja Anda tidak termasuk dalam daftar undangan agenda rapat ini.');
        }

        return view('attendances.create', compact('agenda', 'user'));
    }

    /**
     * Store an attendance check-in record.
     */
    public function store(StoreAttendanceRequest $request, Agenda $agenda, AttendanceService $attendanceService): RedirectResponse
    {
        $user = Auth::user();

        // Double check eligibility & attendance status
        if ($agenda->hasUserAttended($user)) {
            $redirectRoute = ($user->isAdministrator() || $user->isAdmin())
                ? route('admin.agendas.show', $agenda)
                : route('agendas.show', $agenda);

            return redirect($redirectRoute)
                ->with('info', 'Anda telah melakukan presensi pada agenda rapat ini.');
        }

        if ($agenda->status !== 'ongoing') {
            $redirectRoute = ($user->isAdministrator() || $user->isAdmin())
                ? route('admin.agendas.show', $agenda)
                : route('agendas.show', $agenda);

            return redirect($redirectRoute)
                ->with('error', 'Sesi presensi untuk agenda rapat ini tidak sedang dibuka.');
        }

        $result = $attendanceService->recordAttendance($agenda, $user, [
            'selfie_data' => $request->input('selfie_data'),
            'selfie_file' => $request->file('selfie_file'),
            'signature_data' => $request->input('signature_data'),
            'signature_file' => $request->file('signature_file'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($result['is_duplicate']) {
            return redirect()->route('attendances.success', [$agenda, $result['attendance']])
                ->with('info', 'Presensi kehadiran Anda telah tercatat pada agenda rapat ini.');
        }

        return redirect()->route('attendances.success', [$agenda, $result['attendance']])
            ->with('success', 'Presensi kehadiran rapat Anda berhasil dicatat dan diverifikasi!');
    }

    /**
     * Display the official attendance confirmation receipt / digital badge.
     */
    public function success(Agenda $agenda, Attendance $attendance): View
    {
        // Ownership of the row: the attendance must genuinely belong to this agenda.
        //
        // Without this, any authenticated user could pair an arbitrary agenda ID
        // with one of their own attendance records. The receipt then rendered that
        // agenda's title, date and room next to their own proof of attendance —
        // an official-looking document naming the wrong meeting, and a way to read
        // details of meetings (including draft ones) they were never invited to.
        //
        // The pairing check is the whole fix, and it is deliberately not paired
        // with AgendaPolicy::viewStaff(): holding an attendance record already
        // proves the user attended this meeting. Blocking them once the meeting is
        // later marked 'cancelled' — which the delete-agenda guidance explicitly
        // recommends for a cancelled-but-held meeting — would revoke a receipt they
        // legitimately earned.
        //
        // Compare as integers: with PDO::ATTR_EMULATE_PREPARES enabled (the PHP
        // default for MySQL) primary keys arrive as numeric strings, so a strict
        // === against an int key would reject legitimate requests.
        abort_unless((int) $attendance->agenda_id === (int) $agenda->getKey(), 404);

        // Confirms the requester owns this record, or is an admin entitled to it.
        Gate::authorize('view', $attendance);

        // The receipt footer prints the meeting headcount. Resolved as a count
        // subquery so the view does not lazy-load the whole attendances relation.
        $agenda->loadCount('attendances');

        $attendance->load(['user.unit', 'agenda']);

        return view('attendances.success', compact('agenda', 'attendance'));
    }

    /**
     * Display personal attendance history for the logged-in user.
     */
    public function history(Request $request): View
    {
        $user = Auth::user();
        $query = Attendance::where('user_id', $user->id)
            ->with('agenda.creator')
            ->orderBy('signed_at', 'desc');

        if ($search = $request->input('search')) {
            $query->whereHas('agenda', function ($q) use ($search) {
                $q->where('judul_rapat', 'like', "%{$search}%")
                  ->orWhere('lokasi_ruang', 'like', "%{$search}%");
            });
        }

        $attendances = $query->paginate(10)->withQueryString();

        return view('attendances.history', compact('attendances', 'user'));
    }
}
