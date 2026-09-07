@extends('layouts.app')

@section('title', 'Rekap: ' . $agenda->judul_rapat)
@section('heading', 'Rekapitulasi Kehadiran & Dokumen Rapat')
@section('subtitle', 'Detail rekapitulasi kehadiran resmi, notulensi rapat, dokumen edaran, dan dokumentasi')

@section('content')
<div class="space-y-6">
    <!-- Top Hero Banner (Deep Navy Gov-Tech Pattern) -->
    <div class="bg-slate-950 text-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-800 space-y-4">
        <!-- Top Row: Meeting Type & Status Badges -->
        <div class="flex flex-wrap items-center gap-2">
            @php
                $statusStyle = match($agenda->status) {
                    'ongoing' => 'bg-amber-400 text-slate-950 font-bold',
                    'completed' => 'bg-emerald-400 text-slate-950 font-bold',
                    'draft' => 'bg-slate-800 text-slate-200 border border-slate-700',
                    'cancelled' => 'bg-rose-400 text-slate-950 font-bold',
                    default => 'bg-slate-800 text-white border border-slate-700 font-bold'
                };
                $statusText = match($agenda->status) {
                    'ongoing' => 'Sedang Berlangsung (Presensi Dibuka)',
                    'completed' => 'Selesai (Presensi Ditutup)',
                    'draft' => 'Konsep',
                    'cancelled' => 'Dibatalkan',
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

        <!-- Full Width Meeting Title: Never squeezed by action buttons -->
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
                <span>Penyelenggara: {{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->kode_unit ?? 'Pusat' }})</span>
            </div>
        </div>

        <!-- Dedicated Action Toolbar (Full Width Bottom Bar) -->
        <div class="pt-4 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Back Navigation to Reports Index -->
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                <span>Daftar Rekapitulasi</span>
            </a>

            <!-- Right: Action Controls (Exports & Agenda Management Shortcut) -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- PDF Export Button -->
                <a 
                    href="{{ route('admin.reports.export.pdf', $agenda) }}" 
                    target="_blank" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition shadow-xs"
                    title="Cetak Berita Acara & Rekap Kehadiran (PDF)"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    <span>Cetak Berita Acara (PDF)</span>
                </a>

                <!-- Word Export Button -->
                <a 
                    href="{{ route('admin.reports.export.word', $agenda) }}" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition shadow-xs"
                    title="Unduh Berita Acara Format Microsoft Word (.doc)"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="8 13 12 17 16 13"/><line x1="12" y1="9" x2="12" y2="17"/></svg>
                    <span>Unduh Format Word (.doc)</span>
                </a>

                <!-- Switch to Agenda Management Page -->
                <a 
                    href="{{ route('admin.agendas.show', $agenda) }}" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-slate-950 bg-white hover:bg-slate-100 transition shadow-sm"
                    title="Buka halaman kelola agenda rapat"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <span>Kelola Agenda</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Metadata Info Bar -->
    <div class="bg-white border border-slate-300 rounded-2xl shadow-xs">
        <div class="flex flex-wrap items-center divide-x divide-slate-200">
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">MPI No. / Kode</div>
                <div class="font-bold text-slate-900">{{ $agenda->slug }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Penyelenggara</div>
                <div class="font-bold text-slate-900">{{ $agenda->creator?->name ?? 'Penyelenggara' }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Unit Kerja</div>
                <div class="font-bold text-slate-900">{{ $agenda->creator?->unit?->kode_unit ?? 'Pusat' }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Jenis Pertemuan</div>
                <div class="font-bold text-slate-900">{{ ucfirst($agenda->jenis_rapat) }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Total Kehadiran</div>
                <div class="font-bold text-slate-900 font-mono">{{ $attendances->total() }} Pegawai Hadir</div>
            </div>
        </div>
    </div>

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

    <!-- Main 4-Column Grid: Left (1 col: Surat & Scope) + Right (3 cols: Notulensi Resmi) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left: Surat Edaran, Target Unit, & Status Partisipasi (1 col) -->
        <div class="space-y-6">
            <!-- Surat Edaran Card -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Surat Edaran</h3>
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

            <!-- Target Partisipan Unit Card -->
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

            <!-- Ringkasan Validasi Kehadiran Card -->
            <div class="panel p-5 space-y-3">
                <div class="toolbar p-0 pb-3 border-b border-slate-200">
                    <h3 class="font-bold text-slate-900 text-sm">Status Rekapitulasi</h3>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 font-medium">Status Sesi:</span>
                        <span class="font-bold text-slate-950">{{ ucfirst($agenda->status) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 font-medium">Presensi Tervalidasi:</span>
                        <span class="font-bold text-emerald-700 font-mono">{{ $attendances->total() }} Data</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 font-medium">Metode Verifikasi:</span>
                        <span class="font-bold text-slate-900">Selfie + TTD Digital</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Notulensi Rapat & Berita Acara Resmi (3 cols) -->
        <div class="lg:col-span-3 space-y-6">
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Notulensi Rapat</h3>
                    </div>

                    @can('manageMinutes', $agenda)
                        <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="text-xs font-bold text-slate-900 hover:underline">
                            Edit Notulensi
                        </a>
                    @endcan
                </div>

                <div class="p-6 space-y-5">
                    <!-- Pembahasan -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">P</span>
                            Pembahasan Jalannya Rapat
                        </h4>
                        @if($agenda->notulensi)
                            <div class="text-xs text-slate-900 font-medium leading-relaxed pl-7 prose-gov">
                                {!! $agenda->formatted_notulensi !!}
                            </div>
                        @else
                            <p class="text-xs text-slate-600 italic pl-7">
                                Notulensi jalannya rapat belum diinput oleh pengelola rapat.
                            </p>
                        @endif
                    </div>

                    @if($agenda->notulensi)
                        <div class="border-t border-slate-200"></div>
                    @endif

                    <!-- Kesimpulan & Tindak Lanjut -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">K</span>
                            Kesimpulan, Keputusan & Rencana Tindak Lanjut (RTL)
                        </h4>
                        @if($agenda->kesimpulan)
                            <div class="text-xs text-slate-900 font-medium leading-relaxed pl-7 prose-gov">
                                {!! $agenda->formatted_kesimpulan !!}
                            </div>
                        @else
                            <p class="text-xs text-slate-600 italic pl-7">
                                Kesimpulan rapat belum diinput.
                            </p>
                        @endif
                    </div>

                    <div class="border-t border-slate-200"></div>

                    <!-- Penutup -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">✓</span>
                            Penutup
                        </h4>
                        <p class="text-xs text-slate-700 font-medium leading-relaxed pl-7">
                            Rapat ditutup pada pukul {{ $agenda->waktu_selesai->format('H:i') }} WIB. Rekapitulasi berita acara ini disusun dan diverifikasi sebagai bukti sah pelaksanaan kegiatan kedinasan.
                        </p>
                    </div>
                </div>

                <!-- Signature Verification Section -->
                <div class="border-t border-slate-200 px-6 py-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider font-mono">Pemimpin Rapat</div>
                            <div class="h-14 flex items-center justify-center">
                                <svg viewBox="0 0 100 40" width="80" height="32" class="text-slate-300"><path d="M10 30 Q 25 5 40 25 T 70 20 T 90 25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-900 border-t border-slate-300 pt-2">{{ $agenda->creator?->name ?? 'Pimpinan Rapat' }}</div>
                        </div>

                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider font-mono">Notulis</div>
                            <div class="h-14 flex items-center justify-center">
                                <svg viewBox="0 0 100 40" width="80" height="32" class="text-slate-300"><path d="M10 30 Q 25 5 40 25 T 70 20 T 90 25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-900 border-t border-slate-300 pt-2">{{ $agenda->creator?->name ?? 'Notulis Rapat' }}</div>
                        </div>

                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider font-mono">Direktur / Pimpinan Unit</div>
                            <div class="h-14 flex items-center justify-center">
                                <svg viewBox="0 0 100 40" width="80" height="32" class="text-slate-300"><path d="M10 30 Q 25 5 40 25 T 70 20 T 90 25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-900 border-t border-slate-300 pt-2">Direktur/PJ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Attendees with Verified Media (Full Width Section) -->
    <div class="panel">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-950" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <h3 class="font-bold text-slate-950 text-sm">Daftar Kehadiran Pegawai &amp; Verifikasi Media Digital ({{ $attendances->total() }})</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Pegawai</th>
                        <th>Unit Kerja</th>
                        <th>Waktu Presensi</th>
                        <th class="text-center">Selfie Wajah</th>
                        <th class="text-center">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $index => $att)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold">{{ $attendances->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-950 text-xs">{{ $att->user->name }}</div>
                                <div class="text-[10px] text-slate-600 font-mono font-medium">NIP: {{ $att->user->nip }}</div>
                            </td>
                            <td class="text-xs text-slate-800 font-medium">
                                {{ $att->user->unit?->kode_unit ?? 'Pusat' }} &bull; {{ $att->user->unit?->nama_unit ?? 'Tingkat Lembaga' }}
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-950 font-bold">
                                    {{ $att->signed_at->format('H:i:s') }} WIB
                                </div>
                                <div class="text-[10px] text-slate-600 font-mono font-medium">IP: {{ $att->ip_address ?? '127.0.0.1' }}</div>
                            </td>
                            <td class="text-center">
                                <button 
                                    type="button" 
                                    class="w-10 h-10 rounded-lg overflow-hidden border border-slate-300 mx-auto shadow-xs block cursor-pointer hover:opacity-80 transition"
                                    onclick="previewAttendanceMedia('{{ Storage::disk('public')->url($att->selfie_path) }}', 'Foto Selfie: {{ addslashes($att->user->name) }}')"
                                    title="Klik untuk memperbesar Foto Selfie"
                                >
                                    <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" class="w-full h-full object-cover">
                                </button>
                            </td>
                            <td class="text-center">
                                <button 
                                    type="button" 
                                    class="w-16 h-10 rounded-lg overflow-hidden border border-slate-300 bg-white mx-auto shadow-xs flex items-center justify-center p-1 cursor-pointer hover:opacity-80 transition"
                                    onclick="previewAttendanceMedia('{{ Storage::disk('public')->url($att->signature_path) }}', 'Tanda Tangan: {{ addslashes($att->user->name) }}')"
                                    title="Klik untuk memperbesar Tanda Tangan"
                                >
                                    <img src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" class="max-w-full max-h-full object-contain">
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-search py-10">
                                <p class="text-slate-700 font-bold text-xs">Belum ada data presensi yang masuk pada agenda rapat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            {{ $attendances->links() }}
        @endif
    </div>

    <!-- Dokumentasi Foto & Lampiran (Full Width Bottom Section, matching agendas/show) -->
    <div class="panel">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                <h3 class="font-bold text-slate-900 text-sm">Dokumentasi Foto &amp; Lampiran Rapat</h3>
            </div>

            <div class="flex items-center gap-2">
                @if($documentations->hasPages())
                    <div class="flex items-center gap-1 text-xs">
                        {{ $documentations->links('vendor.pagination.compact') }}
                    </div>
                @endif
                <span class="text-xs font-bold text-slate-500 font-mono">{{ $documentations->total() }} File</span>
            </div>
        </div>

        <div class="p-6">
            @if($documentations->count() > 0)
                <div class="flex gap-4 overflow-x-auto pb-2">
                    @foreach($documentations as $doc)
                        <div class="shrink-0 w-40 group">
                            <div class="relative bg-slate-100 rounded-xl overflow-hidden border border-slate-300">
                                @php
                                    $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                    $isPdf = $ext === 'pdf';
                                    $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'webm']);
                                @endphp

                                @if($isImage)
                                    <img 
                                        src="{{ Storage::disk('public')->url($doc->file_path) }}" 
                                        alt="{{ $doc->caption ?? 'Dokumentasi Rapat' }}" 
                                        class="w-full h-28 object-cover group-hover:scale-105 transition duration-300"
                                    >
                                @elseif($isPdf)
                                    <div class="w-full h-28 flex items-center justify-center bg-slate-50">
                                        <div class="text-center">
                                            <span class="inline-block px-2 py-0.5 bg-rose-500 text-white text-[10px] font-bold rounded mb-1">PDF</span>
                                            <div class="text-[10px] text-slate-500 font-medium px-2 truncate">{{ basename($doc->file_path) }}</div>
                                        </div>
                                    </div>
                                @elseif($isVideo)
                                    <div class="w-full h-28 flex items-center justify-center bg-slate-900">
                                        <div class="text-center">
                                            <span class="inline-block px-2 py-0.5 bg-rose-500 text-white text-[10px] font-bold rounded mb-1">VID</span>
                                            <div class="text-[10px] text-slate-400 font-medium px-2 truncate">{{ basename($doc->file_path) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="w-full h-28 flex items-center justify-center bg-slate-50">
                                        <div class="text-center">
                                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 mx-auto"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <div class="text-[10px] text-slate-500 font-medium px-2 mt-1 truncate">{{ basename($doc->file_path) }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2 px-0.5">
                                <div class="text-[11px] text-slate-800 font-semibold truncate" title="{{ $doc->caption ?? basename($doc->file_path) }}">
                                    {{ $doc->caption ?? basename($doc->file_path) }}
                                </div>
                                @if($doc->created_at)
                                    <div class="text-[10px] text-slate-400 font-medium">{{ $doc->created_at->diffForHumans() }}</div>
                                @endif
                            </div>

                            <div class="mt-1.5 flex items-center gap-1">
                                <a 
                                    href="{{ Storage::disk('public')->url($doc->file_path) }}" 
                                    target="_blank" 
                                    class="flex-1 text-center px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded border border-slate-200 transition"
                                >
                                    Preview
                                </a>
                                <a 
                                    href="{{ Storage::disk('public')->url($doc->file_path) }}" 
                                    download 
                                    class="px-1.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-200 transition"
                                    title="Unduh Berkas"
                                >
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                            </div>
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
</div>

<script>
function previewAttendanceMedia(mediaUrl, title) {
    if (typeof window.showModal === 'function') {
        window.showModal({
            title: title,
            message: `
                <div class="text-center p-2">
                    <div class="max-w-xs sm:max-w-sm mx-auto rounded-xl overflow-hidden border border-slate-300 shadow-md bg-white">
                        <img src="${mediaUrl}" alt="${title}" class="w-full h-auto object-contain max-h-96">
                    </div>
                </div>
            `,
            type: 'info',
            confirmText: 'Tutup'
        });
    } else {
        window.open(mediaUrl, '_blank');
    }
}
</script>
@endsection
