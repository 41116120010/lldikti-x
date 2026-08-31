@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('heading', 'Tambah Pengguna Baru')
@section('subtitle', 'Daftarkan akun pegawai atau administrator baru ke dalam sistem')

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Lengkap -->
                <div class="field md:col-span-2">
                    <label for="name" class="text-xs font-bold text-slate-900 block mb-1">Nama Lengkap & Gelar <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        class="input w-full font-medium @error('name') input-error @enderror" 
                        placeholder="Contoh: Dr. Ir. Ahmad Syahid, M.Kom." 
                        required 
                        autofocus
                    >
                    @error('name')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- NIP -->
                <div class="field">
                    <label for="nip" class="text-xs font-bold text-slate-900 block mb-1">Nomor Induk Pegawai (NIP) <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="nip" 
                        name="nip" 
                        value="{{ old('nip') }}" 
                        class="input w-full font-mono font-bold @error('nip') input-error @enderror" 
                        placeholder="Contoh: 199402142020121004" 
                        required
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">18 digit angka NIP resmi instansi.</p>
                    @error('nip')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username -->
                <div class="field">
                    <label for="username" class="text-xs font-bold text-slate-900 block mb-1">Username <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="{{ old('username') }}" 
                        class="input w-full font-mono font-bold @error('username') input-error @enderror" 
                        placeholder="Contoh: ahmad_syahid" 
                        required
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Alfanumerik unik, digunakan untuk login alternatif.</p>
                    @error('username')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="field">
                    <label for="email" class="text-xs font-bold text-slate-900 block mb-1">Alamat Email Resmi <span class="text-rose-600">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        class="input w-full font-medium @error('email') input-error @enderror" 
                        placeholder="nama@lldikti.kemdikbud.go.id" 
                        required
                    >
                    @error('email')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Telepon / WhatsApp -->
                <div class="field">
                    <label for="phone" class="text-xs font-bold text-slate-900 block mb-1">Nomor Telepon / WhatsApp</label>
                    <input 
                        type="text" 
                        id="phone" 
                        name="phone" 
                        value="{{ old('phone') }}" 
                        class="input w-full font-mono font-medium @error('phone') input-error @enderror" 
                        placeholder="081234567890"
                    >
                    @error('phone')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Peran (Role) -->
                <div class="field">
                    <label for="role" class="text-xs font-bold text-slate-900 block mb-1">Peran Pengguna (Role) <span class="text-rose-600">*</span></label>
                    <select id="role" name="role" class="input w-full font-semibold @error('role') input-error @enderror" required>
                        @if($currentUser->isAdministrator())
                            <option value="administrator" {{ old('role') === 'administrator' ? 'selected' : '' }}>Administrator (Akses Penuh)</option>
                        @endif
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin Unit (Pengelola Unit)</option>
                        <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff Pegawai (Presensi Saja)</option>
                    </select>
                    @error('role')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit Kerja -->
                <div class="field">
                    <label for="unit_id" class="text-xs font-bold text-slate-900 block mb-1">Unit Kerja Organisasi</label>
                    @if($currentUser->isAdministrator())
                        <select id="unit_id" name="unit_id" class="input w-full font-semibold @error('unit_id') input-error @enderror">
                            <option value="">-- Tingkat Lembaga (Tanpa Unit) --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->kode_unit }} — {{ $unit->nama_unit }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="input w-full bg-slate-100 text-slate-900 font-bold" value="{{ $currentUser->unit?->nama_unit }}" readonly disabled>
                        <input type="hidden" name="unit_id" value="{{ $currentUser->unit_id }}">
                    @endif
                    @error('unit_id')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="field md:col-span-2">
                    <label for="password" class="text-xs font-bold text-slate-900 block mb-1">Kata Sandi Awal <span class="text-rose-600">*</span></label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input w-full @error('password') input-error @enderror" 
                        placeholder="Minimal 8 karakter (kombinasi huruf & angka)" 
                        required
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Minimal 8 karakter, wajib menyertakan huruf dan angka.</p>
                    @error('password')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 accent-slate-950">
                    <span class="text-sm font-bold text-slate-900">Akun pengguna langsung aktif</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.users.index') }}" class="button secondary text-xs font-bold">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Daftarkan Pengguna</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Role Information & Guidance Card (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <h3 class="font-extrabold text-sm text-white mb-3 flex items-center gap-2">
                <svg class="text-slate-300" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Tingkatan Hak Akses (Role)</span>
            </h3>
            <div class="space-y-3 text-xs text-slate-200 leading-relaxed font-medium">
                <div>
                    <strong class="text-white block font-bold">1. Administrator:</strong>
                    Akses penuh ke master data unit, audit trail, rekapitulasi, dan agenda tingkat lembaga.
                </div>
                <div>
                    <strong class="text-white block font-bold">2. Admin Unit:</strong>
                    Mengelola agenda dan pegawai yang berada di dalam unit kerjanya saja.
                </div>
                <div>
                    <strong class="text-white block font-bold">3. Staff Pegawai:</strong>
                    Mengakses portal presensi, riwayat kehadiran pribadi, dan melihat agenda terkait.
                </div>
            </div>
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Multi-Identifier Login</h4>
            <p class="leading-relaxed font-medium">Pegawai dapat masuk ke portal menggunakan <strong>18 digit NIP</strong> atau <strong>Username</strong> dengan kata sandi yang telah didaftarkan.</p>
        </div>
    </div>
</div>
@endsection
