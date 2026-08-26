@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi Rapat')
@section('heading', 'Rekapitulasi & Laporan Rapat')
@section('subtitle', 'Analitik kehadiran, berita acara kedinasan, dan ekspor dokumen resmi')

@section('content')
<div class="space-y-6">
    <!-- Stat Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="panel p-5 space-y-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono">Total Agenda</div>
            <div class="text-2xl font-bold text-slate-900">{{ $totalAgendas }}</div>
            <div class="text-[11px] text-slate-500">Pertemuan kedinasan</div>
        </div>

        <div class="panel p-5 space-y-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 font-mono">Rapat Selesai</div>
            <div class="text-2xl font-bold text-emerald-700">{{ $completedAgendas }}</div>
            <div class="text-[11px] text-emerald-600/80">Dokumen telah dirampungkan</div>
        </div>

        <div class="panel p-5 space-y-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-blue-600 font-mono">Total Kehadiran Pegawai</div>
            <div class="text-2xl font-bold text-blue-700">{{ $totalPresensi }}</div>
            <div class="text-[11px] text-blue-600/80">Presensi selfie & TTD sah</div>
        </div>

        <div class="panel p-5 space-y-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 font-mono">Rata-Rata Kehadiran</div>
            <div class="text-2xl font-bold text-indigo-700">{{ $avgPresensi }}</div>
            <div class="text-[11px] text-indigo-600/80">Pegawai per rapat</div>
        </div>
    </div>

    <!-- Filter Bar & Export CSV -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Start Date -->
            <div class="field">
                <label for="start_date">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="input w-full text-xs">
            </div>

            <!-- End Date -->
            <div class="field">
                <label for="end_date">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="input w-full text-xs">
            </div>

            <!-- Status Filter -->
            <div class="field">
                <label for="status">Status Rapat</label>
                <select id="status" name="status" class="input w-full text-xs">
                    <option value="all">Semua Status</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Sedang Berlangsung</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                </select>
            </div>

            <!-- Format Filter -->
            <div class="field">
                <label for="tipe">Format Rapat</label>
                <select id="tipe" name="tipe" class="input w-full text-xs">
                    <option value="">Semua Format</option>
                    <option value="offline" {{ request('tipe') === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="online" {{ request('tipe') === 'online' ? 'selected' : '' }}>Online</option>
                    <option value="hybrid" {{ request('tipe') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="button small w-full text-xs">Filter</button>
                @if(request()->hasAny(['start_date', 'end_date', 'status', 'tipe']))
                    <a href="{{ route('admin.reports.index') }}" class="button small secondary text-xs">Reset</a>
                @endif
            </div>
        </form>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">Ekspor Ringkasan Laporan Keseluruhan:</span>
            <a 
                href="{{ route('admin.reports.summary.csv', request()->query()) }}" 
                class="button small secondary flex items-center gap-1.5 text-xs text-emerald-800 border-emerald-300 hover:bg-emerald-50"
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
                    <svg class="text-blue-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <h3 class="font-bold text-slate-800 text-sm">Daftar Rekapitulasi Rapat</h3>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Agenda & Jadwal</th>
                            <th class="text-center">Peserta</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Ekspor Dokumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agendas as $agenda)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td>
                                    <div class="font-bold text-slate-900 text-xs">
                                        <a href="{{ route('admin.reports.show', $agenda) }}" class="hover:text-blue-600">
                                            {{ $agenda->judul_rapat }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span>📅 {{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} WIB</span>
                                        <span>&bull;</span>
                                        <span class="uppercase font-mono font-semibold">{{ $agenda->tipe_rapat }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-md">
                                        {{ $agenda->attendances->count() }} Hadir
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="status {{ $agenda->status }} text-[10px]">
                                        {{ ucfirst($agenda->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- PDF Export -->
                                        <a 
                                            href="{{ route('admin.reports.export.pdf', $agenda) }}" 
                                            target="_blank" 
                                            class="button small secondary text-xs text-rose-700 border-rose-200 hover:bg-rose-50 p-1.5" 
                                            title="Cetak Berita Acara (PDF)"
                                        >
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                            <span>PDF</span>
                                        </a>

                                        <!-- Word Export -->
                                        <a 
                                            href="{{ route('admin.reports.export.word', $agenda) }}" 
                                            class="button small secondary text-xs text-blue-700 border-blue-200 hover:bg-blue-50 p-1.5" 
                                            title="Unduh Dokumen Word (.doc)"
                                        >
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <span>Word</span>
                                        </a>

                                        <!-- Detail Link -->
                                        <a 
                                            href="{{ route('admin.reports.show', $agenda) }}" 
                                            class="button small secondary text-xs p-1.5" 
                                            title="Lihat Rekap Lengkap"
                                        >
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-search">
                                    Tidak ada data rapat yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($agendas->hasPages())
                <div class="table-foot">
                    <div>Menampilkan {{ $agendas->firstItem() }} - {{ $agendas->lastItem() }} dari {{ $agendas->total() }} rapat</div>
                    {{ $agendas->links() }}
                </div>
            @endif
        </div>

        <!-- Unit Participation Breakdown (1 col) -->
        <div class="panel">
            <div class="toolbar">
                <div class="flex items-center gap-2">
                    <svg class="text-emerald-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    <h3 class="font-bold text-slate-800 text-sm">Partisipasi Per Unit Kerja</h3>
                </div>
            </div>

            <div class="p-5 space-y-4">
                @foreach($unitStats as $item)
                    @php
                        $maxPresensi = max(1, $totalPresensi);
                        $percent = min(100, round(($item['attendances_count'] / $maxPresensi) * 100));
                    @endphp
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800">{{ $item['unit']->kode_unit }} &bull; {{ $item['unit']->nama_unit }}</span>
                            <span class="font-mono text-slate-500 font-bold">{{ $item['attendances_count'] }} Hadir</span>
                        </div>
                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ $percent }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
