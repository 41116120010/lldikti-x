@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi Rapat')
@section('heading', 'Rekapitulasi & Laporan Rapat')
@section('subtitle', 'Rekapitulasi kehadiran, notulensi resmi, dan ekspor dokumen rapat kedinasan.')

@section('content')
<div class="space-y-6">
    <!-- Stat Summary Cards (Clean Gov-Tech, Consolidated 3-Pillar) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Card 1: Agenda Rapat -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Agenda Rapat</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200">{{ $completedAgendas }} Selesai</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $totalAgendas }}</div>
            <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1.5">
                <span class="text-amber-800 font-bold">{{ $ongoingAgendas }} Berlangsung</span>
                <span>&bull;</span>
                <span class="text-slate-600">{{ $scheduledAgendas }} Terjadwal</span>
            </div>
        </div>

        <!-- Card 2: Kehadiran Pegawai -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Kehadiran Pegawai</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200 font-mono">{{ $attendanceRate }}% Rasio</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $totalPresensi }} <span class="text-sm font-normal text-slate-600 font-sans">Hadir</span></div>
            <div class="text-[11px] text-slate-600 font-medium">Rata-rata {{ $avgPresensi }} aparatur per rapat dinas</div>
        </div>

        <!-- Card 3: Kelengkapan Notulensi -->
        <div class="panel p-5 space-y-2 border border-slate-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700 font-mono">Kelengkapan Notulensi</span>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200 font-mono">{{ $governanceRate }}% Rampung</span>
            </div>
            <div class="text-2xl font-bold font-mono text-slate-900">{{ $countWithNotulen }} <span class="text-sm font-normal text-slate-600 font-sans">/ {{ max(1, $completedAgendas) }} Berkas</span></div>
            <div class="text-[11px] text-slate-600 font-medium flex items-center gap-1.5">
                @php
                    $pendingNotulen = max(0, $completedAgendas - $countWithNotulen);
                @endphp
                @if($pendingNotulen > 0)
                    <span class="text-amber-800 font-bold">{{ $pendingNotulen }} Belum Diisi</span>
                @else
                    <span class="text-emerald-700 font-bold">Semua Notulen Lengkap</span>
                @endif
                <span>&bull;</span>
                <span class="text-slate-600">{{ $completedAgendas }} Rapat Selesai</span>
            </div>
        </div>
    </div>

    <!-- Visual Monitoring Row: Format Distribution & Unit/Member Participation (Side-by-Side) -->
    @php
        $hasParticipationPanel = !is_null($unitStats) || !is_null($memberStats);
    @endphp
    <div class="grid grid-cols-1 {{ $hasParticipationPanel ? 'lg:grid-cols-12' : '' }} gap-5 items-stretch">
        <!-- Card Kiri: Distribusi Format Pelaksanaan Rapat -->
        <div class="{{ $hasParticipationPanel ? 'lg:col-span-5' : '' }} panel flex flex-col justify-between border border-slate-300">
            <div>
                <div class="toolbar">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-900 shrink-0"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm truncate">Distribusi Format Pelaksanaan Rapat</h3>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-700 shrink-0">{{ $totalAgendas }} Total Agenda</span>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Proportional Multi-Segment Horizontal Bar -->
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex shadow-inner border border-slate-200">
                        @if($totalAgendas > 0)
                            <div style="width: {{ $formatStats['pct_offline'] }}%;" class="bg-slate-900 transition-all duration-300" title="Tatap Muka (Luring): {{ $formatStats['offline'] }} ({{ $formatStats['pct_offline'] }}%)"></div>
                            <div style="width: {{ $formatStats['pct_online'] }}%;" class="bg-slate-500 transition-all duration-300" title="Daring (Virtual): {{ $formatStats['online'] }} ({{ $formatStats['pct_online'] }}%)"></div>
                            <div style="width: {{ $formatStats['pct_hybrid'] }}%;" class="bg-emerald-600 transition-all duration-300" title="Hibrida: {{ $formatStats['hybrid'] }} ({{ $formatStats['pct_hybrid'] }}%)"></div>
                        @else
                            <div class="w-full bg-slate-200 flex items-center justify-center text-[9px] text-slate-500 font-medium">Belum ada agenda rapat</div>
                        @endif
                    </div>

                    <!-- Structured 3-Parameter Breakdown Rows -->
                    <div class="divide-y divide-slate-200/80 border border-slate-200 rounded-xl bg-slate-50/60 overflow-hidden">
                        <!-- Parameter 1: Tatap Muka (Luring) -->
                        <div class="px-3.5 py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-900 shrink-0"></span>
                                <span class="text-xs font-bold text-slate-800 truncate">Tatap Muka (Luring)</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 font-mono">
                                <span class="text-xs font-bold text-slate-900">{{ $formatStats['offline'] }} Agenda</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 min-w-[44px] text-center">{{ $formatStats['pct_offline'] }}%</span>
                            </div>
                        </div>

                        <!-- Parameter 2: Daring (Virtual) -->
                        <div class="px-3.5 py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-500 shrink-0"></span>
                                <span class="text-xs font-bold text-slate-800 truncate">Daring (Virtual)</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 font-mono">
                                <span class="text-xs font-bold text-slate-900">{{ $formatStats['online'] }} Agenda</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 min-w-[44px] text-center">{{ $formatStats['pct_online'] }}%</span>
                            </div>
                        </div>

                        <!-- Parameter 3: Hibrida -->
                        <div class="px-3.5 py-2.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                <span class="text-xs font-bold text-slate-800 truncate">Hibrida</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 font-mono">
                                <span class="text-xs font-bold text-slate-900">{{ $formatStats['hybrid'] }} Agenda</span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 min-w-[44px] text-center">{{ $formatStats['pct_hybrid'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Kanan: Partisipasi Per Unit Kerja / Pegawai di Unit -->
        @if($unitStats)
            <div class="lg:col-span-7 panel flex flex-col justify-between border border-slate-300">
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="text-slate-900 shrink-0" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm truncate">Partisipasi Per Unit Kerja</h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-700 shrink-0">{{ $totalPresensi }} Total Hadir</span>
                    </div>

                    <div class="p-5 space-y-3.5">
                        @forelse($unitStats as $item)
                            @php
                                $maxPresensi = max(1, $totalPresensi);
                                $percent = min(100, round(($item['attendances_count'] / $maxPresensi) * 100));
                            @endphp
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-bold text-slate-900 truncate min-w-0">{{ $item['unit']->kode_unit }} &bull; {{ $item['unit']->nama_unit }}</span>
                                    <span class="font-mono text-slate-900 font-bold shrink-0">{{ $item['attendances_count'] }} Hadir</span>
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
            </div>
        @elseif($memberStats)
            <div class="lg:col-span-7 panel flex flex-col justify-between border border-slate-300">
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="text-slate-900 shrink-0" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm truncate">Partisipasi Pegawai di Unit</h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-900 shrink-0">{{ $user->unit?->kode_unit }}</span>
                    </div>

                    <div class="p-5 space-y-3.5">
                        @forelse($memberStats as $item)
                            @php
                                $maxPresensi = max(1, $totalPresensi);
                                $percent = min(100, round(($item['attendances_count'] / $maxPresensi) * 100));
                            @endphp
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-bold text-slate-900 truncate min-w-0">{{ $item['user']->name }}</span>
                                    <span class="font-mono text-slate-900 font-bold shrink-0">{{ $item['attendances_count'] }} Hadir</span>
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
            </div>
        @endif
    </div>

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

    <!-- Full-Width Agendas Recap Table -->
    <div class="panel w-full border border-slate-300">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <h3 class="font-bold text-slate-900 text-sm">Daftar Rekapitulasi Rapat</h3>
            </div>
            <span class="text-xs font-mono font-bold text-slate-700">{{ $agendas->total() }} Agenda Ditampilkan</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Agenda &amp; Jadwal</th>
                        <th class="text-center">Peserta</th>
                        <th class="text-center">Status Rapat</th>
                        <th class="text-center">Notulensi</th>
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
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 font-mono">
                                    {{ $agenda->attendances_count }} Hadir
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @php
                                    $statusMeta = match ($agenda->status) {
                                        'completed' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                        'ongoing' => ['label' => 'Sedang Berlangsung', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                        'scheduled' => ['label' => 'Terjadwal', 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
                                        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-50 text-rose-800 border-rose-200'],
                                        default => ['label' => ucfirst($agenda->status), 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
                                    };
                                @endphp
                                <span class="inline-flex items-center text-[10px] font-bold px-2.5 py-0.5 rounded-full border {{ $statusMeta['class'] }}">
                                    {{ $statusMeta['label'] }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @if(!empty(trim($agenda->notulensi ?? '')))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200" title="Notulensi resmi telah diarsipkan">
                                        <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        <span>Lengkap</span>
                                    </span>
                                @elseif($agenda->status === 'completed')
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200" title="Rapat telah selesai, menunggu pengisian notulensi">
                                        <span>Belum Diisi</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono text-xs" title="Notulensi diisi setelah rapat selesai">—</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
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
                            <td colspan="5" class="empty-search py-10">
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
</div>
@endsection
