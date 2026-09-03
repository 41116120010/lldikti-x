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
                <span class="text-xs text-slate-700 font-mono font-bold hidden sm:inline">&bull; {{ $ongoingAgendas->total() }} Rapat Aktif</span>
            </div>
            <a href="{{ route('attendances.history') }}" class="button small secondary flex items-center gap-1.5 text-xs shrink-0 font-bold">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Riwayat Presensi Saya</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @forelse($ongoingAgendas as $agenda)
                @php
                    $hasAttended = $agenda->hasUserAttended($user);
                    $detailRoute = ($user->isAdministrator() || $user->isAdmin()) 
                        ? route('admin.agendas.show', $agenda) 
                        : route('agendas.show', $agenda);
                @endphp
                <div class="bg-white rounded-2xl border border-slate-300 shadow-xs hover:border-slate-400 transition flex flex-col justify-between overflow-hidden">
                    <div>
                        <!-- Top Card Header: Badges & Status -->
                        <div class="p-5 pb-3 border-b border-slate-200 flex items-start justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $hasAttended ? 'bg-emerald-100 text-emerald-900 border-emerald-300 font-bold' : 'bg-amber-100 text-amber-900 border-amber-300 font-bold' }}">
                                    {{ $hasAttended ? 'Sudah Hadir' : 'Sedang Berlangsung' }}
                                </span>

                                <span class="text-[11px] font-mono font-bold uppercase px-2 py-0.5 bg-slate-100 text-slate-800 rounded border border-slate-200">
                                    {{ $agenda->tipe_rapat }}
                                </span>
                            </div>

                            @if($user->isAdministrator() || $user->isAdmin())
                                <!-- Attendee Counter Badge (Admin Only) -->
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200" title="Jumlah Peserta Hadir">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    {{ $agenda->attendances->count() }} Hadir
                                </span>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-3">
                            <h3 class="font-bold text-slate-900 text-base leading-snug hover:text-blue-900 transition line-clamp-2">
                                <a href="{{ $detailRoute }}">
                                    {{ $agenda->judul_rapat }}
                                </a>
                            </h3>

                            <div class="space-y-1.5 text-xs text-slate-700 font-medium">
                                <div class="flex items-center gap-2">
                                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span class="text-slate-800">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span class="truncate text-slate-800">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                                    @if($agenda->is_all_units)
                                        <span class="text-emerald-900 font-bold">Seluruh Unit LLDIKTI (Pleno)</span>
                                    @else
                                        <span class="text-slate-900 font-bold">{{ $agenda->units->pluck('kode_unit')->join(', ') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2">
                        <a href="{{ $detailRoute }}" class="button small secondary text-xs font-bold">
                            Lihat Detail
                        </a>

                        <div class="flex items-center gap-2">
                            @if($hasAttended)
                                @php
                                    $myAttendance = $agenda->attendances->firstWhere('user_id', $user->id);
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    Sudah Hadir
                                </span>
                                @if($myAttendance)
                                    <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="button small secondary text-xs font-bold whitespace-nowrap">
                                        Bukti
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('attendances.create', $agenda) }}" class="button small flex items-center gap-1.5 text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold shadow-xs">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <span>Presensi Sekarang</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-slate-300 text-xs text-slate-700 font-medium">
                    <svg class="mx-auto mb-2 text-slate-400" viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Saat ini tidak ada sesi rapat yang sedang berlangsung untuk unit kerja Anda.
                </div>
            @endforelse
        </div>

        @if($ongoingAgendas->hasPages())
            <div class="bg-white rounded-2xl border border-slate-300 overflow-hidden">
                {{ $ongoingAgendas->links('vendor.pagination.compact') }}
            </div>
        @endif
    </div>

    <!-- Section 2: Upcoming Meetings & Personal Attendance History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4">
        <!-- Upcoming Scheduled Meetings -->
        <div class="panel flex flex-col justify-between">
            <div>
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Agenda Rapat Mendatang</h3>
                    </div>
                </div>

                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($scheduledAgendas as $agenda)
                        @php
                            $schedDetailRoute = ($user->isAdministrator() || $user->isAdmin()) 
                                ? route('admin.agendas.show', $agenda) 
                                : route('agendas.show', $agenda);
                        @endphp
                        <div class="bg-white rounded-xl p-4 border border-slate-300 shadow-xs hover:border-slate-400 transition flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-1.5 pb-2 border-b border-slate-100">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-900 border-slate-300">
                                        Terjadwal
                                    </span>
                                    <span class="text-[10px] font-mono font-bold uppercase bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded border border-slate-200">
                                        {{ $agenda->tipe_rapat }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-950 text-xs leading-snug line-clamp-2 pt-2 hover:text-blue-900 transition">
                                    <a href="{{ $schedDetailRoute }}">
                                        {{ $agenda->judul_rapat }}
                                    </a>
                                </h4>
                            </div>

                            <div class="text-[11px] text-slate-700 space-y-1.5 font-medium pt-2 border-t border-slate-100">
                                <div class="flex items-center gap-1.5 text-slate-900 font-semibold">
                                    <svg class="text-slate-500 shrink-0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span>{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-600 truncate">
                                    <svg class="text-slate-500 shrink-0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span class="truncate">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-600 truncate">
                                    <svg class="text-slate-500 shrink-0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                                    <span class="truncate">
                                        @if($agenda->is_all_units)
                                            Pleno Seluruh Unit
                                        @else
                                            {{ $agenda->units->pluck('kode_unit')->join(', ') }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] text-slate-500 font-medium italic">Presensi belum dibuka</span>
                                <a href="{{ $schedDetailRoute }}" class="text-[11px] text-blue-900 hover:text-blue-950 font-bold underline">
                                    Lihat Detail &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-6 text-xs text-slate-600 font-medium">
                            Belum ada agenda rapat terjadwal berikutnya.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($scheduledAgendas->hasPages())
                <div class="mt-auto">
                    {{ $scheduledAgendas->links('vendor.pagination.compact') }}
                </div>
            @endif
        </div>

        <!-- Recent Personal Attendances -->
        <div class="panel flex flex-col justify-between">
            <div>
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

            @if($recentAttendances->hasPages())
                <div class="mt-auto">
                    {{ $recentAttendances->links('vendor.pagination.compact') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
