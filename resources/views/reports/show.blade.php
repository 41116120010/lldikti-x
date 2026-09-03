@extends('layouts.app')

@section('title', 'Rekap: ' . $agenda->judul_rapat)
@section('heading', 'Rekapitulasi Kehadiran & Dokumen Rapat')
@section('subtitle', $agenda->judul_rapat)

@section('content')
<div class="space-y-6">
    <!-- Action Bar & Export Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-300 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('admin.reports.index') }}" class="button small secondary text-xs font-bold flex items-center gap-1.5 self-start sm:self-auto">
            &larr; Kembali ke Daftar Rekap
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <!-- PDF Export Button -->
            <a 
                href="{{ route('admin.reports.export.pdf', $agenda) }}" 
                target="_blank" 
                class="button small flex items-center gap-1.5 text-xs bg-slate-950 hover:bg-slate-800 text-white font-bold shadow-xs"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                <span>Cetak Berita Acara (PDF)</span>
            </a>

            <!-- Word Export Button -->
            <a 
                href="{{ route('admin.reports.export.word', $agenda) }}" 
                class="button small flex items-center gap-1.5 text-xs bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300 font-bold shadow-xs"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <span>Unduh Format Word (.doc)</span>
            </a>

            <a href="{{ route('admin.agendas.show', $agenda) }}" class="button small secondary text-xs font-bold">
                Kelola Agenda
            </a>
        </div>
    </div>

    <!-- Meeting Metadata Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="panel p-4 text-xs space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-600 font-mono">Waktu Rapat</div>
            <div class="font-bold text-slate-950">{{ $agenda->waktu_mulai->translatedFormat('d M Y') }}</div>
            <div class="text-slate-700 font-mono font-medium">{{ $agenda->waktu_mulai->format('H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</div>
        </div>

        <div class="panel p-4 text-xs space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-600 font-mono">Format & Lokasi</div>
            <div class="font-bold text-slate-950 uppercase font-mono">{{ $agenda->tipe_rapat }}</div>
            <div class="text-slate-700 truncate font-medium">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</div>
        </div>

        <div class="panel p-4 text-xs space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-600 font-mono">Penyelenggara</div>
            <div class="font-bold text-slate-950">{{ $agenda->creator->name }}</div>
            <div class="text-slate-700 font-medium">{{ $agenda->creator->unit?->kode_unit ?? 'Pusat' }}</div>
        </div>

        <div class="panel p-4 text-xs space-y-1">
            <div class="text-[10px] uppercase font-bold text-slate-600 font-mono">Total Kehadiran</div>
            <div class="text-2xl font-bold font-mono text-slate-950">{{ $agenda->attendances->count() }} Pegawai</div>
            <div class="text-slate-700 font-medium">Presensi Tervalidasi</div>
        </div>
    </div>

    <!-- Table of Attendees with Verified Media -->
    <div class="panel">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-950" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <h3 class="font-bold text-slate-950 text-sm">Daftar Kehadiran Pegawai ({{ $agenda->attendances->count() }})</h3>
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
                                <div class="w-10 h-10 rounded-lg overflow-hidden border border-slate-300 mx-auto shadow-xs">
                                    <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="w-16 h-10 rounded-lg overflow-hidden border border-slate-300 bg-white mx-auto shadow-xs flex items-center justify-center p-1">
                                    <img src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" class="max-w-full max-h-full object-contain">
                                </div>
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

    <!-- Notulensi & Conclusions Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="panel p-6 space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">Notulensi Rapat:</h4>
            <div class="text-xs text-slate-800 font-medium leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-300">
                {{ $agenda->notulensi ?: 'Belum ada notulensi yang dicatat.' }}
            </div>
        </div>

        <div class="panel p-6 space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">Kesimpulan & RTL:</h4>
            <div class="text-xs text-slate-800 font-medium leading-relaxed whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-300">
                {{ $agenda->kesimpulan ?: 'Belum ada kesimpulan yang dicatat.' }}
            </div>
        </div>
    </div>
</div>
@endsection
