<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>500 Kendala Server — SIPERAPAT LLDIKTI Wilayah X</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-300 shadow-xl p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-900 border border-rose-300 flex items-center justify-center mx-auto mb-4">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-900 border border-slate-300 mb-3">
            KODE ERROR 500
        </div>
        <h1 class="text-xl font-extrabold text-slate-950 mb-2">Kendala Sistem Internal</h1>
        <p class="text-xs text-slate-700 mb-6 leading-relaxed font-medium">
            Terjadi kendala pada pemrosesan permintaan di sisi server. Tim teknis telah mencatat kejadian ini ke dalam log sistem.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/') }}" class="button w-full sm:w-auto text-xs font-bold px-6 py-2.5 bg-slate-950 hover:bg-slate-800 text-white">
                Kembali ke Beranda
            </a>
            <button onclick="window.location.reload()" class="button secondary w-full sm:w-auto text-xs font-bold px-6 py-2.5">
                Muat Ulang Halaman
            </button>
        </div>
    </div>
</body>
</html>
