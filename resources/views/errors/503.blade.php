<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>503 Layanan Dalam Pemeliharaan — SIPERAPAT LLDIKTI Wilayah X</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        *, ::before, ::after { box-sizing: border-box; border-width: 0; border-style: solid; border-color: #e2e8f0; }
        html { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.5; -webkit-text-size-adjust: 100%; }
        body { margin: 0; background-color: #f8fafc; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { max-width: 32rem; width: 100%; background-color: #ffffff; border-radius: 1rem; border: 1px solid #cbd5e1; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.1); padding: 2rem; text-align: center; }
        .icon-circle { width: 4.5rem; height: 4.5rem; border-radius: 9999px; background-color: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        .badge { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-family: ui-monospace, monospace; font-weight: 700; background-color: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; margin-bottom: 0.75rem; }
        .badge-pulse { width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #059669; animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        h1 { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 0.5rem; line-height: 1.4; }
        p { font-size: 0.8125rem; color: #475569; margin: 0 0 1.5rem; line-height: 1.6; }
        .button-group { display: flex; flex-direction: column; gap: 0.75rem; justify-content: center; }
        @media (min-width: 640px) { .button-group { flex-direction: row; } }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; min-height: 44px; padding: 0.625rem 1.25rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700; text-decoration: none; cursor: pointer; transition: all 0.15s ease-in-out; border: 1px solid transparent; }
        .btn-primary { background-color: #0f172a; color: #ffffff; }
        .btn-primary:hover { background-color: #1e293b; }
        .btn-secondary { background-color: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .btn-secondary:hover { background-color: #f1f5f9; }
        .meta-footer { margin-top: 1.75rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.6875rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="card" role="main">
        <div class="icon-circle" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div class="badge">
            <span class="badge-pulse"></span>
            PEMELIHARAAN SISTEM (503)
        </div>
        <h1>Layanan Sedang Dalam Pemeliharaan Berkala</h1>
        <p>
            {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Sistem SIPERAPAT LLDIKTI Wilayah X sedang melakukan pembaruan berkala untuk peningkatan performa, keandalan, dan keamanan data kedinasan. Layanan akan segera aktif kembali.' }}
        </p>
        <div class="button-group">
            <button onclick="window.location.reload()" class="btn btn-primary" type="button">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                Periksa Ketersediaan Layanan
            </button>
            <a href="{{ url('/up') }}" class="btn btn-secondary">
                Cek Status Sistem
            </a>
        </div>
        <div class="meta-footer">
            Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X &bull; Biro Sistem Informasi
        </div>
    </div>
</body>
</html>
