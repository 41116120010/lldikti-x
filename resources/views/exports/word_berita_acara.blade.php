<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Berita Acara — {{ $agenda->judul_rapat }}</title>
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
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000000;
        }

        .header-kop {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .header-kop h3 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-kop h2 {
            margin: 2px 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-kop p {
            margin: 0;
            font-size: 9.5pt;
            font-style: italic;
        }

        .doc-title {
            text-align: center;
            margin: 15px 0;
        }

        .doc-title h1 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }

        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11pt;
        }

        table.info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 10.5pt;
        }

        table.attendance-table th, table.attendance-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        table.attendance-table th {
            background-color: #EFEFEF;
            text-align: center;
            font-weight: bold;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
        }

        .box-text {
            border: 1px solid #000;
            padding: 10px;
            font-size: 11pt;
            min-height: 50px;
            margin-bottom: 10px;
        }

        .signature-block {
            margin-top: 30px;
            width: 100%;
        }

        .signature-block table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-block td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 11pt;
        }

        .signature-space {
            height: 65px;
        }
    </style>
</head>
<body>
    <!-- Kop Surat Resmi -->
    <div class="header-kop">
        <h3>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
        <h2>LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X</h2>
        <p>Jalan Khatib Sulaiman, Padang, Sumatera Barat &bull; Laman: lldikti10.kemdikbud.go.id</p>
    </div>

    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h1>BERITA ACARA DAN DAFTAR HADIR RAPAT</h1>
        <div>Nomor: BA-RAPAT/{{ date('Y') }}/{{ str_pad($agenda->id, 4, '0', STR_PAD_LEFT) }}</div>
    </div>

    <!-- Data Rapat -->
    <table class="info-table">
        <tr>
            <td style="width: 25%;"><strong>Perihal / Agenda</strong></td>
            <td style="width: 2%;">:</td>
            <td><strong>{{ $agenda->judul_rapat }}</strong></td>
        </tr>
        <tr>
            <td><strong>Hari / Tanggal</strong></td>
            <td>:</td>
            <td>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>Waktu Pelaksanaan</strong></td>
            <td>:</td>
            <td>{{ $agenda->waktu_mulai->format('H:i') }} s.d. {{ $agenda->waktu_selesai->format('H:i') }} WIB</td>
        </tr>
        <tr>
            <td><strong>Format & Tempat</strong></td>
            <td>:</td>
            <td>{{ ucfirst($agenda->tipe_rapat) }} &mdash; {{ $agenda->lokasi_ruang ?? 'Daring (Online Meeting)' }}</td>
        </tr>
        <tr>
            <td><strong>Penyelenggara</strong></td>
            <td>:</td>
            <td>{{ $agenda->creator->name }} ({{ $agenda->creator->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
        </tr>
    </table>

    <!-- I. Daftar Hadir Peserta -->
    <div class="section-title">I. DAFTAR KEHADIRAN PESERTA ({{ $agenda->attendances->count() }} Orang)</div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 32%;">Nama Lengkap</th>
                <th style="width: 24%;">NIP</th>
                <th style="width: 20%;">Unit Kerja / Pokja</th>
                <th style="width: 10%;">Waktu</th>
                <th style="width: 8%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $item['model']->user->name }}</strong></td>
                    <td style="font-family: monospace;">{{ $item['model']->user->nip }}</td>
                    <td>{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                    <td style="text-align: center;">{{ $item['model']->signed_at->format('H:i') }}</td>
                    <td style="text-align: center; color: green; font-weight: bold;">HADIR</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 15px; font-style: italic;">
                        Tidak ada data kehadiran yang tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- II. Notulensi & Kesimpulan -->
    <div class="section-title" style="margin-top: 20px;">II. NOTULENSI & KESIMPULAN RAPAT</div>
    <div>
        <strong>A. Catatan Jalannya Rapat (Notulensi):</strong>
        <div class="box-text">
            {!! nl2br(e($agenda->notulensi ?: 'Tidak ada catatan notulensi khusus yang dicatat.')) !!}
        </div>
    </div>

    <div>
        <strong>B. Kesimpulan & Rencana Tindak Lanjut (RTL):</strong>
        <div class="box-text">
            {!! nl2br(e($agenda->kesimpulan ?: 'Tidak ada catatan kesimpulan khusus yang dicatat.')) !!}
        </div>
    </div>

    <!-- Tanda Tangan Pengesahan -->
    <div class="signature-block">
        <table>
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>Pimpinan Rapat / Penanggung Jawab</strong>
                    <div class="signature-space"></div>
                    <strong><u>{{ $agenda->creator->name }}</u></strong><br>
                    NIP. {{ $agenda->creator->nip }}
                </td>
                <td>
                    Padang, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Notulis / Petugas Presensi</strong>
                    <div class="signature-space"></div>
                    <strong><u>{{ Auth::user()?->name ?? 'Petugas Presensi' }}</u></strong><br>
                    NIP. {{ Auth::user()?->nip ?? '-' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
