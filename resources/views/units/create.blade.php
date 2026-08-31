@extends('layouts.app')

@section('title', 'Tambah Unit Kerja')
@section('heading', 'Tambah Unit Kerja Baru')
@section('subtitle', 'Daftarkan struktur organisasi / Pokja baru di lingkungan LLDIKTI')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.units.store') }}" class="space-y-5">
            @csrf

            <!-- Nama Unit -->
            <div class="field">
                <label for="nama_unit" class="text-xs font-bold text-slate-900 block mb-1">Nama Unit Kerja <span class="text-rose-600">*</span></label>
                <input 
                    type="text" 
                    id="nama_unit" 
                    name="nama_unit" 
                    value="{{ old('nama_unit') }}" 
                    class="input w-full font-medium @error('nama_unit') input-error @enderror" 
                    placeholder="Contoh: Pokja Transformasi Pendidikan Tinggi" 
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
                    value="{{ old('kode_unit') }}" 
                    class="input w-full font-mono uppercase font-bold @error('kode_unit') input-error @enderror" 
                    placeholder="Contoh: POKJA-TPT" 
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
                    placeholder="Uraian singkat peran dan tanggung jawab unit kerja..."
                >{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 accent-slate-950">
                    <span class="text-sm font-bold text-slate-900">Unit berstatus aktif</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.units.index') }}" class="button secondary text-xs font-bold">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Simpan Unit Kerja</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Informative Guidelines Card (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <h3 class="font-extrabold text-sm text-white mb-2 flex items-center gap-2">
                <svg class="text-slate-300" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Standar Penamaan Unit</span>
            </h3>
            <ul class="text-xs text-slate-200 space-y-2 leading-relaxed font-medium">
                <li>&bull; <strong class="text-white">Nama Unit:</strong> Gunakan nomenklatur resmi Pokja / Sub-bagian (misal: <em>Pokja Kelembagaan & Kerjasama</em>).</li>
                <li>&bull; <strong class="text-white">Kode Unit:</strong> Format ringkas 3-10 huruf kapital alfanumerik (misal: <code>POKJA-KLB</code>, <code>AKADEMIK</code>, <code>KEUANGAN</code>).</li>
                <li>&bull; <strong class="text-white">Status Aktif:</strong> Unit aktif akan otomatis muncul pada target scoping rapat dan pilihan registrasi pegawai.</li>
            </ul>
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Integrasi Scoping Presensi</h4>
            <p class="leading-relaxed font-medium">Setiap agenda rapat kedinasan dapat dikhususkan hanya untuk unit tertentu atau mencakup seluruh unit LLDIKTI Wilayah X.</p>
        </div>
    </div>
</div>
@endsection
