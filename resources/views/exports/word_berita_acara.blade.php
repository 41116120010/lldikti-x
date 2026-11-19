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

        /* Rich-text print rules (.prose-gov) for Dompdf / Word */
        .prose-gov {
            font-family: 'Times New Roman', Times, serif;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .prose-gov p {
            margin: 0pt 0pt 5pt 0pt;
        }
        .prose-gov p:last-child {
            margin-bottom: 0pt;
        }
        .prose-gov h2 {
            font-size: 14pt;
            line-height: 19pt;
            font-weight: bold;
            margin: 6pt 0pt 3pt 0pt;
        }
        .prose-gov h3 {
            font-size: 13pt;
            line-height: 18pt;
            font-weight: bold;
            margin: 5pt 0pt 2pt 0pt;
        }
        .prose-gov h4 {
            font-size: 12pt;
            line-height: 18pt;
            font-weight: bold;
            margin: 4pt 0pt 2pt 0pt;
        }
        .prose-gov ul,
        .prose-gov ol {
            margin: 3pt 0pt 5pt 18pt;
            padding: 0pt;
        }
        .prose-gov ul {
            list-style-type: disc;
        }
        .prose-gov ol {
            list-style-type: decimal;
        }
        .prose-gov li {
            margin: 0pt 0pt 2pt 0pt;
        }
        .prose-gov blockquote {
            margin: 4pt 0pt 6pt 0pt;
            padding: 3pt 0pt 3pt 8pt;
            border-left: 2pt solid #64748b;
            font-style: italic;
            color: #334155;
        }
        .prose-gov hr {
            border: none;
            border-top: 0.75pt solid #94a3b8;
            margin: 6pt 0pt;
        }
        .prose-gov a {
            color: #1d4ed8;
            text-decoration: underline;
        }
        .prose-gov font[size="1"] { font-size: 8pt; }
        .prose-gov font[size="2"] { font-size: 10pt; }
        .prose-gov font[size="3"] { font-size: 12pt; }
        .prose-gov font[size="4"] { font-size: 14pt; }
        .prose-gov font[size="5"] { font-size: 18pt; }
        .prose-gov font[size="6"],
        .prose-gov font[size="7"] { font-size: 24pt; }
        .prose-gov table {
            width: 100%;
            border-collapse: collapse;
            margin: 5pt 0pt;
            font-size: 11pt;
            line-height: 15pt;
        }
        .prose-gov th,
        .prose-gov td {
            border: 0.75pt solid #000000;
            padding: 4pt 6pt;
            vertical-align: top;
            text-align: left;
        }
        .prose-gov th {
            background-color: #f1f5f9;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="Section1">
    @include('exports.partials.document_body')
</div>
</body>
</html>
