@extends('layouts.app')

@section('title', 'Agenda Rapat')
@section('heading', 'Agenda Rapat Kedinasan')
@section('subtitle', 'Daftar pertemuan, rapat koordinasi, dan agenda kedinasan LLDIKTI')

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
@endphp
<div class="space-y-6">
    <!-- Status Tabs & Header Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 bg-slate-200/70 p-1.5 rounded-xl">
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'all'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ !request('status') || request('status') === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Semua ({{ $statusCounts['all'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'ongoing'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'ongoing' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Sedang Berlangsung ({{ $statusCounts['ongoing'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'scheduled'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'scheduled' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Terjadwal ({{ $statusCounts['scheduled'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'completed'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Selesai ({{ $statusCounts['completed'] }})
            </a>
        </div>

        @if($currentUser->isAdministrator() || $currentUser->isAdmin())
            <a href="{{ route('admin.agendas.create') }}" class="button small flex items-center gap-2 text-xs self-start lg:self-auto shrink-0">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>Buat Agenda Rapat</span>
            </a>
        @endif
    </div>

    <!-- Search & Filters Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.agendas.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="hidden" name="status" value="{{ request('status', 'all') }}">

            <!-- Search Title / Location -->
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari judul rapat atau ruangan..." 
                    class="input w-full pl-9 text-xs"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Tipe Rapat Filter -->
            <select name="tipe" onchange="this.form.submit()" class="input text-xs sm:w-56">
                <option value="">Semua Format Pelaksanaan</option>
                <option value="offline" {{ request('tipe') === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring)</option>
                <option value="online" {{ request('tipe') === 'online' ? 'selected' : '' }}>Daring (Virtual)</option>
                <option value="hybrid" {{ request('tipe') === 'hybrid' ? 'selected' : '' }}>Hibrida</option>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs">Cari</button>
                @if(request()->hasAny(['search', 'tipe']))
                    <a href="{{ route('admin.agendas.index', ['status' => request('status', 'all')]) }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Agendas Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($agendas as $agenda)
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                <div>
                    <!-- Top Card Header: Badges & Status -->
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @php
                                $statusStyle = match($agenda->status) {
                                    'ongoing' => 'bg-amber-50 text-amber-700 border-amber-300 font-bold',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
                                    'draft' => 'bg-slate-100 text-slate-600 border-slate-300',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200'
                                };
                                $statusLabel = match($agenda->status) {
                                    'ongoing' => 'Sedang Berlangsung',
                                    'completed' => 'Selesai',
                                    'draft' => 'Konsep',
                                    'cancelled' => 'Dibatalkan',
                                    default => 'Terjadwal'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $statusStyle }}">
                                {{ $statusLabel }}
                            </span>

                            <span class="text-[11px] font-mono uppercase px-2 py-0.5 bg-slate-100 text-slate-600 rounded">
                                {{ $agenda->tipe_rapat }}
                            </span>
                        </div>

                        <!-- Attendee Counter Badge -->
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md" title="Jumlah Peserta Hadir">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            {{ $agenda->attendances->count() }} Hadir
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 space-y-3">
                        <h3 class="font-bold text-slate-900 text-base leading-snug hover:text-blue-600 transition">
                            <a href="{{ route('admin.agendas.show', $agenda) }}">
                                {{ $agenda->judul_rapat }}
                            </a>
                        </h3>

                        <div class="space-y-1.5 text-xs text-slate-500">
                            <!-- Datetime -->
                            <div class="flex items-center gap-2">
                                <svg class="text-slate-400 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                            </div>

                            <!-- Venue / Link -->
                            <div class="flex items-center gap-2">
                                <svg class="text-slate-400 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span class="truncate">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                            </div>

                            <!-- Target Units Scope -->
                            <div class="flex items-center gap-2 pt-1">
                                <svg class="text-slate-400 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                                @if($agenda->is_all_units)
                                    <span class="text-emerald-700 font-semibold">Seluruh Unit LLDIKTI (Pleno)</span>
                                @else
                                    <span class="text-blue-700 font-semibold">{{ $agenda->units->pluck('kode_unit')->join(', ') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button small secondary text-xs">
                        Lihat Detail
                    </a>

                    <div class="flex items-center gap-1.5">
                        @can('manageMinutes', $agenda)
                            <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="button small secondary text-xs text-indigo-700 hover:bg-indigo-50 border-indigo-200" title="Notulensi & Foto">
                                Notulensi
                            </a>
                        @endcan

                        @can('manageStatus', $agenda)
                            @if($agenda->status === 'scheduled')
                                <form 
                                    action="{{ route('admin.agendas.update-status', $agenda) }}" 
                                    method="POST" 
                                    class="inline"
                                    data-confirm="Buka sesi presensi rapat '{{ $agenda->judul_rapat }}' sekarang? Pegawai akan dapat langsung melakukan presensi."
                                    data-confirm-title="Buka Sesi Presensi"
                                    data-confirm-type="confirm"
                                    data-confirm-btn="Ya, Mulai Sesi"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="ongoing">
                                    <button type="submit" class="button small text-xs bg-amber-600 hover:bg-amber-700 text-white" title="Buka Sesi Presensi">
                                        Mulai
                                    </button>
                                </form>
                            @elseif($agenda->status === 'ongoing')
                                <form 
                                    action="{{ route('admin.agendas.update-status', $agenda) }}" 
                                    method="POST" 
                                    class="inline"
                                    data-confirm="Selesaikan dan tutup sesi presensi rapat '{{ $agenda->judul_rapat }}'? Pegawai tidak dapat lagi mengisi presensi setelah ini."
                                    data-confirm-title="Selesaikan Sesi Rapat"
                                    data-confirm-type="warning"
                                    data-confirm-btn="Ya, Selesaikan Rapat"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="button small text-xs bg-emerald-600 hover:bg-emerald-700 text-white" title="Tutup Rapat">
                                        Selesai
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-slate-200 p-8">
                <svg class="mx-auto text-slate-300 mb-3" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <h4 class="text-sm font-bold text-slate-700">Tidak Ada Agenda Rapat</h4>
                <p class="text-xs text-slate-500 mt-1">Belum ada agenda rapat yang sesuai dengan filter yang dipilih.</p>
            </div>
        @endforelse
    </div>

    @if($agendas->hasPages())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            {{ $agendas->links() }}
        </div>
    @endif
</div>
@endsection
