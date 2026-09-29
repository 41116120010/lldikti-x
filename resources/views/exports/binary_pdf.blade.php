{{--
    DEPRECATED - TIDAK DIRENDER.

    Tidak ada kode yang memuat view ini; satu-satunya view yang dirender untuk
    ekspor adalah exports.word_berita_acara. Berkas ini pernah punya @page dan
    tipografi sendiri yang berbeda dari template Word, sehingga ada dua
    geometri halaman hidup di repositori tanpa ada yang salah satunya berlaku.
    Selain itu, siapa pun yang menyunting berkas ini tidak akan melihat
    perubahan sama sekali karena berkas ini tidak pernah dirender.

    Ekspor PDF biner memakai alur: word_berita_acara -> .doc sementara ->
    LibreOffice headless -> .pdf (lihat PdfExportService).

    Hapus berkas ini begitu tidak lagi dibutuhkan. Isi kop surat TIDAK boleh
    disunting di sini: definisinya tunggal di partials/kop_surat.blade.php.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara — {{ $agenda->judul_rapat }}</title>
    <style>
        @page {
            size: 210mm 297mm portrait;
            margin: 16mm 18mm 20mm 18mm;
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
            font-size: 9.5pt;
            line-height: 1.25;
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

        table.signature-table {
            width: 100%;
            table-layout: fixed;
            margin: 8pt 0 4pt 0;
            page-break-inside: avoid;
        }

        table.signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
    </style>
</head>
<body>
    @include('exports.partials.document_body')
</body>
</html>
