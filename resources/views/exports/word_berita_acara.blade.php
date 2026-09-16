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
        @page Section1 {
            size: 595.3pt 841.9pt; /* A4 Portrait: 21.0cm x 29.7cm */
            margin: 1.2cm 1.5cm 1.2cm 1.8cm;
            mso-header-margin: 0pt;
            mso-footer-margin: 0pt;
            mso-paper-source: 0;
        }

        div.Section1 {
            page: Section1;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            line-height: 1.25;
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

        table.header-kop {
            width: 100%;
            margin-bottom: 6pt;
        }

        table.header-kop td {
            vertical-align: middle;
        }

        table.info-table {
            width: 100%;
            margin-bottom: 5pt;
            font-size: 9.5pt;
        }

        table.info-table td {
            padding: 1.5pt 0;
            vertical-align: top;
            border: none;
        }

        table.attendance-table {
            width: 100%;
            border: 1pt solid #000000;
            margin-top: 2pt;
            font-size: 8.5pt;
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
            margin-top: 4pt;
            page-break-inside: avoid;
            mso-yfti-row: cantSplit;
        }

        table.signature-table td {
            vertical-align: top;
            text-align: center;
            font-size: 9.5pt;
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
