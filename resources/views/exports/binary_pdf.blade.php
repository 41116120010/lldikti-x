<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara — {{ $agenda->judul_rapat }}</title>
    <style>
        @page {
            size: 210mm 297mm portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
        }

        p, div, h1, h2, h3 {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>
    @include('exports.partials.document_body')
</body>
</html>
