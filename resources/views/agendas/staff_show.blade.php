@extends('layouts.app')

@section('title', $agenda->judul_rapat)
@section('heading', 'Detail Agenda Rapat')
@section('subtitle', 'Informasi pelaksanaan rapat kedinasan, dokumen undangan, notulensi, dan dokumentasi')

@section('content')
<div class="space-y-6">
    <!-- Top Hero Banner -->
    <div class="bg-slate-950 text-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-800 space-y-4">
        <!-- Top Row: Meeting Type & Status Badges -->
        <div class="flex flex-wrap items-center gap-2">
            @php
                $statusStyle = match($agenda->status) {
                    'ongoing' => 'bg-amber-400 text-slate-950 font-bold',
                    'completed' => 'bg-emerald-400 text-slate-950 font-bold',
                    default => 'bg-slate-800 text-white border border-slate-700 font-bold'
                };
                $statusText = match($agenda->status) {
                    'ongoing' => 'Sedang Berlangsung (Presensi Dibuka)',
                    'completed' => 'Selesai',
                    default => 'Terjadwal'
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs {{ $statusStyle }}">
                {{ $statusText }}
            </span>

            <span class="text-xs uppercase font-mono font-bold px-2.5 py-1 bg-slate-900 border border-slate-700 rounded-md text-slate-200">
                {{ $agenda->tipe_rapat }}
            </span>

            <span class="text-xs uppercase font-bold px-2.5 py-1 bg-slate-900 border border-slate-700 rounded-md text-slate-200">
                {{ $agenda->jenis_rapat }}
            </span>
        </div>

        <!-- Full Width Meeting Title: Never squeezed by buttons! -->
        <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight leading-snug">
            {{ $agenda->judul_rapat }}
        </h1>

        <!-- Metadata Row: Date, Location, Organizer -->
        <div class="flex flex-wrap items-center gap-y-2 gap-x-5 text-xs text-slate-300 font-medium">
            <div class="flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }} &bull; {{ $agenda->waktu_mulai->format('H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
            </div>

            <div class="flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
            </div>

            <div class="flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Penyelenggara: {{ $agenda->creator->name }} ({{ $agenda->creator->unit?->kode_unit ?? 'Pusat' }})</span>
            </div>
        </div>

        <!-- Dedicated Action Toolbar (Full Width Bottom Bar) -->
        <div class="pt-4 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Back Navigation to Dashboard -->
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>

            <!-- Right: Attendance Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                @if($agenda->status === 'ongoing')
                    @if($myAttendance)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 shadow-sm">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            <span>Sudah Mengisi Presensi</span>
                        </span>
                        <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition">
                            <span>Bukti Presensi</span>
                        </a>
                    @else
                        <a href="{{ route('attendances.create', $agenda) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold shadow-md transition">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            <span>Isi Presensi Sekarang</span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Agenda Details, Notulensi, Documentations (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Online Meeting Link (if available) -->
            @if($agenda->link_meeting)
                <div class="p-4 bg-white border border-slate-300 rounded-xl flex items-center justify-between gap-4 shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-xs text-slate-900">Tautan Pertemuan Daring</div>
                            <div class="text-xs text-slate-700 font-medium truncate max-w-sm">{{ $agenda->link_meeting }}</div>
                        </div>
                    </div>
                    <a href="{{ $agenda->link_meeting }}" target="_blank" class="button small text-xs shrink-0 font-bold">
                        Buka Zoom / GMeet
                    </a>
                </div>
            @endif

            <!-- Notulensi & Kesimpulan Section -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Notulensi &amp; Kesimpulan Rapat</h3>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2">Poin-Poin Pembahasan (Notulensi):</h4>
                        @if($agenda->notulensi)
                            <div class="text-xs text-slate-900 font-medium leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200">
                                {{ $agenda->notulensi }}
                            </div>
                        @else
                            <p class="text-xs text-slate-600 italic bg-slate-50 p-4 rounded-xl border border-slate-200">
                                Notulensi jalannya rapat belum diinput oleh pengelola rapat.
                            </p>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2">Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</h4>
                        @if($agenda->kesimpulan)
                            <div class="text-xs text-slate-900 font-medium leading-relaxed whitespace-pre-line bg-emerald-50/70 p-4 rounded-xl border border-emerald-300">
                                {{ $agenda->kesimpulan }}
                            </div>
                        @else
                            <p class="text-xs text-slate-600 italic bg-slate-50 p-4 rounded-xl border border-slate-200">
                                Kesimpulan rapat belum diinput.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dokumentasi Foto Kegiatan -->
            <div class="panel flex flex-col justify-between">
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2">
                            <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm">Dokumentasi Foto Rapat ({{ $documentations->total() }})</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        @if($documentations->count() > 0)
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                @foreach($documentations as $doc)
                                    <div class="group relative bg-slate-100 rounded-xl overflow-hidden border border-slate-300">
                                        <img 
                                            src="{{ Storage::disk('public')->url($doc->file_path) }}" 
                                            alt="{{ $doc->caption ?? 'Dokumentasi Rapat' }}" 
                                            class="w-full h-36 object-cover group-hover:scale-105 transition duration-300"
                                        >
                                        @if($doc->caption)
                                            <div class="p-2 text-[11px] text-slate-800 font-semibold bg-white border-t border-slate-200 truncate" title="{{ $doc->caption }}">
                                                {{ $doc->caption }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-500 italic text-center py-6">
                                Belum ada foto dokumentasi yang diunggah untuk agenda ini.
                            </p>
                        @endif
                    </div>
                </div>

                @if($documentations->hasPages())
                    <div class="mt-auto">
                        {{ $documentations->links('vendor.pagination.compact') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Surat Edaran & Status Presensi Pribadi (1 col) -->
        <div class="space-y-6">
            <!-- Status Presensi Pribadi Saya (Bukan Daftar Orang Lain) -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Status Presensi Anda</h3>
                    </div>
                </div>
                <div class="p-5 space-y-3">
                    @if($myAttendance)
                        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl space-y-2">
                            <div class="flex items-center gap-2 text-emerald-900 font-bold text-xs">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                <span>Anda Telah Mengisi Presensi</span>
                            </div>
                            <div class="text-[11px] text-emerald-950 font-mono">
                                Waktu Hadir: {{ $myAttendance->signed_at->translatedFormat('d F Y, H:i') }} WIB
                            </div>
                        </div>
                        <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="button small secondary w-full flex items-center justify-center gap-1.5 text-xs font-bold">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span>Lihat Bukti Presensi Digital</span>
                        </a>
                    @elseif($agenda->status === 'ongoing')
                        <div class="p-4 bg-amber-50 border border-amber-300 rounded-xl space-y-2">
                            <div class="text-xs font-bold text-amber-950">
                                Sesi Presensi Sedang Dibuka
                            </div>
                            <p class="text-[11px] text-amber-900">
                                Anda belum mengisi daftar hadir untuk agenda pertemuan ini. Silakan isi presensi dengan foto selfie wajah dan tanda tangan digital.
                            </p>
                        </div>
                        <a href="{{ route('attendances.create', $agenda) }}" class="button small w-full flex items-center justify-center gap-1.5 text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold shadow-xs">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            <span>Isi Presensi Sekarang</span>
                        </a>
                    @else
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-center text-xs text-slate-600 font-medium">
                            @if($agenda->status === 'scheduled')
                                Sesi presensi belum dibuka oleh penyelenggara rapat.
                            @else
                                Sesi presensi untuk rapat ini telah ditutup.
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Surat Edaran Card -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Surat Edaran / Undangan</h3>
                    </div>
                </div>

                <div class="p-5">
                    @if($agenda->surat_edaran_path)
                        <div class="text-center p-5 bg-slate-50 border border-slate-300 rounded-xl space-y-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center mx-auto">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-900">Berkas Undangan Rapat</div>
                                <div class="text-[11px] text-slate-600 font-medium">PDF / Dokumen Resmi</div>
                            </div>
                            <a 
                                href="{{ Storage::disk('public')->url($agenda->surat_edaran_path) }}" 
                                target="_blank" 
                                class="button small w-full flex items-center justify-center gap-2 text-xs font-bold"
                            >
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Buka / Unduh Surat</span>
                            </a>
                        </div>
                    @else
                        <div class="text-center py-6 text-xs text-slate-500 font-medium">
                            Tidak ada berkas surat edaran terlampir.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Target Unit Card -->
            <div class="panel">
                <div class="toolbar">
                    <h3 class="font-bold text-slate-900 text-sm">Target Partisipan Unit</h3>
                </div>
                <div class="p-5">
                    @if($agenda->is_all_units)
                        <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-950 font-bold flex items-center gap-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span>Terbuka untuk Seluruh Unit LLDIKTI</span>
                        </div>
                    @else
                        <div class="space-y-2">
                            <div class="text-xs text-slate-600 font-semibold mb-2">Hanya unit kerja berikut:</div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($agenda->units as $u)
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-900 font-bold text-xs rounded-md border border-slate-300">
                                        {{ $u->kode_unit }} — {{ $u->nama_unit }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
