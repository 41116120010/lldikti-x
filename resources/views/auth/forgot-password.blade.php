@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi — Pemulihan Akun Pegawai')

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
                    Pemulihan Akses Kedinasan Mandiri
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed">
                    Layanan mandiri pengaturan ulang kata sandi pegawai terintegrasi dengan email resmi kedinasan LLDIKTI Wilayah X.
                </p>
            </div>

            <!-- Key Gov-Tech Service Capabilities -->
            <div class="space-y-4 pt-4">
                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Verifikasi Multi-Identifier</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Cukup masukkan NIP 18 Digit, Username, atau Email kedinasan yang terdaftar.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Pengiriman Tautan Aman</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Tautan reset dikirimkan langsung ke email resmi kedinasan pegawai dengan enkripsi TLS.</div>
                    </div>
                </div>

                <div class="pl-4 flex items-start gap-3 text-xs">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shrink-0 mt-0.5">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-xs mb-0.5">Batas Waktu Keamanan</div>
                        <div class="text-white/80 text-[11px] leading-relaxed drop-shadow-sm">Setiap tautan memiliki batas kedaluwarsa 60 menit untuk mencegah penyalahgunaan akun.</div>
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
            <h2 class="auth-title">Lupa Kata Sandi?</h2>
            <p class="auth-subtitle">Masukkan NIP (18 Digit), Username, atau Email kedinasan Anda. Sistem akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>
        </div>

        {{-- Flash Status Message --}}
        @if (session('status'))
            <div class="auth-alert auth-alert-success flex items-start gap-2 mb-4">
                <svg class="mt-0.5 shrink-0" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span class="text-xs leading-relaxed">{{ session('status') }}</span>
            </div>
        @endif

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

        <form class="auth-form" method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            <!-- Identity Input -->
            <div class="field">
                <label for="identity-input">NIP, Username, atau Email Kedinasan</label>
                <input
                    id="identity-input"
                    class="input @error('identity') input-error @enderror"
                    type="text"
                    name="identity"
                    value="{{ old('identity') }}"
                    placeholder="Contoh: 19701205... / afdalisma / nama@..."
                    required
                    autofocus
                >
            </div>

            <!-- Submit Button -->
            <button class="button auth-submit flex items-center justify-center gap-2" type="submit" data-loading-label="Mengirimkan Tautan...">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                <span>Kirim Tautan Atur Ulang</span>
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
