@extends('layouts.app')

@section('title', 'Pengaturan Profil Akun')
@section('heading', 'Pengaturan Profil Akun')
@section('subtitle', 'Kelola informasi identitas diri, kontak, dan keamanan kata sandi akun Anda')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <!-- Left / Main Form Area (2 cols) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Card 1: Personal Profile Information -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-100 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Informasi Data Diri</h2>
                        <p class="text-xs text-slate-500">Perbarui nama lengkap, email kedinasan, dan nomor kontak aktif</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="p-5 sm:p-6 space-y-5">
                @csrf
                @method('PUT')

                <!-- Nama Lengkap -->
                <div class="field">
                    <label for="name" class="text-xs font-semibold text-slate-700 block mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $user->name) }}" 
                        required 
                        class="input w-full text-xs @error('name') border-rose-500 @enderror"
                        placeholder="Masukkan nama lengkap beserta gelar..."
                    >
                    @error('name')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username & NIP (Read-Only Info) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="username" class="text-xs font-semibold text-slate-500 block mb-1.5">
                            Nama Pengguna (Username)
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="username" 
                                value="{{ $user->username }}" 
                                disabled 
                                class="input w-full text-xs bg-slate-100 text-slate-500 cursor-not-allowed font-mono pr-8"
                            >
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Username ditetapkan oleh Administrator sistem.</p>
                    </div>

                    <div class="field">
                        <label for="nip" class="text-xs font-semibold text-slate-500 block mb-1.5">
                            Nomor Induk Pegawai (NIP)
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="nip" 
                                value="{{ $user->nip ?? 'Tidak tercatat' }}" 
                                disabled 
                                class="input w-full text-xs bg-slate-100 text-slate-500 cursor-not-allowed font-mono pr-8"
                            >
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Perubahan NIP hanya dapat dilakukan melalui Administrator.</p>
                    </div>
                </div>

                <!-- Email & Nomor Telepon -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="email" class="text-xs font-semibold text-slate-700 block mb-1.5">
                            Alamat Email <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email', $user->email) }}" 
                            required 
                            class="input w-full text-xs @error('email') border-rose-500 @enderror"
                            placeholder="nama@lldikti.kemdikbud.go.id"
                        >
                        @error('email')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="phone" class="text-xs font-semibold text-slate-700 block mb-1.5">
                            Nomor Telepon / WhatsApp
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="{{ old('phone', $user->phone) }}" 
                            class="input w-full text-xs font-mono @error('phone') border-rose-500 @enderror"
                            placeholder="081234567890"
                        >
                        @error('phone')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Unit Kerja (Read-Only) -->
                <div class="field">
                    <label for="unit_name" class="text-xs font-semibold text-slate-500 block mb-1.5">
                        Unit Kerja Organisasi
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="unit_name" 
                            value="{{ $user->unit ? ($user->unit->kode_unit . ' — ' . $user->unit->nama_unit) : 'Tingkat Lembaga (Tanpa Unit Kerja Terikat)' }}" 
                            disabled 
                            class="input w-full text-xs bg-slate-100 text-slate-500 cursor-not-allowed pr-8"
                        >
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="button small flex items-center gap-2 text-xs">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Simpan Perubahan Data Diri</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Password Security Update -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-100 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Perbarui Kata Sandi Akun</h2>
                        <p class="text-xs text-slate-500">Pastikan menggunakan kombinasi kata sandi yang kuat dan aman</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="p-5 sm:p-6 space-y-5">
                @csrf
                @method('PUT')
                <!-- Hidden inputs to retain current name and email if only changing password -->
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <input type="hidden" name="phone" value="{{ $user->phone }}">

                <!-- Kata Sandi Saat Ini -->
                <div class="field">
                    <label for="current_password" class="text-xs font-semibold text-slate-700 block mb-1.5">
                        Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required 
                            class="input w-full text-xs pr-10 @error('current_password') border-rose-500 @enderror"
                            placeholder="Masukkan kata sandi yang sedang digunakan..."
                        >
                        <button 
                            type="button" 
                            data-toggle-password="current_password" 
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                            aria-label="Tampilkan kata sandi"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kata Sandi Baru & Konfirmasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="field">
                        <label for="password" class="text-xs font-semibold text-slate-700 block mb-1.5">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                class="input w-full text-xs pr-10 @error('password') border-rose-500 @enderror"
                                placeholder="Minimal 8 karakter kombinasi..."
                            >
                            <button 
                                type="button" 
                                data-toggle-password="password" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                aria-label="Tampilkan kata sandi"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation" class="text-xs font-semibold text-slate-700 block mb-1.5">
                            Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                required 
                                class="input w-full text-xs pr-10"
                                placeholder="Ulangi kata sandi baru..."
                            >
                            <button 
                                type="button" 
                                data-toggle-password="password_confirmation" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                aria-label="Tampilkan kata sandi"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="button small flex items-center gap-2 text-xs bg-amber-600 hover:bg-amber-700 text-white">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>Perbarui Kata Sandi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Account Summary & Security Card (1 col) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Profile Summary Panel -->
        <div class="panel p-6 text-center space-y-4">
            <div class="avatar bg-blue-700 text-white font-bold text-lg uppercase w-16 h-16 rounded-2xl mx-auto flex items-center justify-center shadow-md">
                {{ substr($user->name, 0, 2) }}
            </div>

            <div>
                <h3 class="text-sm font-bold text-slate-900">{{ $user->name }}</h3>
                <p class="text-xs text-slate-500 font-mono">{{ $user->email }}</p>
            </div>

            <div class="pt-3 border-t border-slate-100 space-y-2.5 text-xs text-left">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Hak Akses:</span>
                    @if($user->isAdministrator())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Administrator
                        </span>
                    @elseif($user->isAdmin())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Admin Unit
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Pegawai Unit
                        </span>
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Status Akun:</span>
                    <span class="status completed font-bold">Aktif</span>
                </div>

                @if($user->nip)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Nomor Induk:</span>
                        <span class="font-mono font-semibold text-slate-800">{{ $user->nip }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Terdaftar Sejak:</span>
                    <span class="text-slate-700 font-medium">{{ $user->created_at->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Security Guide Panel -->
        <div class="panel p-5 space-y-3">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-xs">
                <svg class="text-blue-600" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Ketentuan Keamanan Akun</span>
            </div>
            <ul class="text-xs text-slate-500 space-y-2 list-disc list-inside leading-relaxed">
                <li>Gunakan kata sandi unik yang tidak digunakan pada layanan lain.</li>
                <li>Kata sandi minimal terdiri dari 8 karakter dengan kombinasi huruf dan angka.</li>
                <li>Setiap pembaruan profil atau kata sandi akan otomatis tercatat pada log audit sistem.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
