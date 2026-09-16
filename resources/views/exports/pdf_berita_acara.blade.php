<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara — {{ $agenda->judul_rapat }}</title>
    <style>
        @page {
            size: 210mm 297mm portrait;
            margin: 12mm 15mm 12mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000000;
            background: #e2e8f0;
            margin: 0;
            padding: 0;
        }

        /* Screen Sheet View */
        .a4-sheet-wrapper {
            display: flex;
            justify-content: center;
            padding: 24px 16px 48px 16px;
            overflow-x: auto;
            min-height: calc(100vh - 54px);
        }

        .a4-sheet-container {
            width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            padding: 12mm 15mm 12mm 15mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1), 0 20px 25px -5px rgba(0, 0, 0, 0.15);
            border: 1px solid #cbd5e1;
            box-sizing: border-box;
            margin: 0 auto;
        }

        .header-kop {
            text-align: center;
            padding-bottom: 2px;
            margin-bottom: 8px;
        }

        .header-kop h3 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-kop h2 {
            margin: 2px 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-kop p {
            margin: 0;
            font-size: 9pt;
            font-style: normal;
        }

        .doc-title {
            text-align: center;
            margin: 12px 0 10px 0;
        }

        .doc-title h1 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-title span {
            font-size: 10pt;
            font-family: 'Courier New', Courier, monospace;
        }

        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            font-size: 10.5pt;
            border: none;
        }

        .info-table td {
            padding: 2.5px 0;
            vertical-align: top;
            border: none;
        }

        .info-table td.label {
            width: 25%;
            font-weight: bold;
        }

        .info-table td.colon {
            width: 2%;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 14px 0 6px 0;
            text-transform: uppercase;
            page-break-after: avoid;
            break-after: avoid;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 4px;
            font-size: 9.5pt;
        }

        .attendance-table th, .attendance-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .attendance-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .sig-cell {
            text-align: center;
            height: 36px;
        }

        .box-text {
            border: 1px solid #000000;
            padding: 6px 8px;
            background-color: #fafafa;
            font-size: 9.5pt;
            line-height: 1.4;
            text-align: justify;
        }

        .signature-block {
            margin-top: 20px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-block table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .signature-block td {
            vertical-align: top;
            text-align: center;
            font-size: 10.5pt;
            border: none;
        }

        /* Top Bar Screen Styles */
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #f8fafc;
        }

        .brand-title strong {
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        .brand-divider {
            color: #475569;
            font-weight: 300;
        }

        .brand-subtitle {
            color: #94a3b8;
            font-size: 12.5px;
            font-weight: 500;
        }

        .actions-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
            min-height: 36px;
        }

        .btn-action svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .btn-pdf {
            background: #059669;
            color: #ffffff;
        }
        .btn-pdf:hover {
            background: #047857;
        }

        .btn-print {
            background: #1e3a8a;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-word {
            background: #2563eb;
            color: #ffffff;
        }
        .btn-word:hover {
            background: #1d4ed8;
        }

        .btn-close {
            background: #334155;
            color: #f1f5f9;
            border: 1px solid #475569;
        }
        .btn-close:hover {
            background: #475569;
        }

        @media print {
            @page {
                size: 210mm 297mm portrait !important;
                margin: 12mm 15mm 12mm 15mm !important;
            }
            html, body {
                width: 210mm !important;
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .a4-sheet-wrapper {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                min-height: auto !important;
            }
            .a4-sheet-container {
                width: 100% !important;
                max-width: none !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            .page-break {
                page-break-before: always;
                break-before: page;
            }
        }
    </style>
</head>
<body>
    <!-- Top Action Bar for Browser (Clean & Non-Slop) -->
    <div class="no-print-bar">
        <div class="brand-title">
            <strong>SIPERAPAT</strong>
            <span class="brand-divider">|</span>
            <span class="brand-subtitle">Pratinjau Dokumen Berita Acara</span>
        </div>
        <div class="actions-group">
            <a href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" class="btn-action btn-pdf" title="Unduh langsung berkas PDF biner resmi">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Unduh PDF (.pdf)
            </a>
            <button class="btn-action btn-print" onclick="window.print()" title="Cetak langsung ke printer atau Simpan ke PDF via browser">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak Lembar A4
            </button>
            <a href="{{ route('admin.reports.export.word', array_merge(['agenda' => $agenda], request()->query())) }}" class="btn-action btn-word" title="Unduh salinan berkas Microsoft Word (.doc)">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <line x1="10" y1="9" x2="8" y2="9"></line>
                </svg>
                Unduh Word (.doc)
            </a>
            <button class="btn-action btn-close" onclick="window.close()" title="Tutup pratinjau">Tutup</button>
        </div>
    </div>

    <!-- Center Screen Wrapper for A4 Physical Sheet -->
    <div class="a4-sheet-wrapper">
        <div class="a4-sheet-container">
            @include('exports.partials.document_body')
        </div>
    </div>
</body>
</html>
