<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Berita Acara &mdash; {{ $agenda->judul_rapat }}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        /* Margin halaman mengikuti pedoman format kop surat dinas:
             - atas 2,5 cm supaya kop tidak masuk area non-cetak printer
             - bawah 2,5 cm
             - kanan 2 cm
             - KIRI 3 cm, lebih lebar karena ruang itu dipakai untuk jilid dan
               pengarsipan

           Bentuk @page sengaja ditulis berbeda untuk tiap mesin, karena tidak
           ada satu bentuk yang dipahami keduanya:

             - 'plain'  -> @page polos. Ini satu-satunya bentuk yang dibaca
                           Dompdf, dan Dompdf adalah mesin utama.
             - 'named'  -> @page Section1, konstruk MS Office. Word
                           understands it; LibreOffice membuang named page
                           seluruhnya, sehingga margin di blok itu tidak
                           berlaku pada jalur cadangan. Bukti: mengganti margin
                           menjadi 5 cm di keempat sisi pada jalur itu
                           menghasilkan PDF yang byte-identical.

           Bentuk polos tidak boleh dipakai pada jalur LibreOffice: diuji
           menggantung sampai melewati batas waktu pada lebar konten normal. */
        @if(($pageMode ?? 'named') === 'plain')
        @page {
            size: 595.3pt 841.9pt; /* A4 Portrait: 21.0cm x 29.7cm */
            margin: {{ config('export.page.top', '1.5cm') }} {{ config('export.page.right', '2cm') }} {{ config('export.page.bottom', '1.5cm') }} {{ config('export.page.left', '2cm') }};
        }
        @else
        @page Section1 {
            size: 595.3pt 841.9pt; /* A4 Portrait: 21.0cm x 29.7cm */
            margin: {{ config('export.page.top', '1.5cm') }} {{ config('export.page.right', '2cm') }} {{ config('export.page.bottom', '1.5cm') }} {{ config('export.page.left', '2cm') }};
            mso-header-margin: 1.25cm;
            mso-footer-margin: 1.25cm;
            mso-paper-source: 0;
        }

        div.Section1 {
            page: Section1;
        }
        @endif

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 18pt; mso-line-height-rule: exactly;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        p, div, h1, h2, h3, th, td, li {
            margin: 0pt;
            padding: 0pt;
            mso-pagination: widow-orphan;
            mso-line-height-rule: exactly;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            mso-table-bspace: 0pt;
            mso-table-tspace: 0pt;
            mso-padding-alt: 0pt 0pt 0pt 0pt;
        }

        table.info-table {
            width: 100%;
            margin-bottom: 6pt;
            font-size: 12pt;
        }

        table.info-table td {
            padding: 1.5pt 0;
            vertical-align: top;
            border: none;
        }

        table.attendance-table {
            width: 100%;
            border: 1pt solid #000000;
            margin-top: 0pt;
            font-size: 9pt;
            line-height: 11pt;
        }

        table.attendance-table th {
            background-color: #f2f2f2;
            border: 1pt solid #000000;
            padding: 3pt 2pt;
            font-weight: bold;
        }

        table.attendance-table td {
            border: 1pt solid #000000;
            padding: 2pt 3pt;
            vertical-align: middle;
        }

        table.signature-table {
            width: 100%;
            table-layout: fixed;
            margin: 8pt 0 4pt 0;
            page-break-inside: avoid;
            mso-yfti-row: cantSplit;
        }

        table.signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 12pt;
            border: none;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }
    </style>
</head>
<body>
<div class="Section1">
    @include('exports.partials.document_body')
</div>
</body>
</html>
