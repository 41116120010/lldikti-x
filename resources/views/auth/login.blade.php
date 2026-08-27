@extends('layouts.guest')

@section('title', 'Masuk ke Sistem Presensi Rapat')

@section('content')
<div class="auth-split">
    <!-- Left Hero / Brand Identity Section -->
    <div class="auth-illustration">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center font-bold text-white tracking-wider">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-white tracking-wide">SIPERAPAT</h1>
                <p class="text-xs text-blue-200">LLDIKTI Wilayah X</p>
            </div>
        </div>

        <!-- 3D Interactive Hero Scene -->
        <div class="scene-3d my-auto" id="hero-3d-scene">
            <!-- Orbital Gyroscope Background Rings -->
            <div class="orbit-ring-1"></div>
            <div class="orbit-ring-2"></div>

            <div class="card-3d-wrap" id="hero-3d-card">
                <!-- Floating Node 1 (Top Left) -->
                <div class="floating-node-1 bg-slate-900/90 backdrop-blur-md px-3 py-2 rounded-xl border border-blue-500/40 shadow-xl flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-blue-600/30 text-blue-400 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Verifikasi</div>
                        <div class="text-[11px] font-bold text-white">Live WebRTC</div>
                    </div>
                </div>

                <!-- Floating Node 2 (Bottom Right) -->
                <div class="floating-node-2 bg-slate-900/90 backdrop-blur-md px-3 py-2 rounded-xl border border-emerald-500/40 shadow-xl flex items-center gap-2">
                    <div class="w-6 h-6 rounded-lg bg-emerald-600/30 text-emerald-400 flex items-center justify-center">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </div>
                    <div>
                        <div class="text-[9px] uppercase tracking-wider font-semibold text-slate-400">Keabsahan</div>
                        <div class="text-[11px] font-bold text-emerald-300">TTD Digital</div>
                    </div>
                </div>

                <!-- Main Holographic Card Body -->
                <div class="card-3d-main text-white">
                    <div class="flex items-center justify-between border-b border-slate-700/80 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Presensi Kedinasan</span>
                        </div>
                        <span class="text-[10px] font-mono bg-blue-900/40 text-blue-300 px-2 py-0.5 rounded border border-blue-700/50">TERENKRIPSI</span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="text-[11px] text-slate-400 font-mono">AUTENTIKASI MULTI-IDENTIFIER</div>
                        <h3 class="text-base font-bold text-white tracking-tight">SIPERAPAT LLDIKTI</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Pencatatan kehadiran rapat kedinasan dengan verifikasi kamera selfie wajah dan tanda tangan digital terenkripsi.
                        </p>
                    </div>

                    <!-- Security Grid Graphic Indicator -->
                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <div>
                                <div class="font-bold text-white text-[11px]">NIP & Username</div>
                                <div class="text-[10px] text-slate-400 font-mono">18-Digit Smart Guard</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-1 h-3 bg-blue-500 rounded-full"></span>
                            <span class="w-1 h-5 bg-blue-400 rounded-full"></span>
                            <span class="w-1 h-2 bg-blue-600 rounded-full"></span>
                            <span class="w-1 h-4 bg-emerald-400 rounded-full"></span>
                            <span class="w-1 h-3 bg-emerald-500 rounded-full"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-copy">
            <div class="auth-stats">
                <div class="auth-stat">
                    <b>100%</b>
                    <span>Paperless & Akurat</span>
                </div>
                <div class="auth-stat">
                    <b>Multi-ID</b>
                    <span>NIP atau Username</span>
                </div>
                <div class="auth-stat">
                    <b>Realtime</b>
                    <span>Rekap & Laporan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Login Form Section -->
    <div class="auth-form-panel">
        <div class="auth-brand md:hidden">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
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
            &copy; {{ date('Y') }} LLDIKTI — Sistem Kehadiran Rapat Kedinasan Terintegrasi.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const scene = document.getElementById('hero-3d-scene');
    const card = document.getElementById('hero-3d-card');
    if (scene && card && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        scene.addEventListener('mousemove', (e) => {
            const rect = scene.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.animation = 'none';
            card.style.transform = `rotateX(${-y * 20}deg) rotateY(${x * 20}deg) translateY(-4px)`;
        });

        scene.addEventListener('mouseleave', () => {
            card.style.animation = '';
            card.style.transform = '';
        });
    }
});
</script>
@endsection