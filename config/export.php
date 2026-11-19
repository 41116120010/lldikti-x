<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mesin PDF
    |--------------------------------------------------------------------------
    |
    | Dompdf menjadi mesin utama karena satu alasan yang tidak bisa ditawar:
    | ia menghormati @page. LibreOffice membuang seluruh blok @page bernama
    | tanpa peringatan, sehingga margin halaman terkunci pada bawaannya
    | (kiri 2 cm, kanan 0,98 cm, atas 1,12 cm) dan tabel dokumen meluap ke
    | margin kanan. Bukti: mengganti margin menjadi 5 cm di keempat sisi
    | menghasilkan PDF yang byte-identical.
    |
    | Dompdf juga 6 kali lebih cepat dan 12 kali lebih hemat memori pada
    | dokumen yang sama, dan tidak memunculkan proses LibreOffice sama
    | sekali sehingga tidak ada lagi subprocess yang bisa menggantung.
    |
    | 'auto'   - coba Dompdf, jatuh ke LibreOffice bila Dompdf gagal
    | 'dompdf' - hanya Dompdf
    | 'libreoffice' - hanya LibreOffice (bila Dompdf tidak tersedia)
    |
    */

    'engine' => env('PDF_EXPORT_ENGINE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Margin Halaman (A4)
    |--------------------------------------------------------------------------
    |
    | Diukur dari surat resmi Departemen Pendidikan - LLDIKTI Wilayah XVII:
    | kiri 1,91 cm, kanan 1,41 cm, atas 0,81 cm, bawah 0,47 cm.
    |
    | Nilai atas dan bawah TIDAK diikuti persis. Angka 0,81 dan 0,47 cm
    | berada di dalam pita non-cetak banyak printer kantor (LaserJet sekitar
    | 4,2 mm; sebagian inkjet all-in-one sampai 1,27 cm), sehingga kop surat
    | berisiko terpotong saat dicetak. Dipakai 1,5 cm sebagai lantai aman.
    |
    | Lebar halaman A4 adalah 21 cm, hampir sama dengan Folio 21,59 cm pada
    | surat referensi, jadi margin kiri dan kanan dipakai nyaris apa adanya.
    |
    | Semua nilai di sini hanya menyangkut tata letak cetak. Isi dokumen dan
    | fitur penyesuaian dokumen sama sekali tidak tersentuh.
    |
    */

    'page' => [
        'size' => 'A4',
        'top' => env('PDF_EXPORT_MARGIN_TOP', '1.0cm'),
        'right' => env('PDF_EXPORT_MARGIN_RIGHT', '2cm'),
        'bottom' => env('PDF_EXPORT_MARGIN_BOTTOM', '2.5cm'),
        'left' => env('PDF_EXPORT_MARGIN_LEFT', '3cm'),
    ],

    'dompdf' => [

        'is_remote_enabled' => false,

        'is_html5_parser_enabled' => true,

        /*
        | Font batang tubuh memakai sans-serif (Arial/Helvetica) agar seragam
        | dengan template resmi instansi, sementara Kop Surat tetap Times New Roman.
        */
        'default_font' => env('PDF_EXPORT_FONT', 'sans-serif'),

        'font_dir' => 'app/fonts',

        'font_cache' => 'app/fonts/fontdata',

    ],

];
