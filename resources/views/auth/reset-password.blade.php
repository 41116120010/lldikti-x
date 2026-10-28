@extends('layouts.guest')

@section('title', 'Atur Ulang Kata Sandi — SIPERAPAT LLDIKTI Wilayah X')

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
                    Pembaharuan Kata Sandi Akun
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed">
                    Pastikan kata sandi baru Anda kuat, unik, dan tidak dibagikan kepada pihak manapun demi integritas presensi kedinasan.
                </p>
            </div>

            <!-- Key Gov-Tech Service Capabilities -->
            <div class="space-y-4 pt-4">
                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Standar Keamanan Kata Sandi</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Gunakan minimal 8 karakter dengan kombinasi huruf dan angka untuk proteksi optimal.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Pencabutan Sesi Perangkat Lain</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Seluruh sesi aktif di peramban dan aplikasi mobile akan otomatis disegarkan kembali.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Audit Trail Terintegrasi</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Setiap pergantian kata sandi tercatat dalam log aktivitas kepegawaian institusi.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Section -->
    <div class="auth-form-panel">
        <div class="auth-brand md:hidden">
            <img src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri Handayani" class="w-9 h-9 object-contain shrink-0">
            <div>
                <div class="brand-name">SIPERAPAT</div>
                <small>LLDIKTI Wilayah X</small>
            </div>
        </div>

        <div>
            <h2 class="auth-title">Atur Ulang Kata Sandi</h2>
            <p class="auth-subtitle">Masukkan alamat email kedinasan terdaftar dan tentukan kata sandi baru Anda.</p>
        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="auth-alert flex items-start gap-2 mb-4">
                <svg class="mt-0.5 shrink-0" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div class="flex-1 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf

            <!-- Hidden Token -->
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Email Input -->
            <div class="field">
                <label for="email-input">Alamat Email Kedinasan</label>
                <input
                    id="email-input"
                    class="input @error('email') input-error @enderror"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    placeholder="nama@lldiktiwilayahx.kemdiktisaintek.go.id"
                    required
                    autocomplete="email"
                >
            </div>

            <!-- New Password Input -->
            <div class="field">
                <label for="password-input">Kata Sandi Baru</label>
                <div class="auth-password-wrap">
                    <input
                        id="password-input"
                        class="input @error('password') input-error @enderror"
                        type="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        required
                        autocomplete="new-password"
                        autofocus
                    >
                    <button type="button" class="auth-password-toggle" data-toggle-password="password-input" aria-label="Tampilkan kata sandi" title="Tampilkan/Sembunyikan">
                        <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Password Confirmation Input -->
            <div class="field">
                <label for="password-confirm-input">Konfirmasi Kata Sandi Baru</label>
                <div class="auth-password-wrap">
                    <input
                        id="password-confirm-input"
                        class="input @error('password_confirmation') input-error @enderror"
                        type="password"
                        name="password_confirmation"
                        placeholder="Ulangi kata sandi baru"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="auth-password-toggle" data-toggle-password="password-confirm-input" aria-label="Tampilkan konfirmasi kata sandi" title="Tampilkan/Sembunyikan">
                        <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <!-- Password Quality Hint -->
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 space-y-1">
                <div class="font-medium text-slate-700 flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Ketentuan Kata Sandi:</span>
                </div>
                <ul class="list-disc list-inside text-slate-500 space-y-0.5 pl-1">
                    <li>Panjang minimal 8 karakter.</li>
                    <li>Disarankan memuat huruf besar, huruf kecil, dan angka.</li>
                </ul>
            </div>

            <!-- Submit Button -->
            <button class="button auth-submit flex items-center justify-center gap-2" type="submit" data-loading-label="Memperbarui Kata Sandi...">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                <span>Perbarui Kata Sandi</span>
            </button>

            <!-- Back to Login Link -->
            <div class="pt-2 text-center">
                <a href="{{ route('login') }}" class="auth-link text-xs inline-flex items-center gap-1.5 font-medium text-slate-600 hover:text-blue-700">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    <span>Kembali ke Halaman Masuk</span>
                </a>
            </div>
        </form>

        <div class="auth-footer">
            &copy; {{ date('Y') }} SIPERAPAT - Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X.
        </div>
    </div>
</div>
@endsection
