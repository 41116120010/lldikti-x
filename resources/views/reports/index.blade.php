@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi Rapat')
@section('heading', 'Rekapitulasi & Laporan Rapat')
@section('subtitle', 'Analitik kehadiran kedinasan, berita acara resmi, monitoring akuntabilitas, dan ekspor dokumen')

@section('content')
<div class="space-y-6">
    <!-- Header Context Scoping Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-300 shadow-xs">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full text-[11px] font-bold font-mono uppercase tracking-wider {{ $user->isAdministrator() ? 'bg-slate-900 text-white' : 'bg-indigo-900 text-indigo-100' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $user->isAdministrator() ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                <span>{{ $user->isAdministrator() ? 'Cakupan: Seluruh Unit Kerja LLDIKTI (12 Unit)' : 'Cakupan: ' . ($user->unit?->nama_unit ?? 'Unit Kerja') . ' & Penugasan' }}</span>
            </div>
            <p class="text-xs text-slate-600 font-medium">
                {{ $user->isAdministrator() 
                    ? 'Pemantauan komprehensif kehadiran ASN, akuntabilitas berita acara, dan analitik pertemuan lintas subbagian di LLDIKTI Wilayah X.' 
                    : 'Pemantauan kehadiran aparatur di unit kerja Anda serta rekapitulasi rapat di mana aparatur ditunjuk sebagai Pimpinan atau Notulis.' }}
            </p>
        </div>

        @if($user->isAdmin() && $user->unit)
            <div class="shrink-0 flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>{{ $user->unit->kode_unit }}</span>
            </div>
        @endif
    </div>

    <!-- Stat Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total & Status Rapat -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Total Agenda</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200">{{ $completedAgendas }} Selesai</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $totalAgendas }}</div>
            <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1.5">
                <span class="text-emerald-700 font-bold">{{ $ongoingAgendas }} Berlangsung</span>
                <span>&bull;</span>
                <span class="text-slate-600">{{ $scheduledAgendas }} Terjadwal</span>
            </div>
        </div>

        <!-- Card 2: Total Kehadiran Pegawai -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Total Kehadiran</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200 font-mono">{{ $avgPresensi }} / Rapat</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $totalPresensi }}</div>
            <div class="text-[11px] text-slate-600 font-medium">Presensi selfie &amp; TTD terverifikasi</div>
        </div>

        <!-- Card 3: Tingkat Kepatuhan Presensi -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Disiplin Presensi</span>
                @php
                    $rateBadge = 'bg-emerald-50 text-emerald-800 border-emerald-300';
                    $rateLabel = 'Tinggi';
                    if ($attendanceRate < 70) {
                        $rateBadge = 'bg-rose-50 text-rose-800 border-rose-300';
                        $rateLabel = 'Perhatian';
                    } elseif ($attendanceRate < 85) {
                        $rateBadge = 'bg-amber-50 text-amber-800 border-amber-300';
                        $rateLabel = 'Baik';
                    }
                @endphp
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md border {{ $rateBadge }}">{{ $rateLabel }}</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $attendanceRate }}%</div>
            <div class="text-[11px] text-slate-600 font-medium truncate" title="{{ $attendanceRateSubtext }}">{{ $attendanceRateSubtext }}</div>
        </div>

        <!-- Card 4: Akuntabilitas Notulensi & Dokumen -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Tata Kelola Notulen</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200">{{ $countWithNotulen }} / {{ max(1, $completedAgendas) }}</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $governanceRate }}%</div>
            <div class="text-[11px] text-slate-600 font-medium">Berita acara rampung terarsip</div>
        </div>
    </div>

    <!-- Visual Monitoring & Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Widget 1: Distribusi Format Pelaksanaan Rapat -->
        <div class="bg-white p-5 rounded-2xl border border-slate-300 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-900"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">Distribusi Format Rapat</h4>
                </div>
                <span class="text-[11px] font-bold text-slate-600 font-mono">{{ $totalAgendas }} Rapat Total</span>
            </div>

            <!-- Proportional Multi-Segment Horizontal Bar -->
            <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden flex shadow-inner border border-slate-200">
                @if($totalAgendas > 0)
                    <div style="width: {{ $formatStats['pct_offline'] }}%;" class="bg-slate-900 transition-all duration-300" title="Tatap Muka (Luring): {{ $formatStats['offline'] }} ({{ $formatStats['pct_offline'] }}%)"></div>
                    <div style="width: {{ $formatStats['pct_online'] }}%;" class="bg-indigo-600 transition-all duration-300" title="Daring (Virtual): {{ $formatStats['online'] }} ({{ $formatStats['pct_online'] }}%)"></div>
                    <div style="width: {{ $formatStats['pct_hybrid'] }}%;" class="bg-emerald-600 transition-all duration-300" title="Hibrida: {{ $formatStats['hybrid'] }} ({{ $formatStats['pct_hybrid'] }}%)"></div>
                @else
                    <div class="w-full bg-slate-200 flex items-center justify-center text-[9px] text-slate-500 font-medium">Belum ada agenda rapat</div>
                @endif
            </div>

            <!-- Legends Row -->
            <div class="grid grid-cols-3 gap-2 pt-1 text-xs">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-900 shrink-0"></span>
                        <span class="text-[11px]">Luring</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $formatStats['offline'] }} <span class="text-[10px] text-slate-600 font-normal">({{ $formatStats['pct_offline'] }}%)</span>
                    </div>
                </div>

                <div class="space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 shrink-0"></span>
                        <span class="text-[11px]">Daring</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $formatStats['online'] }} <span class="text-[10px] text-slate-600 font-normal">({{ $formatStats['pct_online'] }}%)</span>
                    </div>
                </div>

                <div class="space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <span class="text-[11px]">Hibrida</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $formatStats['hybrid'] }} <span class="text-[10px] text-slate-600 font-normal">({{ $formatStats['pct_hybrid'] }}%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 2: Kesiapan Administrasi & Akuntabilitas Dokumen -->
        <div class="bg-white p-5 rounded-2xl border border-slate-300 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-900"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">Status Tata Kelola Dokumen</h4>
                </div>
                <span class="text-[11px] font-bold text-slate-600 font-mono">{{ $governanceRate }}% Lengkap</span>
            </div>

            @php
                $pendingNotulen = max(0, $completedAgendas - $countWithNotulen);
                $pctWithNotulen = $completedAgendas > 0 ? round(($countWithNotulen / $completedAgendas) * 100) : ($totalAgendas > 0 ? 0 : 100);
                $pctPendingNotulen = $completedAgendas > 0 ? round(($pendingNotulen / $completedAgendas) * 100) : 0;
            @endphp

            <!-- Progress Bar Notulensi -->
            <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden flex shadow-inner border border-slate-200">
                @if($completedAgendas > 0)
                    <div style="width: {{ $pctWithNotulen }}%;" class="bg-emerald-600 transition-all duration-300" title="Notulensi Rampung: {{ $countWithNotulen }} ({{ $pctWithNotulen }}%)"></div>
                    <div style="width: {{ $pctPendingNotulen }}%;" class="bg-amber-500 transition-all duration-300" title="Notulensi Pending: {{ $pendingNotulen }} ({{ $pctPendingNotulen }}%)"></div>
                @else
                    <div class="w-full bg-slate-200 flex items-center justify-center text-[9px] text-slate-500 font-medium">Belum ada rapat dinas selesai</div>
                @endif
            </div>

            <!-- Status Details Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1 text-xs">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <span class="text-[11px]">Notulen Rampung</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $countWithNotulen }} <span class="text-[10px] text-slate-600 font-normal">({{ $pctWithNotulen }}%)</span>
                    </div>
                </div>

                <div class="space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                        <span class="text-[11px]">Notulen Pending</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $pendingNotulen }} <span class="text-[10px] text-slate-600 font-normal">({{ $pctPendingNotulen }}%)</span>
                    </div>
                </div>

                <div class="space-y-0.5 col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                        <span class="text-[11px]">Rapat Terjadwal</span>
                    </div>
                    <div class="font-bold text-slate-900 font-mono text-sm pl-4">
                        {{ $scheduledAgendas }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget 3: Panel Monitoring Agenda Penugasan Resmi (Pimpinan & Notulis) -->
    @if(($user->isAdministrator() || $user->isAdmin()) && $assignedAgendas->isNotEmpty())
        <div class="bg-white p-5 rounded-2xl border border-amber-200 bg-linear-to-r from-amber-50/40 via-white to-white shadow-xs space-y-3.5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-amber-100">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-amber-100 text-amber-900 rounded-lg">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-950 font-mono">Monitoring Agenda Penugasan Resmi (Pimpinan &amp; Notulis)</h4>
                        <p class="text-[11px] text-amber-900/80 font-medium">{{ $user->isAdministrator() ? 'Agenda rapat kedinasan dengan penugasan pimpinan atau notulis resmi.' : 'Agenda rapat di mana Anda ditugaskan secara personal untuk memimpin pertemuan atau mencatat notulensi resmi.' }}</p>
                    </div>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 bg-amber-100 text-amber-900 rounded-full font-mono self-start sm:self-auto">
                    {{ $assignedAgendas->count() }} Penugasan Aktif
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($assignedAgendas as $assigned)
                    @php
                        $isPimpinan = ($assigned->pimpinan_id == $user->id);
                        $isNotulis = ($assigned->notulis_id == $user->id);
                        $hasMinutes = !empty(trim($assigned->notulensi ?? ''));
                    @endphp
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-2 hover:border-slate-300 transition">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md {{ $isNotulis ? 'bg-amber-100 text-amber-900' : 'bg-indigo-100 text-indigo-900' }}">
                                {{ $isNotulis ? 'Notulis Rapat' : 'Pimpinan Rapat' }}
                            </span>
                            <span class="status {{ $assigned->status }} text-[10px] py-0.5">
                                {{ ucfirst($assigned->status) }}
                            </span>
                        </div>

                        <div class="font-bold text-xs text-slate-900 line-clamp-1" title="{{ $assigned->judul_rapat }}">
                            <a href="{{ route('admin.reports.show', $assigned) }}" class="hover:underline">
                                {{ $assigned->judul_rapat }}
                            </a>
                        </div>

                        <div class="text-[11px] text-slate-600 flex items-center justify-between pt-1 border-t border-slate-100">
                            <span>{{ $assigned->waktu_mulai->translatedFormat('d M Y, H:i') }}</span>
                            @if($hasMinutes)
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold text-[10px]">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Notulen Siap</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-amber-700 font-bold text-[10px]">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                                    <span>Belum Diisi</span>
                                </span>
                            @endif
                        </div>

                        <div class="pt-1 flex items-center gap-1.5">
                            <a href="{{ route('admin.reports.show', $assigned) }}" class="button small secondary flex-1 text-[11px] justify-center py-1 font-bold">
                                Rekap
                            </a>
                            @can('manageMinutes', $assigned)
                                <a href="{{ route('admin.agendas.notulen', $assigned) }}" class="button small flex-1 text-[11px] justify-center py-1 font-bold bg-amber-400 text-slate-950 hover:bg-amber-500 border-amber-500">
                                    Notulen
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filter Bar & Export CSV -->
    <div class="bg-white p-5 rounded-2xl border border-slate-300 shadow-xs space-y-4">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 {{ $user->isAdministrator() ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }} gap-3.5 items-end">
            <!-- Start Date -->
            <div class="space-y-1">
                <label for="start_date" class="text-xs font-bold text-slate-900 block uppercase tracking-wider">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="input w-full text-xs font-medium">
            </div>

            <!-- End Date -->
            <div class="space-y-1">
                <label for="end_date" class="text-xs font-bold text-slate-900 block uppercase tracking-wider">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="input w-full text-xs font-medium">
            </div>

            <!-- Status Filter -->
            <div class="space-y-1">
                <label for="status" class="text-xs font-bold text-slate-900 block uppercase tracking-wider">Status Rapat</label>
                <select id="status" name="status" class="input w-full text-xs font-semibold">
                    <option value="all">Semua Status</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Sedang Berlangsung</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                </select>
            </div>

            <!-- Format Filter -->
            <div class="space-y-1">
                <label for="tipe" class="text-xs font-bold text-slate-900 block uppercase tracking-wider">Format Rapat</label>
                <select id="tipe" name="tipe" class="input w-full text-xs font-semibold">
                    <option value="">Semua Format</option>
                    <option value="offline" {{ request('tipe') === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring)</option>
                    <option value="online" {{ request('tipe') === 'online' ? 'selected' : '' }}>Daring (Virtual)</option>
                    <option value="hybrid" {{ request('tipe') === 'hybrid' ? 'selected' : '' }}>Hibrida</option>
                </select>
            </div>

            <!-- Unit Filter (For Administrator) -->
            @if($user->isAdministrator())
                <div class="space-y-1">
                    <label for="unit_id" class="text-xs font-bold text-slate-900 block uppercase tracking-wider">Unit Kerja</label>
                    <select id="unit_id" name="unit_id" class="input w-full text-xs font-semibold">
                        <option value="">Semua Unit</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->kode_unit }} &bull; {{ $u->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="button small flex-1 text-xs justify-center font-bold">Terapkan</button>
                @if(request()->hasAny(['start_date', 'end_date', 'status', 'tipe', 'unit_id']))
                    <a href="{{ route('admin.reports.index') }}" class="button small secondary text-xs justify-center font-bold">Reset</a>
                @endif
            </div>
        </form>

        <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span class="text-xs text-slate-700 font-bold">Ekspor Ringkasan Laporan Keseluruhan:</span>
            <a 
                href="{{ route('admin.reports.summary.csv', request()->query()) }}" 
                class="button small secondary flex items-center justify-center gap-1.5 text-xs text-slate-900 font-bold border-slate-300 hover:bg-slate-50 self-start sm:self-auto"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Unduh Rekap CSV / Excel</span>
            </a>
        </div>
    </div>

    <!-- 2-Column: Agendas Table & Unit Participation -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Agendas Table (2 cols) -->
        <div class="lg:col-span-2 panel">
            <div class="toolbar">
                <div class="flex items-center gap-2">
                    <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <h3 class="font-bold text-slate-900 text-sm">Daftar Rekapitulasi Rapat</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Agenda &amp; Jadwal</th>
                            <th class="text-center">Peserta</th>
                            <th class="text-center">Status &amp; Notulen</th>
                            <th class="text-right">Ekspor Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agendas as $agenda)
                            <tr class="hover:bg-slate-50 transition">
                                <td>
                                    <div class="font-bold text-slate-900 text-xs">
                                        <a href="{{ route('admin.reports.show', $agenda) }}" class="hover:underline">
                                            {{ $agenda->judul_rapat }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-700 flex flex-wrap items-center gap-2 mt-0.5 font-medium">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                            <span>{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} WIB</span>
                                        </span>
                                        <span>&bull;</span>
                                        <span class="uppercase font-mono font-bold">{{ $agenda->tipe_rapat }}</span>
                                        <span>&bull;</span>
                                        <span class="text-slate-600">{{ $agenda->creator?->unit?->kode_unit ?? 'Pusat' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200">
                                        {{ $agenda->attendances_count }} Hadir
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="status {{ $agenda->status }} text-[10px]">
                                            {{ ucfirst($agenda->status) }}
                                        </span>
                                        @if(!empty(trim($agenda->notulensi ?? '')))
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200" title="Notulensi resmi telah diarsipkan">
                                                <svg viewBox="0 0 24 24" width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                <span>Notulen Siap</span>
                                            </span>
                                        @elseif($agenda->status === 'completed')
                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200" title="Menunggu pengisian notulensi rapat">
                                                <span>Pending</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- PDF Export -->
                                        <a 
                                            href="{{ route('admin.reports.export.pdf', $agenda) }}" 
                                            class="button small secondary text-xs font-bold text-slate-900 border-slate-300 hover:bg-slate-50 p-1.5" 
                                            title="Unduh Berita Acara (PDF)"
                                        >
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                            <span>PDF</span>
                                        </a>

                                        <!-- Word Export -->
                                        <a 
                                            href="{{ route('admin.reports.export.word', $agenda) }}" 
                                            class="button small secondary text-xs font-bold text-slate-900 border-slate-300 hover:bg-slate-50 p-1.5" 
                                            title="Unduh Dokumen Word (.doc)"
                                        >
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                                            <span>Word</span>
                                        </a>

                                        <!-- Detail Link -->
                                        <a 
                                            href="{{ route('admin.reports.show', $agenda) }}" 
                                            class="button small secondary text-xs font-bold p-1.5" 
                                            title="Lihat Rekap Lengkap"
                                        >
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-search py-10">
                                    <div class="space-y-2 text-center">
                                        <p class="text-slate-700 font-bold text-xs">Tidak ada data rapat yang sesuai dengan kriteria filter.</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'status', 'tipe', 'unit_id']))
                                            <a href="{{ route('admin.reports.index') }}" class="button small secondary inline-flex text-xs font-bold">
                                                Reset Kriteria Filter
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($agendas->hasPages())
                {{ $agendas->links() }}
            @endif
        </div>

        <!-- Unit / Member Participation Breakdown (1 col) -->
        <div class="panel flex flex-col justify-between">
            @if($unitStats)
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2">
                            <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm">Partisipasi Per Unit Kerja</h3>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        @forelse($unitStats as $item)
                            @php
                                $maxPresensi = max(1, $totalPresensi);
                                $percent = min(100, round(($item['attendances_count'] / $maxPresensi) * 100));
                            @endphp
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900">{{ $item['unit']->kode_unit }} &bull; {{ $item['unit']->nama_unit }}</span>
                                    <span class="font-mono text-slate-900 font-bold">{{ $item['attendances_count'] }} Hadir</span>
                                </div>
                                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-slate-900 rounded-full" style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-600 font-medium">
                                Belum ada data partisipasi unit kerja.
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($unitStats->hasPages())
                    <div class="mt-auto">
                        {{ $unitStats->links('vendor.pagination.compact') }}
                    </div>
                @endif
            @elseif($memberStats)
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2">
                            <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm">Partisipasi Pegawai di Unit</h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-900">{{ $user->unit?->kode_unit }}</span>
                    </div>

                    <div class="p-5 space-y-4">
                        @forelse($memberStats as $item)
                            @php
                                $maxPresensi = max(1, $totalPresensi);
                                $percent = min(100, round(($item['attendances_count'] / $maxPresensi) * 100));
                            @endphp
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900">{{ $item['user']->name }}</span>
                                    <span class="font-mono text-slate-900 font-bold">{{ $item['attendances_count'] }} Hadir</span>
                                </div>
                                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-slate-900 rounded-full" style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-600 font-medium">
                                Belum ada data pegawai di unit ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($memberStats->hasPages())
                    <div class="mt-auto">
                        {{ $memberStats->links('vendor.pagination.compact') }}
                    </div>
                @endif
            @else
                <div>
                    <div class="toolbar">
                        <h3 class="font-bold text-slate-900 text-sm">Ringkasan Partisipasi</h3>
                    </div>
                    <div class="p-5 text-center text-xs text-slate-600 font-medium">
                        Rekapitulasi kehadiran kedinasan Anda tercatat pada tabel sebelah kiri.
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
