@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard Utama')
@section('subtitle', 'Ringkasan Aktivitas dan Agenda Rapat Kedinasan')

@section('content')
<div class="space-y-6">
    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            </div>
            <div>
                <label>Agenda Rapat</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['total_agendas'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <label>Sedang Berlangsung</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['ongoing_agendas'] ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
            </div>
            <div>
                <label>Terjadwal Mendatang</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['upcoming_agendas'] ?? 0 }}</div>
            </div>
        </div>

        @if($user->isAdministrator())
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <label>Total Pengguna</label>
                    <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['total_users'] ?? 0 }}</div>
                </div>
            </div>
        @elseif($user->isAdmin())
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div>
                    <label>Pegawai di Unit</label>
                    <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['total_users'] ?? 0 }}</div>
                </div>
            </div>
        @else
            <div class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div>
                    <label>Riwayat Kehadiran</label>
                    <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $stats['my_attendances'] ?? 0 }} <span class="text-sm font-sans font-semibold text-slate-600">Kali</span></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Grid: Agendas & Activity Logs -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Active Agendas (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Agenda Rapat Terkini</h3>
                    </div>
                    <span class="text-xs text-slate-600 font-medium">Menampilkan agenda terdekat</span>
                </div>

                <div class="p-4 sm:p-5 divide-y divide-slate-200">
                    @forelse($activeAgendas as $agenda)
                        <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="status text-xs font-bold {{ $agenda->status === 'ongoing' ? 'ongoing' : 'upcoming' }}">
                                        {{ $agenda->status === 'ongoing' ? 'Sedang Berlangsung' : 'Terjadwal' }}
                                    </span>
                                    <span class="text-slate-400">&bull;</span>
                                    <span class="text-xs uppercase font-mono font-bold text-slate-700">{{ $agenda->tipe_rapat }}</span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm hover:text-blue-900 transition">
                                    <a href="{{ route('admin.agendas.show', $agenda) }}">{{ $agenda->judul_rapat }}</a>
                                </h4>
                                <div class="text-xs text-slate-700 flex flex-wrap items-center gap-3 font-medium">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span class="text-slate-800">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span class="text-slate-800">{{ $agenda->lokasi_ruang ?? 'Daring / Virtual' }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="shrink-0 flex items-center gap-2">
                                @if($agenda->status === 'ongoing')
                                    @if($agenda->hasUserAttended($user))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            Sudah Hadir
                                        </span>
                                    @else
                                        <a href="{{ route('attendances.create', $agenda) }}" class="button small flex items-center gap-1.5 text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold shadow-xs">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                            <span>Presensi Sekarang</span>
                                        </a>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-500 font-medium italic">Presensi belum dibuka</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-600 text-xs">
                            <svg class="mx-auto mb-2 text-slate-400" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                            Belum ada agenda rapat aktif saat ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Recent Activity Logs (1 col) -->
        <div class="space-y-4">
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Log Aktivitas Terbaru</h3>
                    </div>
                </div>

                <div class="p-4 divide-y divide-slate-200 text-xs">
                    @forelse($recentLogs as $log)
                        <div class="py-3 first:pt-0 last:pb-0 space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</span>
                                <span class="text-slate-600 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-800 leading-relaxed font-medium">{{ $log->description }}</p>
                            <div class="flex items-center gap-2 text-[10px] text-slate-600 font-mono font-semibold">
                                <span class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-900 border border-slate-200">{{ $log->activity_type }}</span> &bull; <span>{{ $log->ip_address }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500 text-xs">
                            Belum ada riwayat aktivitas tercatat.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
