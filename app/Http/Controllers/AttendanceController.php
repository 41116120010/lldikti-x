<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Models\Agenda;
use App\Models\Attendance;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Ongoing agendas available for this user to attend right now
        $ongoingAgendas = Agenda::visibleTo($user)
            ->where('status', 'ongoing')
            ->with(['creator', 'units', 'attendances'])
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        // Upcoming scheduled meetings
        $scheduledAgendas = Agenda::visibleTo($user)
            ->where('status', 'scheduled')
            ->with(['creator', 'units'])
            ->orderBy('waktu_mulai', 'asc')
            ->limit(6)
            ->get();

        // Personal recent attendances
        $recentAttendances = Attendance::where('user_id', $user->id)
            ->with('agenda')
            ->latest('signed_at')
            ->limit(5)
            ->get();

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
            return redirect()->route('admin.agendas.show', $agenda)
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
    public function store(StoreAttendanceRequest $request, Agenda $agenda): RedirectResponse
    {
        $user = Auth::user();

        // Double check eligibility & attendance status
        if ($agenda->hasUserAttended($user)) {
            return redirect()->route('admin.agendas.show', $agenda)
                ->with('info', 'Anda telah melakukan presensi pada agenda rapat ini.');
        }

        if ($agenda->status !== 'ongoing') {
            return redirect()->route('admin.agendas.show', $agenda)
                ->with('error', 'Sesi presensi untuk agenda rapat ini tidak sedang dibuka.');
        }

        $attendance = DB::transaction(function () use ($request, $agenda, $user) {
            $selfiePath = $this->saveImageFile(
                dataUri: $request->input('selfie_data'),
                file: $request->file('selfie_file'),
                directory: "attendances/{$agenda->id}/selfies",
                prefix: "selfie_{$user->id}"
            );

            $signaturePath = $this->saveImageFile(
                dataUri: $request->input('signature_data'),
                file: $request->file('signature_file'),
                directory: "attendances/{$agenda->id}/signatures",
                prefix: "sig_{$user->id}"
            );

            $record = Attendance::create([
                'agenda_id' => $agenda->id,
                'user_id' => $user->id,
                'signed_at' => now(),
                'selfie_path' => $selfiePath,
                'signature_path' => $signaturePath,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            ActivityLogger::log(
                type: 'RECORD_ATTENDANCE',
                description: "Pegawai {$user->name} (NIP: {$user->nip}) melakukan presensi pada agenda '{$agenda->judul_rapat}'.",
                targetModel: Attendance::class,
                targetId: $record->id,
                properties: [
                    'agenda_id' => $agenda->id,
                    'user_id' => $user->id,
                    'signed_at' => $record->signed_at->toDateTimeString(),
                ]
            );

            return $record;
        });

        return redirect()->route('attendances.success', [$agenda, $attendance])
            ->with('success', 'Presensi kehadiran rapat Anda berhasil dicatat dan diverifikasi!');
    }

    /**
     * Display the official attendance confirmation receipt / digital badge.
     */
    public function success(Agenda $agenda, Attendance $attendance): View
    {
        Gate::authorize('view', $attendance);

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

    /**
     * Helper to process and store Base64 Data URI or File Upload to disk safely.
     */
    private function saveImageFile(?string $dataUri, $file, string $directory, string $prefix): string
    {
        // 1. If uploaded as direct file
        if ($file) {
            $filename = "{$prefix}_" . Str::random(16) . '.' . $file->getClientOriginalExtension();
            return $file->storeAs($directory, $filename, 'public');
        }

        // 2. If uploaded as Base64 Data URI from Canvas
        if ($dataUri && preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            $data = substr($dataUri, strpos($dataUri, ',') + 1);
            $extension = strtolower($type[1]); // jpg, png, webp, jpeg

            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $decoded = base64_decode($data);
            if ($decoded === false) {
                throw new \InvalidArgumentException('Format gambar base64 tidak valid.');
            }

            $filename = "{$prefix}_" . Str::random(16) . ".{$extension}";
            $path = "{$directory}/{$filename}";

            Storage::disk('public')->put($path, $decoded);

            return $path;
        }

        throw new \InvalidArgumentException('Data berkas gambar tidak ditemukan.');
    }
}
