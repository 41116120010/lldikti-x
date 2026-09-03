<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Primary SEO Metadata -->
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'SIPERAPAT') }} LLDIKTI Wilayah X</title>
    <meta name="title" content="@yield('title', 'Dashboard') — {{ config('app.name', 'SIPERAPAT') }} LLDIKTI Wilayah X">
    <meta name="description" content="@yield('meta_description', 'SIPERAPAT - Sistem Informasi Presensi Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi Wilayah X Kemendiktisaintek.')">
    <meta name="keywords" content="@yield('meta_keywords', 'siperapat, presensi rapat, rapat kedinasan, lldikti wilayah x, berita acara rapat, absensi digital, kemdiktisaintek')">
    <meta name="author" content="Lembaga Layanan Pendidikan Tinggi Wilayah X">
    <meta name="publisher" content="LLDIKTI Wilayah X Kemendiktisaintek">
    <meta name="robots" content="@yield('meta_robots', 'noindex, nofollow, noarchive')">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Geo Location Metadata -->
    <meta name="geo.region" content="ID-SB">
    <meta name="geo.placename" content="Padang">
    <meta name="geo.position" content="-0.9242;100.3628">
    <meta name="ICBM" content="-0.9242, 100.3628">
    <meta name="language" content="Indonesian">

    <!-- Web Application & PWA Properties -->
    <meta name="application-name" content="SIPERAPAT">
    <meta name="theme-color" content="#0F172A">
    <meta name="color-scheme" content="light">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIPERAPAT">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">

    <!-- Open Graph Protocol -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SIPERAPAT LLDIKTI Wilayah X">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Dashboard') — SIPERAPAT LLDIKTI Wilayah X">
    <meta property="og:description" content="@yield('meta_description', 'Sistem Informasi Presensi Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi Wilayah X.')">
    <meta property="og:image" content="{{ asset('images/tut-wuri-handayani.png') }}">
    <meta property="og:image:alt" content="Logo SIPERAPAT LLDIKTI Wilayah X">

    <!-- Twitter Card Protocol -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'Dashboard') — SIPERAPAT LLDIKTI Wilayah X">
    <meta name="twitter:description" content="@yield('meta_description', 'Sistem Informasi Presensi Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi Wilayah X.')">
    <meta name="twitter:image" content="{{ asset('images/tut-wuri-handayani.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/tut-wuri-handayani.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/tut-wuri-handayani.png') }}">

    <!-- Schema.org JSON-LD Structured Data for Enterprise Gov-Tech -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "SIPERAPAT",
        "alternateName": "Sistem Presensi Rapat LLDIKTI",
        "url": "{{ url('/') }}",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "All",
        "description": "Sistem Pencatatan Kehadiran Rapat Kedinasan Berbasis Web dengan Verifikasi Wajah dan Tanda Tangan Digital.",
        "provider": {
            "@type": "GovernmentOrganization",
            "name": "Lembaga Layanan Pendidikan Tinggi Wilayah X",
            "alternateName": "LLDIKTI Wilayah X",
            "url": "https://lldikti10.kemdiktisaintek.go.id"
        }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans">
    <!-- Top Progress Bar for Seamless Page Transitions -->
    <div id="app-progress-bar" aria-hidden="true"></div>

    <!-- Skip to Main Content Accessibility Landmark -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-slate-950 focus:text-white focus:rounded-lg focus:shadow-xl focus:outline-none">
        Lewati ke konten utama
    </a>

    <!-- Mobile Sidebar Drawer Backdrop -->
    <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

    <div class="shell">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="app-sidebar" role="navigation" aria-label="Navigasi Utama">
            <!-- Brand Logo & Mobile Close Button -->
            <div class="brand justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri Handayani" class="w-9 h-9 object-contain shrink-0">
                    <div>
                        <div class="brand-name tracking-tight font-extrabold text-slate-950">SIPERAPAT</div>
                        <small class="text-[11px] text-slate-600 font-bold">LLDIKTI Wilayah X</small>
                    </div>
                </div>

                <button id="mobile-sidebar-close" type="button" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200" aria-label="Tutup Menu">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Navigation Links by Role -->
            <div class="nav-group">
                <div class="sidebar-label">MENU UTAMA</div>

                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" {!! request()->routeIs('dashboard') ? 'aria-current="page"' : '' !!}>
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <a class="nav-link {{ request()->routeIs('attendances.history') ? 'active' : '' }}" href="{{ route('attendances.history') }}" {!! request()->routeIs('attendances.history') ? 'aria-current="page"' : '' !!}>
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <span>Riwayat Kehadiran</span>
                </a>

                @if(Auth::user()->isAdministrator() || Auth::user()->isAdmin())
                    <a class="nav-link {{ request()->routeIs('admin.agendas*') ? 'active' : '' }}" href="{{ route('admin.agendas.index') }}" {!! request()->routeIs('admin.agendas*') ? 'aria-current="page"' : '' !!}>
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        </span>
                        <span>Agenda Rapat</span>
                    </a>

                    <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}" {!! request()->routeIs('admin.reports*') ? 'aria-current="page"' : '' !!}>
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        </span>
                        <span>Laporan & Rekap</span>
                    </a>
                @endif
            </div>

            @if(Auth::user()->isAdministrator() || Auth::user()->isAdmin())
                <div class="nav-group">
                    <div class="sidebar-label">PENGELOLAAN</div>

                    @if(Auth::user()->isAdministrator())
                        <a class="nav-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}" href="{{ route('admin.units.index') }}" {!! request()->routeIs('admin.units.*') ? 'aria-current="page"' : '' !!}>
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                            </span>
                            <span>Unit Kerja</span>
                        </a>
                    @endif

                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}" {!! request()->routeIs('admin.users.*') ? 'aria-current="page"' : '' !!}>
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                        <span>Pengguna</span>
                    </a>

                    @if(Auth::user()->isAdministrator())
                        <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" href="{{ route('admin.logs.index') }}" {!! request()->routeIs('admin.logs.*') ? 'aria-current="page"' : '' !!}>
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </span>
                            <span>Log Aktivitas</span>
                        </a>
                    @endif
                </div>
            @endif
        </aside>

        <!-- Main Content Area -->
        <section class="content">
            <!-- Topbar Header -->
            <header class="topbar" id="app-topbar" role="banner">
                <div class="flex items-center gap-3" id="topbar-heading-container">
                    <button id="mobile-sidebar-toggle" type="button" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg bg-slate-100 text-slate-900 hover:bg-slate-200 border border-slate-300 shadow-xs cursor-pointer" aria-label="Buka Menu Navigasi">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div>
                        <h1 class="page-title">@yield('heading', 'Dashboard')</h1>
                        <div class="subtitle text-xs text-slate-600 font-medium">@yield('subtitle', 'Sistem Pencatatan Kehadiran Rapat Kedinasan')</div>
                    </div>
                </div>

                <!-- User Profile Dropdown Menu Area -->
                <div class="relative" id="user-profile-dropdown-container">
                    <button
                        type="button"
                        id="user-profile-dropdown-btn"
                        class="flex items-center gap-2.5 p-1 pl-2.5 rounded-xl hover:bg-slate-100 transition border border-transparent hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-950/20 cursor-pointer select-none"
                        aria-expanded="false"
                        aria-haspopup="true"
                        aria-label="Menu Profil Pengguna"
                    >
                        <div class="text-right hidden sm:block leading-tight">
                            <div class="text-xs font-bold text-slate-900 truncate max-w-[160px]">{{ Auth::user()->name }}</div>
                            <div class="text-[11px] text-slate-600 font-bold truncate max-w-[160px]">
                                @if(Auth::user()->isAdministrator())
                                    Administrator
                                @elseif(Auth::user()->isAdmin())
                                    Admin Unit: {{ Auth::user()->unit?->kode_unit ?? '-' }}
                                @else
                                    Pegawai: {{ Auth::user()->unit?->kode_unit ?? '-' }}
                                @endif
                            </div>
                        </div>

                        <div class="avatar bg-slate-950 text-white font-bold text-xs uppercase shadow-xs" aria-hidden="true">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>

                        <svg class="text-slate-700 transition-transform duration-200" id="user-profile-chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <!-- Dropdown Menu Box -->
                    <div
                        id="user-profile-dropdown-menu"
                        class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-300 shadow-xl py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                        role="menu"
                        aria-orientation="vertical"
                        aria-labelledby="user-profile-dropdown-btn"
                    >
                        <!-- Dropdown Header Profile Info -->
                        <div class="px-4 py-3 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="avatar bg-slate-950 text-white font-bold text-sm uppercase shrink-0">
                                    {{ substr(Auth::user()->name, 0, 2) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <b class="block text-xs text-slate-950 truncate font-extrabold">{{ Auth::user()->name }}</b>
                                    <span class="block text-[11px] text-slate-600 font-medium truncate">{{ Auth::user()->email ?? ('@' . Auth::user()->username) }}</span>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-200 space-y-1.5 text-xs">
                                <!-- Peran / Role -->
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 text-[11px] font-bold">Peran Akun:</span>
                                    @if(Auth::user()->isAdministrator())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-900 border border-slate-300">
                                            Administrator
                                        </span>
                                    @elseif(Auth::user()->isAdmin())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-900 border border-slate-300">
                                            Admin Unit
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-900 border border-slate-300">
                                            Pegawai Unit
                                        </span>
                                    @endif
                                </div>

                                <!-- Unit Kerja -->
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-600 text-[11px] font-bold">Unit Kerja:</span>
                                    <span class="text-slate-900 font-bold text-right text-[11px] truncate max-w-[150px]" title="{{ Auth::user()->unit?->nama_unit ?? 'Tingkat Lembaga (Tanpa Unit)' }}">
                                        {{ Auth::user()->unit?->nama_unit ?? 'Tingkat Lembaga' }}
                                    </span>
                                </div>

                                <!-- NIP (Jika Ada) -->
                                @if(Auth::user()->nip)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-600 text-[11px] font-bold">Nomor Induk (NIP):</span>
                                        <span class="font-mono text-slate-950 text-[11px] font-bold">{{ Auth::user()->nip }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Dropdown Action Links -->
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-900 hover:bg-slate-100 transition font-bold" role="menuitem">
                                <svg class="text-slate-700" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Pengaturan Profil Akun</span>
                            </a>
                            <a href="{{ route('profile.logs') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-900 hover:bg-slate-100 transition font-bold" role="menuitem">
                                <svg class="text-slate-700" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <span>Log Aktivitas Saya</span>
                            </a>
                        </div>

                        <!-- Dropdown Logout Footer -->
                        <div class="pt-1 border-t border-slate-200">
                            <form
                                action="{{ route('logout') }}"
                                method="POST"
                                class="block"
                                data-confirm="Apakah Anda yakin ingin keluar dari sistem SIPERAPAT?"
                                data-confirm-title="Konfirmasi Keluar Akun"
                                data-confirm-type="warning"
                                data-confirm-btn="Ya, Keluar"
                            >
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-700 hover:bg-rose-50 transition text-left font-bold cursor-pointer" role="menuitem">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    <span>Keluar dari Sistem</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="page" id="main-content" role="main" tabindex="-1">
                {{-- Flash Notifications --}}
                @if (session('success'))
                    <div class="mb-5 bg-emerald-50 border border-emerald-300 text-emerald-950 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-semibold shadow-xs" role="alert">
                        <svg class="shrink-0 text-emerald-700" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 bg-rose-50 border border-rose-300 text-rose-950 px-4 py-3 rounded-xl flex items-center gap-3 text-sm font-semibold shadow-xs" role="alert">
                        <svg class="shrink-0 text-rose-700" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Content / Page Footer -->
            <footer class="footer" role="contentinfo">
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                    <span class="font-bold text-slate-800">&copy; {{ date('Y') }} SIPERAPAT - Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X.</span>
                </div>
                <div class="text-[11px] text-slate-600 font-mono font-bold">
                    <span>v2.2</span>
                </div>
            </footer>
        </section>
    </div>

    <!-- Flash Session Data for Modal System -->
    @if (session('success'))
        <div id="flash-modal-data" data-type="success" data-title="Aksi Berhasil" data-message="{{ session('success') }}" class="hidden"></div>
    @elseif (session('error'))
        <div id="flash-modal-data" data-type="error" data-title="Kendala Sistem" data-message="{{ session('error') }}" class="hidden"></div>
    @elseif (session('warning'))
        <div id="flash-modal-data" data-type="warning" data-title="Pemberitahuan" data-message="{{ session('warning') }}" class="hidden"></div>
    @elseif ($errors->any())
        <div id="flash-modal-data" data-type="warning" data-title="Kondisi Belum Terpenuhi" data-message="&bull; {{ implode('<br>&bull; ', array_map('e', $errors->all())) }}" class="hidden"></div>
    @endif

    <!-- Global Modal Structure -->
    <div class="modal-backdrop" id="app-modal" aria-hidden="true">
        <section class="modal" role="dialog" aria-modal="true">
            <button class="modal-close" aria-label="Tutup" data-close-modal>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div id="modal-content"></div>
        </section>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast" id="app-toast" role="status" aria-live="polite"></div>
</body>
</html>
