@extends('layouts.app')

@section('title', 'Bukti Presensi — ' . $agenda->judul_rapat)
@section('heading', 'Tanda Terima Presensi Sah')
@section('subtitle', 'Bukti digital perekaman kehadiran rapat kedinasan LLDIKTI')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Official Attendance Card Receipt -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-lg overflow-hidden">
        <!-- Top Receipt Banner -->
        <div class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white p-6 sm:p-7 text-center space-y-2">
            <div class="w-14 h-14 rounded-full bg-white/20 border border-white/30 flex items-center justify-center mx-auto mb-1 text-white shadow-md">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-400/30 text-emerald-200 border border-emerald-300/40 uppercase tracking-wider">
                Kehadiran Terverifikasi Sah
            </span>
            <h2 class="text-lg font-bold text-white tracking-tight">Tanda Terima Presensi Digital</h2>
            <p class="text-xs text-emerald-100 font-mono">ID Bukti: #ATT-{{ str_pad($attendance->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>

        <!-- Receipt Body Details -->
        <div class="p-6 sm:p-8 space-y-6">
            <!-- Rapat Info -->
            <div class="space-y-1.5 pb-5 border-b border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Agenda Pertemuan Rapat:</div>
                <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $agenda->judul_rapat }}</h3>
                <div class="text-xs text-slate-500 flex flex-wrap items-center gap-3 pt-1">
                    <span>🕒 {{ $agenda->waktu_mulai->translatedFormat('l, d F Y &bull; H:i') }} WIB</span>
                    <span>📍 {{ $agenda->lokasi_ruang ?? 'Daring / Online Meeting' }}</span>
                </div>
            </div>

            <!-- Pegawai Info -->
            <div class="grid grid-cols-2 gap-4 text-xs pb-5 border-b border-slate-100">
                <div>
                    <span class="text-slate-400 block text-[11px]">Nama Lengkap Pegawai:</span>
                    <strong class="text-slate-900 text-sm">{{ $attendance->user->name }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Nomor Induk Pegawai (NIP):</span>
                    <strong class="font-mono text-slate-900 text-sm">{{ $attendance->user->nip }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Unit Kerja / Pokja:</span>
                    <strong class="text-slate-800">{{ $attendance->user->unit?->nama_unit ?? 'Tingkat Lembaga' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Waktu Presensi Tercatat:</span>
                    <strong class="font-mono text-emerald-700">{{ $attendance->signed_at->translatedFormat('d/m/Y &bull; H:i:s') }} WIB</strong>
                </div>
            </div>

            <!-- Media Verification Thumbnails -->
            <div class="space-y-3">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Bukti Autentikasi Kehadiran:</div>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Selfie Thumbnail -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-2">
                        <div class="text-[11px] font-semibold text-slate-600">Foto Selfie Wajah</div>
                        <div class="w-full aspect-[4/3] rounded-lg overflow-hidden bg-slate-200 border border-slate-300">
                            <img src="{{ Storage::disk('public')->url($attendance->selfie_path) }}" alt="Foto Selfie" class="w-full h-full object-cover">
                        </div>
                    </div>

                    <!-- Signature Thumbnail -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-center space-y-2">
                        <div class="text-[11px] font-semibold text-slate-600">Tanda Tangan Digital</div>
                        <div class="w-full aspect-[4/3] rounded-lg overflow-hidden bg-white border border-slate-300 flex items-center justify-center p-2">
                            <img src="{{ Storage::disk('public')->url($attendance->signature_path) }}" alt="Tanda Tangan" class="max-w-full max-h-full object-contain">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technical Metadata Footer -->
            <div class="p-3 bg-slate-50 rounded-xl text-[10px] text-slate-400 font-mono space-y-0.5">
                <div>Alamat IP: {{ $attendance->ip_address ?? '127.0.0.1' }}</div>
                <div class="truncate">Perangkat: {{ $attendance->user_agent }}</div>
            </div>
        </div>

        <!-- Receipt Actions -->
        <div class="p-5 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a href="{{ route('attendances.portal') }}" class="button small secondary w-full sm:w-auto text-xs text-center">
                &larr; Kembali ke Portal Presensi
            </a>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('admin.agendas.show', $agenda) }}" class="button small secondary flex-1 sm:flex-initial text-xs text-center">
                    Detail Agenda
                </a>
                <button type="button" onclick="window.print()" class="button small flex-1 sm:flex-initial flex items-center justify-center gap-1.5 text-xs bg-slate-800 hover:bg-slate-900 text-white">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    <span>Cetak Tanda Terima</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
