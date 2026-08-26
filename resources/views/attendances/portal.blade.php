@extends('layouts.app')

@section('title', 'Portal Presensi Pegawai')
@section('heading', 'Portal Presensi Kehadiran')
@section('subtitle', 'Daftar sesi presensi rapat aktif dan jadwal kedinasan Anda')

@section('content')
<div class="space-y-6">
    <!-- User Profile Header -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white rounded-2xl p-6 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center font-bold text-lg">
                {{ substr($user->name, 0, 2) }}
            </div>
            <div>
                <h2 class="text-base font-bold">{{ $user->name }}</h2>
                <p class="text-xs text-blue-100 font-mono">NIP: {{ $user->nip }} &bull; {{ $user->unit?->nama_unit ?? 'Tingkat Lembaga' }}</p>
            </div>
        </div>
        <a href="{{ route('attendances.history') }}" class="button small secondary flex items-center gap-1.5 text-xs bg-white/10 hover:bg-white/20 text-white border-white/20 self-start sm:self-auto">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Riwayat Presensi Pribadi</span>
        </a>
    </div>

    <!-- Section 1: Ongoing Meetings Ready for Attendance -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                <h3 class="font-bold text-slate-800 text-sm">Sesi Rapat Sedang Berlangsung (Buka Presensi)</h3>
            </div>
            <span class="text-xs text-slate-500 font-mono">{{ $ongoingAgendas->count() }} Rapat Aktif</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($ongoingAgendas as $agenda)
                @php
                    $hasAttended = $agenda->hasUserAttended($user);
                @endphp
                <div class="bg-white rounded-2xl p-5 border-2 {{ $hasAttended ? 'border-emerald-200' : 'border-amber-400 bg-amber-50/20' }} shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="status text-[11px] font-bold {{ $hasAttended ? 'completed' : 'ongoing' }}">
                                {{ $hasAttended ? '✓ Anda Sudah Hadir' : '● Sesi Presensi Dibuka' }}
                            </span>
                            <span class="text-[11px] font-mono uppercase bg-slate-100 px-2 py-0.5 rounded text-slate-600">
                                {{ $agenda->tipe_rapat }}
                            </span>
                        </div>

                        <h4 class="font-bold text-slate-900 text-sm leading-snug">
                            {{ $agenda->judul_rapat }}
                        </h4>

                        <div class="text-xs text-slate-500 space-y-1">
                            <div class="flex items-center gap-1.5">
                                <svg class="text-slate-400 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>{{ $agenda->waktu_mulai->translatedFormat('d M Y &bull; H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="text-slate-400 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>{{ $agenda->lokasi_ruang ?? 'Daring / Online Meeting' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">
                            {{ $agenda->attendances->count() }} pegawai telah hadir
                        </span>

                        @if($hasAttended)
                            @php
                                $myAttendance = $agenda->attendances->firstWhere('user_id', $user->id);
                            @endphp
                            <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="button small secondary text-xs text-emerald-700 border-emerald-300 hover:bg-emerald-50">
                                Bukti Kehadiran
                            </a>
                        @else
                            <a href="{{ route('attendances.create', $agenda) }}" class="button small flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-bold">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <span>Isi Presensi (Selfie & TTD)</span>
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-200 text-xs text-slate-500">
                    <svg class="mx-auto mb-2 text-slate-300" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
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
                    <svg class="text-blue-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <h3 class="font-bold text-slate-800 text-sm">Agenda Rapat Mendatang</h3>
                </div>
            </div>

            <div class="p-4 divide-y divide-slate-100">
                @forelse($scheduledAgendas as $agenda)
                    <div class="py-3 first:pt-0 last:pb-0 space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-900">{{ $agenda->judul_rapat }}</span>
                            <span class="text-blue-600 font-mono text-[11px]">{{ $agenda->waktu_mulai->format('d/m/Y') }}</span>
                        </div>
                        <div class="text-slate-500 text-[11px]">
                            🕒 {{ $agenda->waktu_mulai->format('H:i') }} WIB &bull; 📍 {{ $agenda->lokasi_ruang ?? 'Daring' }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400">
                        Belum ada agenda rapat terjadwal berikutnya.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Personal Attendances -->
        <div class="panel">
            <div class="toolbar">
                <div class="flex items-center gap-2">
                    <svg class="text-emerald-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <h3 class="font-bold text-slate-800 text-sm">Riwayat Kehadiran Terakhir Anda</h3>
                </div>
                <a href="{{ route('attendances.history') }}" class="text-xs text-blue-600 hover:underline">Lihat Semua</a>
            </div>

            <div class="p-4 divide-y divide-slate-100">
                @forelse($recentAttendances as $att)
                    <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3 text-xs">
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 truncate">{{ $att->agenda->judul_rapat }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">Hadir: {{ $att->signed_at->translatedFormat('d M Y, H:i') }} WIB</div>
                        </div>
                        <a href="{{ route('attendances.success', [$att->agenda, $att]) }}" class="button small secondary text-xs shrink-0">
                            Bukti
                        </a>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400">
                        Anda belum memiliki riwayat presensi rapat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
