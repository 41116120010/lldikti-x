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
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
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

    <!-- Active Agendas Section (Card Grid Styled Like Agenda Rapat) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg class="text-slate-900" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <h3 class="font-extrabold text-slate-950 text-base">Agenda Rapat Terkini</h3>
            </div>

            @if($user->isAdministrator() || $user->isAdmin())
                <a href="{{ route('admin.agendas.index') }}" class="button small secondary flex items-center gap-1.5 text-xs font-bold shrink-0">
                    <span>Semua Agenda</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <a href="{{ route('attendances.history') }}" class="button small secondary text-xs font-bold shrink-0">
                    <span>Lihat Riwayat Presensi Saya</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($activeAgendas as $agenda)
                @php
                    $isOngoing = $agenda->status === 'ongoing';
                    $hasAttended = $agenda->hasUserAttended($user);
                    $detailRoute = ($user->isAdministrator() || $user->isAdmin())
                        ? route('admin.agendas.show', $agenda)
                        : route('agendas.show', $agenda);
                @endphp
                <x-agenda-card
                    :agenda="$agenda"
                    :detail-route="$detailRoute"
                    :show-attendance="$user->isAdministrator() || $user->isAdmin()"
                >
                    <x-slot:footer>
                        <a href="{{ $detailRoute }}" class="button small secondary text-xs font-bold">
                            Lihat Detail
                        </a>

                        <div class="flex items-center gap-2">
                            @if($isOngoing)
                                @if($hasAttended)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                                        Sudah Hadir
                                    </span>
                                    @php
                                        $myAtt = $agenda->attendances->firstWhere('user_id', $user->id);
                                    @endphp
                                    @if($myAtt)
                                        <a href="{{ route('attendances.success', [$agenda, $myAtt]) }}" class="button small secondary text-xs font-bold whitespace-nowrap">
                                            Bukti
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('attendances.create', $agenda) }}" class="button small flex items-center gap-1.5 text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold shadow-xs">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        <span>Presensi Sekarang</span>
                                    </a>
                                @endif
                            @else
                                <span class="text-xs text-slate-500 font-medium italic">Presensi belum dibuka</span>
                            @endif
                        </div>
                    </x-slot:footer>
                </x-agenda-card>
            @empty
                <x-ui.empty-state
                    icon="calendar"
                    title="Tidak Ada Agenda Rapat Aktif"
                    description="Saat ini tidak ada sesi rapat yang sedang berlangsung atau agenda terjadwal dalam waktu dekat."
                >
                    @if($user->isAdministrator() || $user->isAdmin())
                        <div class="pt-2 flex items-center justify-center gap-2">
                            <a href="{{ route('admin.agendas.index') }}" class="button small secondary text-xs font-bold">
                                <span>Lihat Semua Arsip Agenda</span>
                            </a>
                            <a href="{{ route('admin.agendas.create') }}" class="button small text-xs font-bold">
                                <span>+ Buat Agenda Baru</span>
                            </a>
                        </div>
                    @endif
                </x-ui.empty-state>
            @endforelse
        </div>

        @if($activeAgendas->hasPages())
            <div class="pt-2">
                {{ $activeAgendas->links('vendor.pagination.compact') }}
            </div>
        @endif
    </div>
</div>
@endsection
