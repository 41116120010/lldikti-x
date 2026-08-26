@extends('layouts.app')

@section('title', 'Edit Unit Kerja')
@section('heading', 'Edit Unit Kerja')
@section('subtitle', 'Perbarui data organisasi untuk ' . $unit->nama_unit)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="panel p-6 sm:p-8">
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
                    rows="3" 
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
</div>
@endsection
