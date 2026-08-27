@extends('layouts.app')

@section('title', $agenda->judul_rapat)
@section('heading', 'Detail Agenda Rapat')
@section('subtitle', 'Informasi pelaksanaan, dokumen edaran, notulensi, dan daftar hadir')

@section('content')
<div class="space-y-6">
    <!-- Top Hero Banner -->
    <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-800">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $statusStyle = match($agenda->status) {
                            'ongoing' => 'bg-amber-500/20 text-amber-300 border border-amber-500/40 font-bold',
                            'completed' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold',
                            'draft' => 'bg-slate-800 text-slate-300 border border-slate-700',
                            'cancelled' => 'bg-rose-500/20 text-rose-300 border border-rose-500/40 font-bold',
                            default => 'bg-blue-500/20 text-blue-300 border border-blue-500/40 font-bold'
                        };
                        $statusText = match($agenda->status) {
                            'ongoing' => 'Sedang Berlangsung (Presensi Dibuka)',
                            'completed' => 'Selesai (Presensi Ditutup)',
                            'draft' => 'Konsep',
                            'cancelled' => 'Dibatalkan',
                            default => 'Terjadwal'
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusStyle }}">
                        {{ $statusText }}
                    </span>

                    <span class="text-xs uppercase font-mono px-2.5 py-1 bg-slate-800 border border-slate-700 rounded-md text-slate-300">
                        {{ $agenda->tipe_rapat }}
                    </span>

                    <span class="text-xs uppercase font-semibold px-2.5 py-1 bg-slate-800 border border-slate-700 rounded-md text-slate-300">
                        {{ $agenda->jenis_rapat }}
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight leading-snug">
                    {{ $agenda->judul_rapat }}
                </h1>

                <div class="flex flex-wrap items-center gap-4 text-xs text-blue-100/80">
                    <div class="flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y &bull; H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span>Oleh: {{ $agenda->creator->name }} ({{ $agenda->creator->unit?->kode_unit ?? 'Pusat' }})</span>
                    </div>
                </div>
            </div>

            <!-- Action Controls -->
            <div class="flex flex-wrap items-center gap-2 self-start lg:self-auto shrink-0">
                @can('manageStatus', $agenda)
                    @if($agenda->status === 'scheduled')
                        <form 
                            action="{{ route('admin.agendas.update-status', $agenda) }}" 
                            method="POST" 
                            class="inline"
                            data-confirm="Buka sesi presensi rapat '{{ $agenda->judul_rapat }}' sekarang? Pegawai akan dapat langsung mengisi daftar hadir."
                            data-confirm-title="Buka Sesi Presensi"
                            data-confirm-type="confirm"
                            data-confirm-btn="Ya, Mulai Sesi"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="ongoing">
                            <button type="submit" class="button flex items-center gap-2 text-xs bg-amber-500 hover:bg-amber-600 shadow-md">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                <span>Buka Sesi Presensi</span>
                            </button>
                        </form>
                    @elseif($agenda->status === 'ongoing')
                        <form 
                            action="{{ route('admin.agendas.update-status', $agenda) }}" 
                            method="POST" 
                            class="inline"
                            data-confirm="Selesaikan dan tutup sesi presensi rapat '{{ $agenda->judul_rapat }}'? Pegawai tidak dapat mengisi presensi lagi setelah sesi ditutup."
                            data-confirm-title="Selesaikan Sesi Rapat"
                            data-confirm-type="warning"
                            data-confirm-btn="Ya, Selesaikan Rapat"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="button flex items-center gap-2 text-xs bg-emerald-600 hover:bg-emerald-700 shadow-md">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span>Tutup Rapat & Selesaikan</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('manageMinutes', $agenda)
                    <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="button secondary flex items-center gap-2 text-xs bg-white/10 hover:bg-white/20 text-white border-white/20">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span>Notulensi & Foto</span>
                    </a>
                @endcan

                @can('update', $agenda)
                    <a href="{{ route('admin.agendas.edit', $agenda) }}" class="button secondary flex items-center gap-2 text-xs bg-white/10 hover:bg-white/20 text-white border-white/20">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg>
                        <span>Edit</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Agenda Details, Notulensi, Documentations (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Online Meeting Link (if available) -->
            @if($agenda->link_meeting)
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-blue-900">Tautan Pertemuan Daring</div>
                            <div class="text-xs text-blue-700 truncate max-w-sm">{{ $agenda->link_meeting }}</div>
                        </div>
                    </div>
                    <a href="{{ $agenda->link_meeting }}" target="_blank" class="button small text-xs shrink-0">
                        Buka Zoom / GMeet
                    </a>
                </div>
            @endif

            <!-- Notulensi & Kesimpulan Section -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-blue-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <h3 class="font-bold text-slate-800 text-sm">Notulensi & Kesimpulan Rapat</h3>
                    </div>

                    @can('manageMinutes', $agenda)
                        <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="text-xs font-semibold text-blue-600 hover:underline">
                            Edit Notulensi
                        </a>
                    @endcan
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Poin-Poin Pembahasan (Notulensi):</h4>
                        @if($agenda->notulensi)
                            <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-100">
                                {{ $agenda->notulensi }}
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-xl border border-slate-100">
                                Notulensi jalannya rapat belum diinput oleh pengelola rapat.
                            </p>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Kesimpulan & Rencana Tindak Lanjut (RTL):</h4>
                        @if($agenda->kesimpulan)
                            <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                                {{ $agenda->kesimpulan }}
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic bg-slate-50 p-4 rounded-xl border border-slate-100">
                                Kesimpulan rapat belum diinput.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dokumentasi Foto Kegiatan -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-indigo-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        <h3 class="font-bold text-slate-800 text-sm">Dokumentasi Foto Rapat ({{ $agenda->documentations->count() }})</h3>
                    </div>
                </div>

                <div class="p-6">
                    @if($agenda->documentations->count() > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($agenda->documentations as $doc)
                                <div class="group relative bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                                    <img 
                                        src="{{ Storage::disk('public')->url($doc->file_path) }}" 
                                        alt="{{ $doc->caption ?? 'Dokumentasi Rapat' }}" 
                                        class="w-full h-36 object-cover group-hover:scale-105 transition duration-300"
                                    >
                                    @if($doc->caption)
                                        <div class="p-2 text-[11px] text-slate-600 bg-white border-t border-slate-200 truncate" title="{{ $doc->caption }}">
                                            {{ $doc->caption }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic text-center py-6">
                            Belum ada foto dokumentasi yang diunggah untuk agenda ini.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Surat Edaran & Rekap Kehadiran (1 col) -->
        <div class="space-y-6">
            <!-- Surat Edaran Card -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-blue-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <h3 class="font-bold text-slate-800 text-sm">Surat Edaran / Undangan</h3>
                    </div>
                </div>

                <div class="p-5">
                    @if($agenda->surat_edaran_path)
                        <div class="text-center p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center mx-auto">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Berkas Undangan Rapat</div>
                                <div class="text-[11px] text-slate-400">PDF / Dokumen Resmi</div>
                            </div>
                            <a 
                                href="{{ Storage::disk('public')->url($agenda->surat_edaran_path) }}" 
                                target="_blank" 
                                class="button small w-full flex items-center justify-center gap-2 text-xs"
                            >
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Buka / Unduh Surat</span>
                            </a>
                        </div>
                    @else
                        <div class="text-center py-6 text-xs text-slate-400">
                            Tidak ada berkas surat edaran terlampir.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Target Unit Card -->
            <div class="panel">
                <div class="toolbar">
                    <h3 class="font-bold text-slate-800 text-sm">Target Partisipan Unit</h3>
                </div>
                <div class="p-5">
                    @if($agenda->is_all_units)
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-semibold flex items-center gap-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span>Terbuka untuk Seluruh Unit LLDIKTI</span>
                        </div>
                    @else
                        <div class="space-y-2">
                            <div class="text-xs text-slate-500 mb-2">Hanya unit kerja berikut:</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($agenda->units as $u)
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-800 font-semibold text-xs rounded-md border border-blue-200">
                                        {{ $u->kode_unit }} — {{ $u->nama_unit }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Rekapitulasi Presensi Peserta -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-emerald-600" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h3 class="font-bold text-slate-800 text-sm">Peserta Hadir ({{ $agenda->attendances->count() }})</h3>
                    </div>
                </div>

                <div class="p-4 divide-y divide-slate-100 max-h-80 overflow-y-auto">
                    @forelse($agenda->attendances as $attendance)
                        <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-[10px] shrink-0">
                                    {{ substr($attendance->user->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">{{ $attendance->user->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $attendance->user->nip }}</div>
                                </div>
                            </div>
                            <span class="font-mono text-[11px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                {{ $attendance->signed_at->format('H:i') }} WIB
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">
                            Belum ada peserta yang melakukan presensi.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
