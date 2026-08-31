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
                <label for="nama_unit">Nama Unit Kerja <span class="text-rose-500">*</span></label>
                <input 
                    type="text" 
                    id="nama_unit" 
                    name="nama_unit" 
                    value="{{ old('nama_unit', $unit->nama_unit) }}" 
                    class="input w-full @error('nama_unit') input-error @enderror" 
                    required 
                    autofocus
                >
                @error('nama_unit')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kode Unit -->
            <div class="field">
                <label for="kode_unit">Kode Singkatan Unit <span class="text-rose-500">*</span></label>
                <input 
                    type="text" 
                    id="kode_unit" 
                    name="kode_unit" 
                    value="{{ old('kode_unit', $unit->kode_unit) }}" 
                    class="input w-full font-mono uppercase @error('kode_unit') input-error @enderror" 
                    required
                >
                <p class="text-[11px] text-slate-400 mt-1">Harus unik, huruf kapital alfanumerik atau tanda hubung.</p>
                @error('kode_unit')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Deskripsi Unit -->
            <div class="field">
                <label for="deskripsi">Deskripsi Tugas & Fungsi</label>
                <textarea 
                    id="deskripsi" 
                    name="deskripsi" 
                    rows="4" 
                    class="input w-full p-3 @error('deskripsi') input-error @enderror"
                >{{ old('deskripsi', $unit->deskripsi) }}</textarea>
                @error('deskripsi')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $unit->is_active) ? 'checked' : '' }} class="w-4 h-4 accent-blue-600">
                    <span class="text-sm font-medium text-slate-700">Unit berstatus aktif</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.units.index') }}" class="button secondary text-xs">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Perbarui Unit Kerja</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Metadata & Statistics Card (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-900 text-white border-slate-800">
            <h3 class="font-bold text-sm text-white mb-2 flex items-center gap-2">
                <svg class="text-blue-400" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Ringkasan Unit Terdaftar</span>
            </h3>
            <div class="space-y-2 text-xs text-slate-300">
                <div>Total Pegawai Terhubung: <strong class="text-white">{{ $unit->users_count ?? $unit->users()->count() }} orang</strong></div>
                <div>Agenda Rapat Terlibat: <strong class="text-white">{{ $unit->agendas_count ?? $unit->agendas()->count() }} agenda</strong></div>
                <div>Status Saat Ini: <strong class="{{ $unit->is_active ? 'text-emerald-400' : 'text-amber-400' }}">{{ $unit->is_active ? 'Aktif Beroperasi' : 'Non-Aktif' }}</strong></div>
                <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-400">Dibuat pada: {{ $unit->created_at->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <div class="panel p-5 text-xs text-slate-600 bg-white border-slate-200">
            <h4 class="font-bold text-slate-800 mb-1">Perubahan Kode Unit</h4>
            <p class="leading-relaxed">Perubahan kode unit akan otomatis diperbarui pada seluruh riwayat agenda dan laporan berita acara rapat terkait.</p>
        </div>
    </div>
</div>
@endsection
