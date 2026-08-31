@extends('layouts.app')

@section('title', 'Bukti Presensi — ' . $agenda->judul_rapat)
@section('heading', 'Tanda Terima Presensi Sah')
@section('subtitle', 'Bukti digital perekaman kehadiran rapat kedinasan LLDIKTI')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start printable-container">
    <!-- Left: Official Attendance Card Receipt (2 cols) -->
    <div class="lg:col-span-2 space-y-6 print:w-full print:col-span-3">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-sm overflow-hidden print:border print:border-slate-400 print:shadow-none">
            <!-- Top Receipt Banner -->
            <div class="bg-slate-950 text-white p-6 sm:p-7 text-center space-y-2 border-b border-slate-800 print:bg-slate-900 print:text-white">
                <div class="w-12 h-12 rounded-full bg-emerald-600 border border-emerald-500 flex items-center justify-center mx-auto mb-1 text-white shadow-sm">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-900 text-white border border-emerald-700 uppercase tracking-wider print:bg-slate-900 print:text-white">
                    Kehadiran Terverifikasi Sah
                </span>
                <h2 class="text-lg font-extrabold text-white tracking-tight">Tanda Terima Presensi Digital</h2>
                <p class="text-xs text-slate-300 font-mono font-bold">ID Bukti: #ATT-{{ str_pad($attendance->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>

            <!-- Receipt Body Details -->
            <div class="p-6 sm:p-8 space-y-6">
                <!-- Rapat Info -->
                <div class="space-y-1.5 pb-5 border-b border-slate-200 print:border-slate-300">
                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-700 font-mono">Agenda Pertemuan Rapat:</div>
                    <h3 class="text-base font-bold text-slate-950 leading-snug">{{ $agenda->judul_rapat }}</h3>
                    <div class="text-xs text-slate-700 flex flex-wrap items-center gap-3 pt-1 font-medium">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="text-slate-600 shrink-0 print:hidden" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="font-bold text-slate-900">{{ $agenda->waktu_mulai->translatedFormat('l, d F Y &bull; H:i') }} WIB</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="text-slate-600 shrink-0 print:hidden" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Pegawai Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pb-5 border-b border-slate-200 print:border-slate-300">
                    <div>
                        <span class="text-slate-600 block text-[11px] font-bold">Nama Lengkap Pegawai:</span>
                        <strong class="text-slate-950 text-sm font-extrabold">{{ $attendance->user->name }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-600 block text-[11px] font-bold">Nomor Induk Pegawai (NIP):</span>
                        <strong class="font-mono text-slate-950 text-sm font-bold">{{ $attendance->user->nip }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-600 block text-[11px] font-bold">Unit Kerja / Pokja:</span>
                        <strong class="text-slate-900 font-bold">{{ $attendance->user->unit?->nama_unit ?? 'Tingkat Lembaga' }}</strong>
                    </div>
                    <div>
                        <span class="text-slate-600 block text-[11px] font-bold">Waktu Presensi Tercatat:</span>
                        <strong class="font-mono text-slate-950 font-bold">{{ $attendance->signed_at->translatedFormat('d/m/Y &bull; H:i:s') }} WIB</strong>
                    </div>
                </div>

                <!-- Media Verification Thumbnails -->
                <div class="space-y-3">
                    <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-700 font-mono">Bukti Autentikasi Kehadiran:</div>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Selfie Thumbnail -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-300 text-center space-y-2 print:border-slate-300">
                            <div class="text-[11px] font-bold text-slate-900">Foto Selfie Wajah</div>
                            <div class="w-full aspect-[4/3] max-w-[200px] mx-auto rounded-lg overflow-hidden bg-slate-200 border border-slate-300">
                                <img src="{{ Storage::disk('public')->url($attendance->selfie_path) }}" alt="Foto Selfie" class="w-full h-full object-cover">
                            </div>
                        </div>

                        <!-- Signature Thumbnail -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-300 text-center space-y-2 print:border-slate-300">
                            <div class="text-[11px] font-bold text-slate-900">Tanda Tangan Digital</div>
                            <div class="w-full aspect-[4/3] max-w-[200px] mx-auto rounded-lg overflow-hidden bg-white border border-slate-300 flex items-center justify-center p-2">
                                <img src="{{ Storage::disk('public')->url($attendance->signature_path) }}" alt="Tanda Tangan" class="max-w-full max-h-full object-contain">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Metadata Footer -->
                <div class="p-3 bg-slate-100 rounded-xl text-[10px] text-slate-700 font-mono space-y-0.5 print:border print:border-slate-300">
                    <div><b class="text-slate-900">Alamat IP:</b> {{ $attendance->ip_address ?? '127.0.0.1' }}</div>
                    <div class="truncate"><b class="text-slate-900">Perangkat:</b> {{ $attendance->user_agent }}</div>
                </div>
            </div>

            <!-- Receipt Actions (Hidden on print) -->
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 print:hidden">
                <a href="{{ route('attendances.portal') }}" class="button small secondary w-full sm:w-auto text-xs text-center font-bold">
                    &larr; Kembali ke Portal Presensi
                </a>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button small secondary flex-1 sm:flex-initial text-xs text-center font-bold">
                        Detail Agenda
                    </a>
                    <button type="button" onclick="window.print()" class="button small flex-1 sm:flex-initial flex items-center justify-center gap-1.5 text-xs bg-slate-950 hover:bg-slate-800 text-white font-bold cursor-pointer">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        <span>Cetak Tanda Terima</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Summary & Quick Navigation (Hidden on print) (1 col) -->
    <div class="lg:col-span-1 space-y-6 print:hidden">
        <!-- Status Summary Card -->
        <div class="panel p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Ringkasan Validasi</h3>
            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-700 font-medium">Status Kehadiran:</span>
                    <span class="status completed font-bold">Hadir Sah</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-700 font-medium">Metode Verifikasi:</span>
                    <span class="text-slate-950 font-bold">Selfie + TTD Digital</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-700 font-medium">Total Hadir Rapat:</span>
                    <span class="font-mono font-bold text-slate-950">{{ $agenda->attendances->count() }} Pegawai</span>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="panel p-5 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Navigasi Lainnya</h4>
            <div class="space-y-2">
                <a href="{{ route('attendances.history') }}" class="button small secondary w-full flex items-center justify-between text-xs font-bold">
                    <span>Lihat Riwayat Presensi Saya</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <a href="{{ route('attendances.portal') }}" class="button small secondary w-full flex items-center justify-between text-xs font-bold">
                    <span>Presensi Rapat Lainnya</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, #mobile-sidebar-toggle, #sidebar-overlay, .print\:hidden {
        display: none !important;
    }
    .content, .shell {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background: #ffffff !important;
    }
    body {
        background: #ffffff !important;
    }
}
</style>
@endsection
