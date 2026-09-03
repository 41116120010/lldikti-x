@extends('layouts.guest')

@section('title', 'Masuk ke Sistem Presensi Rapat')

@section('content')
<div class="auth-split">
    <!-- Left Hero / Institutional Brand Identity Section (Gov-Tech Standard) -->
    <div class="auth-illustration">
        <!-- Top Institutional Brand Header + Value Proposition -->
        <div class="space-y-6 max-w-md">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri Handayani" class="w-14 h-14 object-contain shrink-0">
                <div>
                    <h1 class="text-4xl font-bold text-white tracking-wide">SIPERAPAT</h1>
                    <p class="text-xs text-blue-200/90 font-medium">LLDIKTI Wilayah X Kemendiktisaintek</p>
                </div>
            </div>

            <!-- Center Value Proposition & Institutional Service Pillars -->
            <div class="space-y-2">
                <h2 class="text-xl sm:text-2xl font-semibold text-white tracking-tight leading-snug">
                    Pencatatan Kehadiran & Notulensi Rapat Terintegrasi
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed">
                    Sistem digitalisasi presensi berbasis verifikasi wajah dan tanda tangan digital untuk seluruh unit kerja di lingkungan LLDIKTI Wilayah X.
                </p>
            </div>

            <!-- Key Gov-Tech Service Capabilities -->
            <div class="space-y-4 pt-4">
                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Autentikasi Multi-Identifier</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Masuk cepat dan aman menggunakan NIP 18 Digit atau Username resmi terdaftar.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Presensi Sah & Terverifikasi</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Perekaman foto selfie wajah dan tanda tangan digital dengan stempel waktu jaringan.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Rekapitulasi Berita Acara Otomatis</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Ekspor daftar hadir dan notulensi rapat ke dalam format standar resmi (PDF & Word).</div>
                    </div>
                </div>
            </div>

    </div>
    </div>

    <!-- Right Login Form Section -->
    <div class="auth-form-panel">
        <div class="auth-brand md:hidden">
            <img src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri Handayani" class="w-9 h-9 object-contain shrink-0">
            <div>
                <div class="brand-name">SIPERAPAT</div>
                <small>LLDIKTI Wilayah X</small>
            </div>
        </div>

        <div>
            <h2 class="auth-title">Masuk ke Akun Anda</h2>
            <p class="auth-subtitle">Gunakan Nomor Induk Pegawai (NIP 18 Digit) atau Username yang telah terdaftar di sistem.</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="auth-alert auth-alert-success flex items-center gap-2 mb-4">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="auth-alert bg-blue-50 border-blue-200 text-blue-800 flex items-center gap-2 mb-4">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert flex items-start gap-2 mb-4">
                <svg class="mt-0.5 shrink-0" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div class="flex-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            <!-- NIP or Username Input -->
            <div class="field">
                <label for="login-input">NIP atau Username</label>
                <input
                    id="login-input"
                    class="input @error('login') input-error @enderror"
                    type="text"
                    name="login"
                    value="{{ old('login') }}"
                    placeholder="Contoh: 198501152000031001 atau superadmin"
                    required
                    autocomplete="username"
                    autofocus
                >
            </div>

            <!-- Password Input -->
            <div class="field">
                <div class="auth-field-row">
                    <label for="password-input">Kata Sandi</label>
                    <a href="#" class="auth-link text-xs" onclick="window.showModal({ title: 'Bantuan Kata Sandi', message: 'Fitur pemulihan kata sandi dapat dikonfigurasi melalui Administrator Unit Kepegawaian LLDIKTI Wilayah X atau sistem email kedinasan.', type: 'info' }); return false;">Lupa Kata Sandi?</a>
                </div>
                <div class="auth-password-wrap">
                    <input
                        id="password-input"
                        class="input @error('password') input-error @enderror"
                        type="password"
                        name="password"
                        placeholder="••••••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="auth-password-toggle" data-toggle-password="password-input" aria-label="Tampilkan kata sandi" title="Tampilkan/Sembunyikan">
                        <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="auth-row">
                <label class="auth-remember cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat sesi masuk saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button class="button auth-submit flex items-center justify-center gap-2" type="submit" data-loading-label="Memverifikasi Kredensial...">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                    <polyline points="10 17 15 12 10 7"/>
                    <line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                <span>Masuk ke Sistem</span>
            </button>
        </form>

        <div class="auth-footer">
            &copy; {{ date('Y') }} SIPERAPAT - Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X.
        </div>
    </div>
</div>
@endsection
