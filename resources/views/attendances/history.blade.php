@extends('layouts.app')

@section('title', 'Riwayat Presensi Pribadi')
@section('heading', 'Riwayat Kehadiran Rapat')
@section('subtitle', 'Daftar seluruh kehadiran rapat kedinasan yang pernah Anda ikuti')

@section('content')
<div class="space-y-5">
    <!-- Action Bar & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-300 shadow-xs">
        <form method="GET" action="{{ route('attendances.history') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari judul rapat atau lokasi..." 
                    class="input w-full pl-9 text-xs font-medium"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs font-bold">Cari</button>
                @if(request('search'))
                    <a href="{{ route('attendances.history') }}" class="button small secondary text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
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
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold">{{ $attendances->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-slate-950 text-sm hover:text-slate-700 transition">
                                    @php
                                        $attDetailRoute = (Auth::user()->isAdministrator() || Auth::user()->isAdmin()) 
                                            ? route('admin.agendas.show', $att->agenda) 
                                            : route('agendas.show', $att->agenda);
                                    @endphp
                                    <a href="{{ $attDetailRoute }}">
                                        {{ $att->agenda->judul_rapat }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-600 flex items-center gap-2 font-mono mt-0.5 font-medium">
                                    <span class="uppercase font-bold text-slate-900">{{ $att->agenda->tipe_rapat }}</span> &bull; 
                                    <span>{{ $att->agenda->lokasi_ruang ?? 'Daring' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-xs font-bold text-slate-950">{{ $att->agenda->waktu_mulai->translatedFormat('d M Y') }}</div>
                                <div class="text-[11px] text-slate-600 font-mono font-medium">{{ $att->agenda->waktu_mulai->format('H:i') }} - {{ $att->agenda->waktu_selesai->format('H:i') }} WIB</div>
                            </td>
                            <td>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-emerald-100 text-emerald-950 border border-emerald-300">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ $att->signed_at->format('d/m/Y H:i:s') }}
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="inline-flex items-center gap-2">
                                    <!-- Selfie Thumb (Clickable) -->
                                    <button 
                                        type="button" 
                                        onclick="previewAttendanceMedia('{{ Storage::disk('public')->url($att->selfie_path) }}', 'Foto Selfie Wajah')"
                                        class="w-8 h-8 rounded-lg overflow-hidden border border-slate-400 shadow-xs hover:border-slate-950 hover:ring-2 hover:ring-slate-400 transition cursor-pointer" 
                                        title="Klik untuk memperbesar Foto Selfie"
                                    >
                                        <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" class="w-full h-full object-cover">
                                    </button>

                                    <!-- Signature Thumb (Clickable) -->
                                    <button 
                                        type="button" 
                                        onclick="previewAttendanceMedia('{{ Storage::disk('public')->url($att->signature_path) }}', 'Tanda Tangan Digital')"
                                        class="w-8 h-8 rounded-lg overflow-hidden border border-slate-400 bg-white shadow-xs p-0.5 hover:border-slate-950 hover:ring-2 hover:ring-slate-400 transition cursor-pointer" 
                                        title="Klik untuk memperbesar Tanda Tangan"
                                    >
                                        <img src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" class="w-full h-full object-contain">
                                    </button>
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('attendances.success', [$att->agenda, $att]) }}" class="button small secondary text-xs font-bold">
                                    Bukti Sah
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-search py-10">
                                <div class="space-y-2 text-center">
                                    <p class="text-slate-700 font-bold text-xs">Belum ada catatan kehadiran rapat yang ditemukan.</p>
                                    @if(request('search'))
                                        <a href="{{ route('attendances.history') }}" class="button small secondary inline-flex text-xs font-bold">
                                            Reset Pencarian
                                        </a>
                                    @endif
                                </div>
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

<script>
function previewAttendanceMedia(mediaUrl, title) {
    window.showModal({
        title: title,
        message: `
            <div class="text-center p-2">
                <div class="max-w-xs mx-auto rounded-xl overflow-hidden border border-slate-300 shadow-md bg-white">
                    <img src="${mediaUrl}" alt="${title}" class="w-full h-auto object-contain max-h-80">
                </div>
            </div>
        `,
        type: 'info',
        confirmText: 'Tutup'
    });
}
</script>
@endsection
