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
        <div class="flex flex-wrap items-center gap-1.5 bg-slate-200/80 p-1.5 rounded-xl border border-slate-300">
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'all'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ !request('status') || request('status') === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
            >
                Semua ({{ $statusCounts['all'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'draft'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'draft' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
            >
                Draf / Konsep ({{ $statusCounts['draft'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'ongoing'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'ongoing' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
            >
                Sedang Berlangsung ({{ $statusCounts['ongoing'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'scheduled'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'scheduled' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
            >
                Terjadwal ({{ $statusCounts['scheduled'] }})
            </a>
            <a 
                href="{{ route('admin.agendas.index', ['status' => 'completed'] + request()->except('status', 'page')) }}" 
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ request('status') === 'completed' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900' }}"
            >
                Selesai ({{ $statusCounts['completed'] }})
            </a>
        </div>

        @if($currentUser->isAdministrator() || $currentUser->isAdmin())
            <a href="{{ route('admin.agendas.create') }}" class="button small flex items-center gap-2 text-xs self-start lg:self-auto shrink-0 font-bold">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
                    class="input w-full pl-9 text-xs font-medium"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Tipe Rapat Filter -->
            <select name="tipe" onchange="this.form.submit()" class="input text-xs sm:w-56 font-semibold">
                <option value="">Semua Format Pelaksanaan</option>
                <option value="offline" {{ request('tipe') === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring)</option>
                <option value="online" {{ request('tipe') === 'online' ? 'selected' : '' }}>Daring (Virtual)</option>
                <option value="hybrid" {{ request('tipe') === 'hybrid' ? 'selected' : '' }}>Hibrida</option>
            </select>

            @if($currentUser->isAdministrator() && isset($units) && $units->isNotEmpty())
                <!-- Unit Kerja Filter (Khusus Administrator) -->
                <select name="unit_id" onchange="this.form.submit()" class="input text-xs sm:w-56 font-semibold">
                    <option value="">Semua Sasaran Unit Kerja</option>
                    <option value="all_units" {{ request('unit_id') === 'all_units' ? 'selected' : '' }}>Seluruh Unit (Pleno / Universal)</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->kode_unit }} — {{ $u->nama_unit }}
                        </option>
                    @endforeach
                </select>
            @endif

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs font-bold">Cari</button>
                @if(request()->hasAny(['search', 'tipe', 'unit_id']))
                    <a href="{{ route('admin.agendas.index', ['status' => request('status', 'all')]) }}" class="text-xs text-slate-700 hover:text-slate-900 font-bold underline">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Agendas Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($agendas as $agenda)
            <x-agenda-card
                :agenda="$agenda"
                :detail-route="route('admin.agendas.show', $agenda)"
            >
                <x-slot:bodyExtra>
                    {{-- Organiser & the viewer's relationship to this meeting --}}
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-1.5 truncate text-slate-600">
                            <svg class="text-slate-500 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <span class="truncate">Oleh: <strong class="text-slate-800">{{ $agenda->creator?->unit?->kode_unit ?? ($agenda->creator?->isAdministrator() ? 'Pusat' : 'Penyelenggara') }}</strong></span>
                        </div>

                        @if($currentUser->isAdmin() && !$currentUser->isAdministrator())
                            @if($agenda->created_by === $currentUser->id || ($currentUser->unit_id !== null && $agenda->creatorUnitId() === $currentUser->unit_id))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200 shrink-0">
                                    Penyelenggara
                                </span>
                            @elseif($agenda->status === 'ongoing' && ($agenda->pZpimpinan_id === $currentUser->id || $agenda->notulis_id === $currentUser->id))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shrink-0">
                                    {{ $agenda->pZpimpinan_id === $currentUser->id ? 'Pimpinan Rapat' : 'Notulis Rapat' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                                    Unit Partisipan
                                </span>
                            @endif
                        @endif
                    </div>
                </x-slot:bodyExtra>

                <x-slot:footer>
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button small secondary text-xs font-bold">
                        Lihat Detail
                    </a>

                    <div class="flex items-center gap-1.5">
                        @can('manageMinutes', $agenda)
                            <a href="{{ route('admin.agendas.notulen', $agenda) }}" class="button small secondary text-xs font-bold text-slate-900" title="Notulensi & Foto">
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
                                    <button type="submit" class="button small text-xs bg-amber-700 hover:bg-amber-800 text-white font-bold" title="Buka Sesi Presensi">
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
                                    <button type="submit" class="button small text-xs bg-emerald-700 hover:bg-emerald-800 text-white font-bold" title="Tutup Rapat">
                                        Selesai
                                    </button>
                                </form>
                            @endif
                        @endcan

                        @can('delete', $agenda)
                            @if(!$agenda->hasAttendances())
                                <form 
                                    action="{{ route('admin.agendas.destroy', $agenda) }}" 
                                    method="POST" 
                                    class="inline"
                                    data-confirm="Apakah Anda yakin ingin menghapus agenda '{{ $agenda->judul_rapat }}'? Seluruh data presensi dan lampiran dokumentasi akan dihapus permanen!"
                                    data-confirm-title="Hapus Agenda"
                                    data-confirm-type="danger"
                                    data-confirm-btn="Ya, Hapus"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button small text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 font-bold px-2 py-1 flex items-center justify-center transition" title="Hapus Agenda">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        <span class="sr-only">Hapus Agenda</span>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </x-slot:footer>
            </x-agenda-card>
        @empty
            <x-ui.empty-state
                icon="calendar"
                title="Tidak Ada Agenda Rapat"
                description="Belum ada agenda rapat yang sesuai dengan filter yang dipilih."
            />
        @endforelse
    </div>

    @if($agendas->hasPages())
        <div class="bg-white rounded-2xl border border-slate-300 shadow-xs overflow-hidden">
            {{ $agendas->links() }}
        </div>
    @endif
</div>
@endsection
