<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Primary SEO Metadata -->
    <title>@yield('title', 'Masuk Portal') — {{ config('app.name', 'SIPERAPAT') }} LLDIKTI Wilayah X</title>
    <meta name="title" content="@yield('title', 'Masuk Portal') — {{ config('app.name', 'SIPERAPAT') }} LLDIKTI Wilayah X">
    <meta name="description" content="@yield('meta_description', 'SIPERAPAT - Sistem Informasi Pencatatan Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X Kemendiktisaintek.')">
    <meta name="keywords" content="@yield('meta_keywords', 'siperapat, presensi rapat, presensi selfie, tanda tangan digital, lldikti wilayah x, kemdiktisaintek, rapat kedinasan, daftar hadir digital, berita acara rapat')">
    <meta name="author" content="Lembaga Layanan Pendidikan Tinggi Wilayah X">
    <meta name="publisher" content="LLDIKTI Wilayah X Kemendiktisaintek">
    <meta name="robots" content="@yield('meta_robots', 'index, follow, max-snippet:-1, max-image-preview:large')">
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

    <!-- Open Graph Protocol (Facebook, LinkedIn, WhatsApp, Telegram) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SIPERAPAT LLDIKTI Wilayah X">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Masuk Portal') — SIPERAPAT LLDIKTI Wilayah X">
    <meta property="og:description" content="@yield('meta_description', 'Sistem Informasi Presensi Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi Wilayah X.')">
    <meta property="og:image" content="{{ asset('favicon.svg') }}">
    <meta property="og:image:alt" content="Logo SIPERAPAT LLDIKTI Wilayah X">

    <!-- Twitter Card Protocol -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'Masuk Portal') — SIPERAPAT LLDIKTI Wilayah X">
    <meta name="twitter:description" content="@yield('meta_description', 'Sistem Informasi Presensi Kehadiran Rapat Kedinasan Terintegrasi Lembaga Layanan Pendidikan Tinggi Wilayah X.')">
    <meta name="twitter:image" content="{{ asset('favicon.svg') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231d4ed8'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M22 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E">

    <!-- Schema.org JSON-LD Structured Data for Enterprise Gov-Tech -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "SIPERAPAT",
        "alternateName": "Sistem Informasi Presensi Rapat LLDIKTI",
        "url": "{{ url('/') }}",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "All",
        "description": "Sistem Pencatatan Kehadiran Rapat Kedinasan Berbasis Web dengan Verifikasi Wajah dan Tanda Tangan Digital.",
        "provider": {
            "@type": "GovernmentOrganization",
            "name": "Lembaga Layanan Pendidikan Tinggi Wilayah X",
            "alternateName": "LLDIKTI Wilayah X",
            "url": "https://lldikti10.kemdiktisaintek.go.id",
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "Padang",
                "addressRegion": "Sumatera Barat",
                "addressCountry": "ID"
            }
        }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body antialiased bg-slate-50 text-slate-800 selection:bg-blue-600 selection:text-white">
    @yield('content')

    <!-- Flash Session Data for Modal System -->
    @if (session('success'))
        <div id="flash-modal-data" data-type="success" data-title="Aksi Berhasil" data-message="{{ session('success') }}" class="hidden"></div>
    @elseif (session('error'))
        <div id="flash-modal-data" data-type="error" data-title="Kendala Sistem" data-message="{{ session('error') }}" class="hidden"></div>
    @elseif (session('warning'))
        <div id="flash-modal-data" data-type="warning" data-title="Pemberitahuan" data-message="{{ session('warning') }}" class="hidden"></div>
    @elseif ($errors->any())
        <div id="flash-modal-data" data-type="warning" data-title="Kondisi Belum Terpenuhi" data-message="{!! implode('<br>&bull; ', $errors->all()) !!}" class="hidden"></div>
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

    {{-- Toast Notification --}}
    <div class="toast" id="app-toast" role="status" aria-live="polite"></div>
</body>
</html>
