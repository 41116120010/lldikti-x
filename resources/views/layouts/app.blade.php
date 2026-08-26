<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<<<<<<< HEAD
    <title>SynCore — @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">▪▪</div>
                <div>
                    <div class="brand-name">SynCore</div>
                    <small>Attendance System</small>
                </div>
            </div>

            <div class="nav-group">
                <div class="sidebar-label">MANAGEMENT</div>
                <a class="nav-link {{ request()->routeIs('admin.units') ? 'active' : '' }}"
                   href="{{ route('admin.units') }}">
                    <span class="nav-icon">▦</span>Manage Units
                </a>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">♧</span>User Management
                </a>
                <a class="nav-link {{ request()->routeIs('admin.notulen.create') ? 'active' : '' }}"
                   href="{{ route('admin.notulen.create') }}">
                    <span class="nav-icon">▣</span>Create Meeting
                </a>
                <a class="nav-link {{ request()->routeIs('admin.meetings') ? 'active' : '' }}"
                   href="{{ route('admin.meetings') }}">
                    <span class="nav-icon">▤</span>Meeting Management
                </a>
                <a class="nav-link {{ request()->routeIs('admin.notulen') ? 'active' : '' }}"
                   href="{{ route('admin.notulen') }}">
                    <span class="nav-icon">▧</span>Meeting Report
                </a>
            </div>

            <div class="nav-group">
                <div class="sidebar-label">GENERAL</div>
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">⌂</span>My Dashboard
                </a>
                <button class="nav-link nav-button" type="button" data-toast="Settings will be available soon.">
                    <span class="nav-icon">☼</span>Settings
                </button>
            </div>

            <div class="sidebar-user">
                <div class="avatar">SC</div>
                <div>
                    <b>Sarah Chen</b>
                    <div class="user-meta">Administrator</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="logout" title="Logout" type="submit">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
=======
    <title>{{ config('app.name', 'SIPERAPAT') }} — @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans">
    <div class="shell">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <!-- Brand Logo -->
            <div class="brand">
                <div class="brand-mark bg-blue-700 text-white shadow-md">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-name tracking-tight font-bold text-slate-900">SIPERAPAT</div>
                    <small class="text-[11px] text-slate-400 font-medium">LLDIKTI Wilayah X</small>
                </div>
            </div>

            <!-- Navigation Links by Role -->
            <div class="nav-group">
                <div class="sidebar-label">MENU UTAMA</div>

                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <a class="nav-link {{ request()->routeIs('attendances.portal') ? 'active' : '' }}" href="{{ route('attendances.portal') }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </span>
                    <span>Portal Presensi</span>
                </a>

                <a class="nav-link {{ request()->routeIs('attendances.history') ? 'active' : '' }}" href="{{ route('attendances.history') }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    <span>Riwayat Kehadiran</span>
                </a>

                @if(Auth::user()->isAdministrator() || Auth::user()->isAdmin())
                    <a class="nav-link {{ request()->routeIs('admin.agendas*') ? 'active' : '' }}" href="{{ route('admin.agendas.index') }}">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        </span>
                        <span>Agenda Rapat</span>
                    </a>

                    <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </span>
                        <span>Laporan & Rekap</span>
                    </a>
                @endif
            </div>

            @if(Auth::user()->isAdministrator() || Auth::user()->isAdmin())
                <div class="nav-group">
                    <div class="sidebar-label">PENGELOLAAN</div>

                    @if(Auth::user()->isAdministrator())
                        <a class="nav-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}" href="{{ route('admin.units.index') }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                            </span>
                            <span>Kelola Unit Kerja</span>
                        </a>
                    @endif

                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                        </span>
                        <span>{{ Auth::user()->isAdministrator() ? 'Kelola Pengguna' : 'Pegawai Unit' }}</span>
                    </a>

                    @if(Auth::user()->isAdministrator())
                        <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" href="{{ route('admin.logs.index') }}">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </span>
                            <span>Log Aktivitas</span>
                        </a>
                    @endif
                </div>
            @endif

            <!-- User Session Footer -->
            <div class="sidebar-user">
                <div class="avatar bg-blue-800 text-white font-bold text-xs uppercase">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <div class="min-w-0 flex-1">
                    <b class="block truncate text-xs text-slate-800">{{ Auth::user()->name }}</b>
                    <div class="user-meta text-[11px] truncate text-slate-400">
                        @if(Auth::user()->isAdministrator())
                            <span class="text-blue-600 font-semibold">Administrator</span>
                        @elseif(Auth::user()->isAdmin())
                            <span class="text-indigo-600 font-semibold">{{ Auth::user()->unit?->kode_unit ?? 'Admin Unit' }}</span>
                        @else
                            <span class="text-emerald-600 font-semibold">{{ Auth::user()->unit?->kode_unit ?? 'Staff' }}</span>
                        @endif
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button class="logout" title="Keluar dari sistem" type="submit">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
>>>>>>> 466438b (Versi 1.0 - LLDIKTI X)
                    </button>
                </form>
            </div>
        </aside>

