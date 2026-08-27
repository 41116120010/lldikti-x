@extends('layouts.app')

@section('title', 'Riwayat Presensi Pribadi')
@section('heading', 'Riwayat Kehadiran Rapat')
@section('subtitle', 'Daftar seluruh kehadiran rapat kedinasan yang pernah Anda ikuti')

@section('content')
<div class="space-y-5">
    <!-- Action Bar & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('attendances.history') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari judul rapat atau lokasi..." 
                    class="input w-full pl-9 text-xs"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs">Cari</button>
                @if(request('search'))
                    <a href="{{ route('attendances.history') }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </div>
        </form>

        <a href="{{ route('attendances.portal') }}" class="button small flex items-center gap-1.5 text-xs self-start sm:self-auto shrink-0 bg-blue-600 hover:bg-blue-700 text-white">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <span>Portal Presensi Aktif</span>
        </a>
    </div>

    <!-- Attendance History Table -->
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-16 text-center">No</th>
                        <th>Agenda Rapat</th>
                        <th>Jadwal Pelaksanaan</th>
                        <th>Waktu Presensi</th>
                        <th class="text-center">Verifikasi Media</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $index => $att)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="text-center text-xs text-slate-400 font-mono">{{ $attendances->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-900 text-sm hover:text-blue-600 transition">
                                    <a href="{{ route('admin.agendas.show', $att->agenda) }}">
                                        {{ $att->agenda->judul_rapat }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2 font-mono mt-0.5">
                                    <span class="uppercase font-semibold text-slate-500">{{ $att->agenda->tipe_rapat }}</span> &bull; 
                                    <span>{{ $att->agenda->lokasi_ruang ?? 'Daring' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs font-semibold text-slate-800">{{ $att->agenda->waktu_mulai->translatedFormat('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $att->agenda->waktu_mulai->format('H:i') }} - {{ $att->agenda->waktu_selesai->format('H:i') }} WIB</div>
                            </td>
                            <td>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ $att->signed_at->format('d/m/Y H:i:s') }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="inline-flex items-center gap-2">
                                    <!-- Selfie Thumb -->
                                    <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-300 shadow-xs" title="Foto Selfie Wajah">
                                        <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" class="w-full h-full object-cover">
                                    </div>

                                    <!-- Signature Thumb -->
                                    <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-300 bg-white shadow-xs p-0.5" title="Tanda Tangan Digital">
                                        <img src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" class="w-full h-full object-contain">
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('attendances.success', [$att->agenda, $att]) }}" class="button small secondary text-xs">
                                    Bukti Sah
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-search">
                                Belum ada catatan kehadiran rapat yang ditemukan.
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
</div>
@endsection
