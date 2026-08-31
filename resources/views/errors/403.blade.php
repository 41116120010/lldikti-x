<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>403 Akses Dibatasi — SIPERAPAT LLDIKTI Wilayah X</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-300 shadow-xl p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-900 border border-amber-300 flex items-center justify-center mx-auto mb-4">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-900 border border-slate-300 mb-3">
            KODE ERROR 403
        </div>
        <h1 class="text-xl font-extrabold text-slate-950 mb-2">Akses Dibatasi</h1>
        <p class="text-xs text-slate-700 mb-6 leading-relaxed font-medium">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses atau wewenang untuk membuka halaman ini. Silakan hubungi Administrator apabila ini adalah kekeliruan.' }}
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/') }}" class="button w-full sm:w-auto text-xs font-bold px-6 py-2.5 bg-slate-950 hover:bg-slate-800 text-white">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
