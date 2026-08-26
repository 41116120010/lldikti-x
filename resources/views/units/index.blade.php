@extends('layouts.app')

@section('title', 'Kelola Unit Kerja')
@section('heading', 'Master Data Unit Kerja')
@section('subtitle', 'Daftar Bagian dan Kelompok Kerja (Pokja) di lingkungan LLDIKTI')

@section('content')
<div class="space-y-5">
    <!-- Action Bar & Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.units.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative min-w-[240px] flex-1 max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama atau kode unit..." 
                    class="input w-full pl-9 text-xs"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="input text-xs">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
            </select>

            <button type="submit" class="button small secondary text-xs">Cari</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.units.index') }}" class="text-xs text-slate-500 hover:text-slate-800">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.units.create') }}" class="button small flex items-center gap-2 text-xs self-start sm:self-auto shrink-0">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Tambah Unit Kerja</span>
        </a>
    </div>

    <!-- Units Table -->
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-16 text-center">No</th>
                        <th>Kode Unit</th>
                        <th>Nama Unit Kerja</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Jumlah Pegawai</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $index => $unit)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="text-center text-xs text-slate-400 font-mono">{{ $units->firstItem() + $index }}</td>
                            <td>
                                <span class="font-mono font-bold text-xs px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $unit->kode_unit }}
                                </span>
                            </td>
                            <td>
                                <div class="font-bold text-slate-800 text-sm">{{ $unit->nama_unit }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">Dibuat: {{ $unit->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="max-w-xs text-xs text-slate-600 leading-relaxed">
                                {{ $unit->deskripsi ?? '-' }}
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 font-bold text-xs text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    {{ $unit->users_count }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($unit->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                        Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="row-actions justify-end">
                                    <!-- Toggle Status Button -->
                                    <form action="{{ route('admin.units.toggle-status', $unit) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit" 
                                            class="action-btn text-xs {{ $unit->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
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

                                    <!-- Delete Button (Only if 0 users) -->
                                    @if($unit->users_count === 0)
                                        <form action="{{ route('admin.units.destroy', $unit) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus unit kerja ini?')">
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
                            <td colspan="7" class="empty-search">
                                Tidak ada data unit kerja yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($units->hasPages())
            <div class="table-foot">
                <div>Menampilkan {{ $units->firstItem() }} - {{ $units->lastItem() }} dari {{ $units->total() }} unit</div>
                {{ $units->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
