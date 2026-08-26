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

        <div class="auth-graphic my-auto py-8">
            <div class="w-full max-w-sm bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/15 shadow-2xl text-white">
                <div class="flex items-center justify-between border-b border-white/15 pb-4 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Sistem Presensi Aktif</span>
                    </div>
                    <span class="text-[11px] font-mono bg-white/15 px-2 py-0.5 rounded text-blue-100">v1.0 LTS</span>
                </div>
                
                <h3 class="text-base font-bold mb-2">Presensi Digital Kedinasan</h3>
                <p class="text-xs text-blue-100/85 leading-relaxed mb-4">
                    Pencatatan kehadiran rapat terintegrasi dengan verifikasi kamera selfie wajah dan tanda tangan digital terenkripsi.
                </p>

                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-white/10 text-xs">
                    <div class="bg-black/20 rounded-lg p-2.5">
                        <div class="text-blue-200 text-[10px] uppercase font-semibold">Keamanan</div>
                        <div class="font-bold text-white mt-0.5">Audit Trail Log</div>
                    </div>
                    <div class="bg-black/20 rounded-lg p-2.5">
                        <div class="text-blue-200 text-[10px] uppercase font-semibold">Integritas</div>
                        <div class="font-bold text-white mt-0.5">Selfie + TTD</div>
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
                    <a href="#" class="auth-link text-xs" onclick="alert('Fitur reset password melalui email dapat dikonfigurasi pada pengaturan server SMTP kedinasan.'); return false;">Lupa Kata Sandi?</a>
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

        <!-- Quick Demo Credentials Picker -->
        <div class="mt-8 pt-6 border-t border-slate-200/80">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
                <span>Akun Uji Coba Cepat (Demo):</span>
                <span class="text-[10px] font-mono lowercase text-slate-400">Password: Password123!</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" onclick="fillDemo('superadmin', 'Password123!')" class="p-2 text-left bg-slate-100 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg transition text-xs">
                    <div class="font-bold text-slate-800 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span> Superadmin
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">superadmin</div>
                </button>

                <button type="button" onclick="fillDemo('admin_akademik', 'Password123!')" class="p-2 text-left bg-slate-100 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg transition text-xs">
                    <div class="font-bold text-slate-800 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span> Admin Unit
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">admin_akademik</div>
                </button>

                <button type="button" onclick="fillDemo('199402142020121004', 'Password123!')" class="p-2 text-left bg-slate-100 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg transition text-xs">
                    <div class="font-bold text-slate-800 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Staff (NIP)
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">1994021420...</div>
                </button>
            </div>
        </div>

        <div class="auth-footer">
            &copy; {{ date('Y') }} LLDIKTI — Sistem Kehadiran Rapat Kedinasan Terintegrasi.
        </div>
    </div>
</div>

<script>
function fillDemo(login, password) {
    const loginInput = document.getElementById('login-input');
    const passwordInput = document.getElementById('password-input');
    if (loginInput && passwordInput) {
        loginInput.value = login;
        passwordInput.value = password;
        loginInput.focus();
    }
}
</script>
@endsection