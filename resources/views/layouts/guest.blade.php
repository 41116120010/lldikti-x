<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIPERAPAT') }} — @yield('title', 'Sistem Presensi Rapat LLDIKTI')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body antialiased bg-slate-50 text-slate-800 selection:bg-blue-600 selection:text-white">
    @yield('content')

    {{-- Toast Notification --}}
    <div class="toast" id="app-toast" role="status" aria-live="polite"></div>
</body>
</html>
