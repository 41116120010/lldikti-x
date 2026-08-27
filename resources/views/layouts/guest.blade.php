<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, noimageindex">
    <meta name="description" content="SIPERAPAT - Sistem Pencatatan Kehadiran Rapat Kedinasan LLDIKTI Wilayah X.">
    <meta name="author" content="Lembaga Layanan Pendidikan Tinggi Wilayah X">
    <meta name="application-name" content="SIPERAPAT">
    <meta name="theme-color" content="#0F172A">
    <meta name="color-scheme" content="light">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIPERAPAT">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">

    <!-- Open Graph Metadata -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SIPERAPAT LLDIKTI Wilayah X">
    <meta property="og:title" content="@yield('title', 'Masuk') — SIPERAPAT LLDIKTI">
    <meta property="og:description" content="Sistem Pencatatan Kehadiran Rapat Kedinasan Terintegrasi.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231d4ed8'%3E%3Cpath d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='9' cy='7' r='4'/%3E%3Cpath d='M22 21v-2a4 4 0 0 0-3-3.87'/%3E%3Cpath d='M16 3.13a4 4 0 0 1 0 7.75'/%3E%3C/svg%3E">

    <title>{{ config('app.name', 'SIPERAPAT') }} — @yield('title', 'Sistem Presensi Rapat LLDIKTI')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body antialiased bg-slate-50 text-slate-800 selection:bg-blue-600 selection:text-white">
    @yield('content')

    {{-- Toast Notification --}}
    <div class="toast" id="app-toast" role="status" aria-live="polite"></div>
</body>
</html>
