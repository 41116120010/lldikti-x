@extends('layouts.app')

@section('title', 'Portal Presensi Pegawai')
@section('heading', 'Portal Presensi Kehadiran')
@section('subtitle', 'Daftar sesi presensi rapat aktif dan jadwal kedinasan Anda')

@section('content')
<div class="space-y-6">
    <!-- Section 1: Ongoing Meetings Ready for Attendance -->
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                <h3 class="font-extrabold text-slate-950 text-sm">Sesi Rapat Sedang Berlangsung (Buka Presensi)</h3>
                <span class="text-xs text-slate-700 font-mono font-bold hidden sm:inline">&bull; {{ $ongoingAgendas->count() }} Rapat Aktif</span>
            </div>
            <a href="{{ route('attendances.history') }}" class="button small secondary flex items-center gap-1.5 text-xs shrink-0 font-bold">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Riwayat Presensi Saya</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($ongoingAgendas as $agenda)
                @php
                    $hasAttended = $agenda->hasUserAttended($user);
                @endphp
                <div class="bg-white rounded-2xl p-5 border-2 {{ $hasAttended ? 'border-slate-300' : 'border-slate-900 bg-slate-50/50' }} shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="status text-[11px] font-bold {{ $hasAttended ? 'completed' : 'ongoing' }}">
                                {{ $hasAttended ? 'Sudah Hadir' : 'Sesi Presensi Dibuka' }}
                            </span>
                            <span class="text-[11px] font-mono font-bold uppercase bg-slate-100 px-2 py-0.5 rounded text-slate-900 border border-slate-300">
                                {{ $agenda->tipe_rapat }}
                            </span>
                        </div>

                        <h4 class="font-bold text-slate-950 text-sm leading-snug">
                            {{ $agenda->judul_rapat }}
                        </h4>

                        <div class="text-xs text-slate-800 space-y-1 font-medium">
                            <div class="flex items-center gap-1.5">
                                <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span class="font-bold text-slate-900">{{ $agenda->waktu_mulai->translatedFormat('d M Y') }} &bull; {{ $agenda->waktu_mulai->format('H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-xs text-slate-700 font-bold">
                            {{ $agenda->attendances->count() }} pegawai telah hadir
                        </span>

                        @if($hasAttended)
                            @php
                                $myAttendance = $agenda->attendances->firstWhere('user_id', $user->id);
                            @endphp
                            <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="button small secondary text-xs font-bold text-slate-900 border-slate-300 hover:bg-slate-100">
                                Bukti Kehadiran
                            </a>
                        @else
                            <a href="{{ route('attendances.create', $agenda) }}" class="button small flex items-center gap-1.5 text-xs bg-slate-950 hover:bg-slate-800 text-white shadow-xs font-bold">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <span>Isi Presensi (Selfie & TTD)</span>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-300 text-xs text-slate-700 font-medium">
                    <svg class="mx-auto mb-2 text-slate-400" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Saat ini tidak ada sesi rapat yang sedang berlangsung untuk unit kerja Anda.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section 2: Upcoming Meetings & Personal Attendance History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4">
        <!-- Upcoming Scheduled Meetings -->
        <div class="panel">
            <div class="toolbar">
                <div class="flex items-center gap-2">
                    <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <h3 class="font-bold text-slate-900 text-sm">Agenda Rapat Mendatang</h3>
                </div>
            </div>

            <div class="p-4 divide-y divide-slate-200">
                @forelse($scheduledAgendas as $agenda)
                    <div class="py-3 first:pt-0 last:pb-0 space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-950">{{ $agenda->judul_rapat }}</span>
                            <span class="text-slate-900 font-mono text-[11px] font-bold">{{ $agenda->waktu_mulai->format('d/m/Y') }}</span>
                        </div>
                        <div class="text-slate-700 text-[11px] flex items-center gap-2 font-medium">
                            <span class="font-bold text-slate-900">{{ $agenda->waktu_mulai->format('H:i') }} WIB</span>
                            <span>&bull;</span>
                            <span>{{ $agenda->lokasi_ruang ?? 'Daring' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-600 font-medium">
                        Belum ada agenda rapat terjadwal berikutnya.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Personal Attendances -->
        <div class="panel">
            <div class="toolbar">
                <div class="flex items-center gap-2">
                    <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <h3 class="font-bold text-slate-900 text-sm">Riwayat Kehadiran Terakhir Anda</h3>
                </div>
                <a href="{{ route('attendances.history') }}" class="text-xs text-slate-900 hover:text-slate-950 font-bold underline">Lihat Semua</a>
            </div>

            <div class="p-4 divide-y divide-slate-200">
                @forelse($recentAttendances as $att)
                    <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3 text-xs">
                        <div class="min-w-0">
                            <div class="font-bold text-slate-950 truncate">{{ $att->agenda->judul_rapat }}</div>
                            <div class="text-[11px] text-slate-600 font-mono font-medium">Hadir: {{ $att->signed_at->translatedFormat('d M Y, H:i') }} WIB</div>
                        </div>
                        <a href="{{ route('attendances.success', [$att->agenda, $att]) }}" class="button small secondary text-xs shrink-0 font-bold">
                            Bukti
                        </a>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-600 font-medium">
                        Anda belum memiliki riwayat presensi rapat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