<<<<<<< HEAD
        <section class="content">
            <header class="topbar">
                <div>
                    <div class="page-title">@yield('heading')</div>
                    <div class="subtitle">@yield('subtitle')</div>
                </div>

                <div class="top-actions">
                    <button class="search-top" type="button" data-focus-search>
                        ⌕ &nbsp; Search...　⌘K
                    </button>
                    <button class="icon-button" type="button" data-toast="No new notifications.">♧</button>

                    <div class="profile-menu-wrap">
                        <button class="top-profile" type="button" data-profile-toggle
                                aria-haspopup="true" aria-expanded="false">
                            <div class="avatar">SC</div>
                            <div>
                                <b>Sarah Chen</b>
                                <div class="user-meta">Administrator</div>
                            </div>
                            <span class="muted">⌄</span>
                        </button>

                        <div class="profile-dropdown" data-profile-dropdown>
                            <a class="profile-dropdown-item" href="{{ route('admin.dashboard') }}">
                                My Dashboard
                            </a>
                            <button class="profile-dropdown-item" type="button"
                                    data-toast="Settings will be available soon.">
                                Settings
                            </button>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="profile-dropdown-item profile-dropdown-danger" type="submit">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page">
=======
        <!-- Main Content Area -->
        <section class="content">
            <!-- Topbar Header -->
            <header class="topbar">
                <div>
                    <div class="page-title">@yield('heading', 'Dashboard')</div>
                    <div class="subtitle text-xs">@yield('subtitle', 'Sistem Pencatatan Kehadiran Rapat Kedinasan')</div>
                </div>

                <div class="top-actions">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] font-mono text-slate-500">NIP: {{ Auth::user()->nip }}</div>
                    </div>

                    <div class="top-profile">
                        <div class="avatar bg-blue-700 text-white font-bold text-xs uppercase">
                            {{ substr(Auth::user()->name, 0, 2) }}
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="button small secondary flex items-center gap-1.5 text-xs text-rose-600 border-rose-200 hover:bg-rose-50" title="Keluar">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="page">
                {{-- Flash Notifications --}}
                @if (session('success'))
                    <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm shadow-xs">
                        <svg class="shrink-0 text-emerald-600" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl flex items-center gap-3 text-sm shadow-xs">
                        <svg class="shrink-0 text-rose-600" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

>>>>>>> 466438b (Versi 1.0 - LLDIKTI X)
                @yield('content')
            </main>
        </section>
    </div>

<<<<<<< HEAD
    <div class="modal-backdrop" id="app-modal" aria-hidden="true">
        <section class="modal" role="dialog" aria-modal="true">
            <button class="modal-close" aria-label="Close">×</button>
=======
    <!-- Global Modal Placeholder -->
    <div class="modal-backdrop" id="app-modal" aria-hidden="true">
        <section class="modal" role="dialog" aria-modal="true">
            <button class="modal-close" aria-label="Tutup">×</button>
>>>>>>> 466438b (Versi 1.0 - LLDIKTI X)
            <div id="modal-content"></div>
        </section>
    </div>

<<<<<<< HEAD
    <div class="toast" id="app-toast" role="status"></div>
</body>

</html>
=======
    <!-- Toast Notification Container -->
    <div class="toast" id="app-toast" role="status" aria-live="polite"></div>
</body>
</html>
>>>>>>> 466438b (Versi 1.0 - LLDIKTI X)
