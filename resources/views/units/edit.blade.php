@extends('layouts.app')

@section('title', 'Edit Unit Kerja')
@section('heading', 'Edit Unit Kerja')
@section('subtitle', 'Perbarui data organisasi untuk ' . $unit->nama_unit)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.units.update', $unit) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Nama Unit -->
            <div class="field">
                <label for="nama_unit" class="text-xs font-bold text-slate-900 block mb-1">Nama Unit Kerja <span class="text-rose-600">*</span></label>
                <input 
                    type="text" 
                    id="nama_unit" 
                    name="nama_unit" 
                    value="{{ old('nama_unit', $unit->nama_unit) }}" 
                    class="input w-full font-medium @error('nama_unit') input-error @enderror" 
                    required 
                    autofocus
                >
                @error('nama_unit')
                    <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kode Unit -->
            <div class="field">
                <label for="kode_unit" class="text-xs font-bold text-slate-900 block mb-1">Kode Singkatan Unit <span class="text-rose-600">*</span></label>
                <input 
                    type="text" 
                    id="kode_unit" 
                    name="kode_unit" 
                    value="{{ old('kode_unit', $unit->kode_unit) }}" 
                    class="input w-full font-mono uppercase font-bold @error('kode_unit') input-error @enderror" 
                    required
                >
                <p class="text-[11px] text-slate-600 font-medium mt-1">Harus unik, huruf kapital alfanumerik atau tanda hubung.</p>
                @error('kode_unit')
                    <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Deskripsi Unit -->
            <div class="field">
                <label for="deskripsi" class="text-xs font-bold text-slate-900 block mb-1">Deskripsi Tugas & Fungsi</label>
                <textarea 
                    id="deskripsi" 
                    name="deskripsi" 
                    rows="4" 
                    class="input w-full p-3 font-medium @error('deskripsi') input-error @enderror"
                >{{ old('deskripsi', $unit->deskripsi) }}</textarea>
                @error('deskripsi')
                    <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $unit->is_active) ? 'checked' : '' }} class="w-4 h-4 accent-slate-950">
                    <span class="text-sm font-bold text-slate-900">Unit berstatus aktif</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.units.index') }}" class="button secondary text-xs font-bold">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Perbarui Unit Kerja</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Metadata & Statistics Card (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <h3 class="font-extrabold text-sm text-white mb-2 flex items-center gap-2">
                <svg class="text-slate-300" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Ringkasan Unit Terdaftar</span>
            </h3>
            <div class="space-y-2 text-xs text-slate-200 font-medium">
                <div>Total Pegawai Terhubung: <strong class="text-white">{{ $unitUsers->total() }} orang</strong></div>
                <div>Agenda Rapat Terlibat: <strong class="text-white">{{ $unit->agendas_count ?? $unit->agendas()->count() }} agenda</strong></div>
                <div>Status Saat Ini: <strong class="{{ $unit->is_active ? 'text-emerald-400 font-bold' : 'text-amber-400 font-bold' }}">{{ $unit->is_active ? 'Aktif Beroperasi' : 'Non-Aktif' }}</strong></div>
                <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-400">Dibuat pada: {{ $unit->created_at->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <!-- Paginated List of Employees in Unit -->
        <div class="panel flex flex-col justify-between overflow-hidden">
            <div>
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <h4 class="font-bold text-slate-900 text-xs">Pegawai di Unit Ini ({{ $unitUsers->total() }})</h4>
                    </div>
                </div>

                <div class="p-4 divide-y divide-slate-200 text-xs">
                    @forelse($unitUsers as $userItem)
                        <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-950 truncate">{{ $userItem->name }}</div>
                                <div class="text-[10px] text-slate-600 font-mono font-medium">NIP: {{ $userItem->nip }}</div>
                            </div>
                            <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-slate-100 font-bold text-slate-800 border border-slate-300 shrink-0">
                                {{ $userItem->role }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-slate-500 font-medium">
                            Belum ada pegawai terhubung pada unit ini.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($unitUsers->hasPages())
                <div class="mt-auto">
                    {{ $unitUsers->links('vendor.pagination.compact') }}
                </div>
            @endif
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Perubahan Kode Unit</h4>
            <p class="leading-relaxed font-medium">Perubahan kode unit akan otomatis diperbarui pada seluruh riwayat agenda dan laporan berita acara rapat terkait.</p>
        </div>
    </div>
</div>
@endsection
