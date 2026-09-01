@extends('layouts.app')

@section('title', 'Kelola Unit Kerja')
@section('heading', 'Master Data Unit Kerja')
@section('subtitle', 'Daftar Bagian dan Kelompok Kerja (Pokja) di lingkungan LLDIKTI')

@section('content')
<div class="space-y-5">
    <!-- Action Bar & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-300 shadow-xs">
        <form method="GET" action="{{ route('admin.units.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama atau kode unit..." 
                    class="input w-full pl-9 text-xs font-medium"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="input text-xs sm:w-44 font-semibold">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs font-bold">Cari</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.units.index') }}" class="button small secondary text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>

        <a href="{{ route('admin.units.create') }}" class="button small flex items-center gap-2 text-xs self-start lg:self-auto shrink-0 font-bold">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Tambah Unit Kerja</span>
        </a>
    </div>

    <!-- Units Table -->
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-12 text-center whitespace-nowrap">No</th>
                        <th class="whitespace-nowrap min-w-[130px]">Kode Unit</th>
                        <th class="min-w-[240px]">Nama Unit Kerja</th>
                        <th>Deskripsi</th>
                        <th class="text-center whitespace-nowrap">Jumlah Pegawai</th>
                        <th class="text-center whitespace-nowrap">Agenda Rapat</th>
                        <th class="text-center whitespace-nowrap">Status</th>
                        <th class="text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $index => $unit)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold whitespace-nowrap">{{ $units->firstItem() + $index }}</td>
                            <td class="whitespace-nowrap">
                                <span class="inline-block whitespace-nowrap font-mono font-bold text-xs px-2.5 py-1 rounded-md bg-slate-100 text-slate-950 border border-slate-300">
                                    {{ $unit->kode_unit }}
                                </span>
                            </td>
                            <td class="min-w-[240px]">
                                <div class="font-bold text-slate-900 text-sm leading-snug">{{ $unit->nama_unit }}</div>
                                <div class="text-[11px] text-slate-600 font-mono font-medium mt-0.5">Dibuat: {{ $unit->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="max-w-xs text-xs text-slate-800 leading-relaxed font-medium">
                                {{ $unit->deskripsi ?? '-' }}
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 font-bold text-xs text-slate-900 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    {{ $unit->users_count }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 font-bold text-xs text-slate-900 bg-slate-100 border border-slate-300 px-2.5 py-1 rounded-full">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                    {{ $unit->agendas_count }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                @if($unit->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                        Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="row-actions justify-end">
                                    <!-- Toggle Status Button -->
                                    <form 
                                        action="{{ route('admin.units.toggle-status', $unit) }}" 
                                        method="POST" 
                                        class="inline"
                                        data-confirm="Apakah Anda yakin ingin {{ $unit->is_active ? 'menonaktifkan' : 'mengaktifkan kembali' }} unit kerja '{{ $unit->nama_unit }}'?"
                                        data-confirm-title="{{ $unit->is_active ? 'Nonaktifkan Unit Kerja' : 'Aktifkan Unit Kerja' }}"
                                        data-confirm-type="{{ $unit->is_active ? 'warning' : 'confirm' }}"
                                        data-confirm-btn="Ya, Lanjutkan"
                                    >
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit" 
                                            class="action-btn text-xs font-bold {{ $unit->is_active ? 'text-amber-800 hover:bg-amber-50' : 'text-emerald-800 hover:bg-emerald-50' }}"
                                            title="{{ $unit->is_active ? 'Non-aktifkan Unit' : 'Aktifkan Unit' }}"
                                        >
                                            {{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <a 
                                        href="{{ route('admin.units.edit', $unit) }}" 
                                        class="action-btn action-icon action-edit" 
                                        title="Edit Unit"
                                    >
                                        <svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg>
                                    </a>

                                    <!-- Delete Button (Only if 0 users and 0 agendas) -->
                                    @if($unit->users_count === 0 && $unit->agendas_count === 0)
                                        <form 
                                            action="{{ route('admin.units.destroy', $unit) }}" 
                                            method="POST" 
                                            class="inline"
                                            data-confirm="Apakah Anda yakin ingin menghapus unit kerja '{{ $unit->nama_unit }}' secara permanen?"
                                            data-confirm-title="Hapus Unit Kerja"
                                            data-confirm-type="warning"
                                            data-confirm-btn="Ya, Hapus Unit"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="action-btn action-icon action-delete" 
                                                title="Hapus Unit"
                                            >
                                                <svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v5m4-5v5M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-search py-10">
                                <div class="space-y-2 text-center">
                                    <p class="text-slate-700 font-bold text-xs">Tidak ada data unit kerja yang sesuai dengan kriteria pencarian.</p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.units.index') }}" class="button small secondary inline-flex text-xs font-bold">
                                            Reset Pencarian & Filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($units->hasPages())
            {{ $units->links() }}
        @endif
    </div>
</div>
@endsection
