@extends('layouts.app')

@section('title', $agenda->judul_rapat)
@section('heading', 'Detail Agenda Rapat')
@section('subtitle', 'Informasi pelaksanaan, dokumen edaran, notulensi, dan daftar hadir')

@section('content')
<div class="space-y-6">
    <!-- Top Hero Banner -->
    <div class="bg-slate-950 text-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-800 space-y-4">
        <!-- Top Row: Status Badges & Type -->
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

        <!-- Full Width Meeting Title: Never squeezed by action buttons! -->
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
                <span>Oleh: {{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->kode_unit ?? 'Pusat' }})</span>
            </div>
        </div>

        <!-- Dedicated Action Toolbar (Full Width Bottom Bar) -->
        <div class="pt-4 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3">
            <!-- Left: Back Navigation -->
            <a href="{{ route('admin.agendas.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                <span>Daftar Agenda</span>
            </a>

            <!-- Right: Action Controls -->
            <div class="flex flex-wrap items-center gap-2.5">
                @can('update', $agenda)
                    <a href="{{ route('admin.agendas.edit', $agenda) }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg>
                        <span>Edit Agenda</span>
                    </a>
                @endcan

                <!-- Official Meeting Exports (PDF & Word) -->
                <a href="{{ route('admin.reports.export.pdf', $agenda) }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition" title="Cetak Berita Acara &amp; Rekap Kehadiran (PDF)">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    <span>Ekspor PDF</span>
                </a>

                <a href="{{ route('admin.reports.export.word', $agenda) }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition" title="Unduh Berita Acara Format Microsoft Word (.doc)">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="8 13 12 17 16 13"/><line x1="12" y1="9" x2="12" y2="17"/></svg>
                    <span>Ekspor Word</span>
                </a>

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
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold shadow-md cursor-pointer transition">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
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
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md cursor-pointer transition">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span>Tutup Rapat &amp; Selesaikan</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('manageMinutes', $agenda)
                    <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="button secondary flex items-center gap-2 text-xs bg-white/10 hover:bg-white/20 text-white border-white/30 font-bold">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <span>Notulensi & Foto</span>
                    </a>
                @endcan

                {{-- Personal Attendance Action for Current User --}}
                @if(isset($myAttendance) && $myAttendance)
                    <a href="{{ route('attendances.success', [$agenda, $myAttendance]) }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-emerald-950 bg-emerald-400 hover:bg-emerald-300 border border-emerald-300 shadow-sm transition" title="Lihat Bukti Kehadiran Anda">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        <span>Anda Sudah Hadir</span>
                    </a>
                @elseif($agenda->status === 'ongoing')
                    <a href="{{ route('attendances.create', $agenda) }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-500 shadow-sm transition" title="Isi Presensi Kehadiran Anda pada Rapat Ini">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>Isi Presensi Saya</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Metadata Info Bar -->
    <div class="bg-white border border-slate-300 rounded-2xl shadow-xs">
        <div class="flex flex-wrap items-center divide-x divide-slate-200">
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">MPI No.</div>
                <div class="font-bold text-slate-900">{{ $agenda->slug }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Notulis</div>
                <div class="font-bold text-slate-900">{{ $agenda->creator?->name ?? 'Penyelenggara' }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Moderator</div>
                <div class="font-bold text-slate-900">{{ $agenda->creator?->unit?->kode_unit ?? 'Pusat' }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Pimpinan</div>
                <div class="font-bold text-slate-900">{{ $agenda->jenis_rapat }}</div>
            </div>
            <div class="px-5 py-3 text-xs">
                <div class="text-[10px] uppercase font-bold text-slate-500 font-mono">Pimp. vi.</div>
                <div class="font-bold text-slate-900">{{ $attendances->total() }}</div>
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

    <!-- Main 2-Column Grid: Kehadiran (left) + Notulensi (right) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Left: Rekapitulasi Kehadiran (1 col) -->
        <div class="space-y-6">
            <!-- Rekapitulasi Kehadiran Panel -->
            <div class="panel flex flex-col justify-between">
                <div>
                    <div class="toolbar">
                        <div class="flex items-center gap-2">
                            <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <h3 class="font-bold text-slate-900 text-sm">Peserta Hadir</h3>
                        </div>
                        <span class="text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            {{ $attendances->total() }} / {{ $attendances->total() }}
                        </span>
                    </div>

                    <!-- Attendance Summary Stats -->
                    <div class="px-4 pt-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold bg-slate-900 text-white">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                Hadir: {{ $attendances->total() }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                Absen: 0
                            </span>
                        </div>

                        @can('manageMinutes', $agenda)
                            <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="text-xs font-bold text-slate-900 hover:underline">
                                Unggah Foto
                            </a>
                        @endcan
                    </div>

                    <!-- Attendee List -->
                    <div class="p-4 divide-y divide-slate-200">
                        @forelse($attendances as $attendance)
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center gap-2.5 text-xs">
                                <div class="w-7 h-7 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-[10px] shrink-0">
                                    {{ substr($attendance->user->name, 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 truncate">{{ $attendance->user->name }}</div>
                                    <div class="text-[10px] text-slate-500 font-medium">{{ $attendance->user->unit?->kode_unit ?? 'Pusat' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-xs text-slate-500 font-medium">
                                Belum ada peserta yang melakukan presensi.
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($attendances->hasPages())
                    <div class="mt-auto">
                        {{ $attendances->links('vendor.pagination.compact') }}
                    </div>
                @endif
            </div>

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

        <!-- Right: Notulensi Rapat (3 cols) -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Notulensi Rapat Section -->
            <div class="panel">
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <h3 class="font-bold text-slate-900 text-sm">Notulensi Rapat</h3>
                    </div>

                    @can('manageMinutes', $agenda)
                        <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="btn btn-secondary py-1 px-3 text-xs inline-flex items-center gap-1.5" title="Buka Pengolah Kata Notulensi & Dokumentasi">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Notulensi</span>
                        </a>
                    @endcan
                </div>

                <div class="p-6 space-y-5">
                    <!-- Pembahasan -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">P</span>
                            Pembahasan
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
                            Keputusan & Tindak Lanjut
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
                            Rapat ditutup pada pukul {{ $agenda->waktu_selesai->format('H:i') }} WIB. Notulen ini disusun oleh Sekretaris/PJ dan telah diverifikasi oleh pihak terkait.
                        </p>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="border-t border-slate-200 px-6 py-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Pemimpin Rapat</div>
                            <div class="h-14 flex items-center justify-center">
                                <svg viewBox="0 0 100 40" width="80" height="32" class="text-slate-300"><path d="M10 30 Q 25 5 40 25 T 70 20 T 90 25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-900 border-t border-slate-300 pt-2">{{ $agenda->creator?->name ?? 'Pimpinan Rapat' }}</div>
                        </div>

                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Notulis</div>
                            <div class="h-14 flex items-center justify-center">
                                <svg viewBox="0 0 100 40" width="80" height="32" class="text-slate-300"><path d="M10 30 Q 25 5 40 25 T 70 20 T 90 25" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-900 border-t border-slate-300 pt-2">{{ $agenda->creator?->name ?? 'Notulis Rapat' }}</div>
                        </div>

                        <div class="text-center space-y-8">
                            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Direktur/PJ</div>
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

    <!-- Dokumentasi Foto & Lampiran (Full Width Bottom Section) -->
    <div class="panel">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                <h3 class="font-bold text-slate-900 text-sm">Dokumentasi Foto & Lampiran</h3>
            </div>

            <div class="flex items-center gap-2">
                @can('manageMinutes', $agenda)
                    <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Kelola Foto / Notulen</span>
                    </a>
                @endcan
                @if($documentations->hasPages())
                    <div class="flex items-center gap-1 text-xs">
                        {{ $documentations->links('vendor.pagination.compact') }}
                    </div>
                @endif
                <span class="text-xs font-bold text-slate-500">{{ $documentations->total() }} File</span>
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
@endsection
