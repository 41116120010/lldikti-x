<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $config['perihal'] ?? 'Surat Undangan Rapat' }}</title>
    <style>
        /*
         * Spesifikasi Presisi berdasarkan Template_Surat_Undangan.docx:
         * - Ukuran Kertas: A4 Portrait (21.0 cm × 29.7 cm)
         * - Margin Halaman (w:pgMar):
         *   Left   = 1701 dxa = 3.0 cm
         *   Right  = 1134 dxa = 2.0 cm
         *   Bottom = 1418 dxa = 2.5 cm
         *   Top    = ~1.0 cm (Header + Top Margin)
         */
        @page {
            size: A4 portrait;
            margin: 1.0cm 2.0cm 2.5cm 3.0cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        p {
            margin: 0 0 6pt 0;
            text-align: justify;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }

        /*
         * KOP SURAT DINAS (word/header3.xml)
         * - Font: Times New Roman
         * - Lebar Kop: 18.25 cm (10348 dxa), menjorok kiri -1.26 cm (-714 dxa) & kanan -0.99 cm
         * - Kolom Logo Kiri: ~3.60 cm (2043 dxa = 19.74%), Teks Kanan: ~14.65 cm (8305 dxa = 80.26%)
         * - Logo: 3.67 cm × 3.92 cm (104pt × 111pt)
         * - Baris Kementerian: 16pt, Regular (Tidak Bold), 2 baris
         * - Baris Unit Pelaksana: 14pt, BOLD
         * - Baris Alamat / Telepon / Laman & Pos-el: 12pt, Regular (1 baris utuh)
         * - Garis Penutup Kop: Garis lurus tunggal tebal 2.25pt solid #000000
         */
        .kop-wrapper {
            width: 18.25cm;
            margin: 0 -0.99cm 12pt -1.26cm;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
        }
        .kop-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border-bottom: 2.25pt solid #000000;
            padding-bottom: 4pt;
        }
        .kop-logo-cell {
            width: 19.74%;
            text-align: center;
            vertical-align: middle;
            padding: 0 4pt 4pt 0;
        }
        .kop-logo-cell img {
            width: 104pt;
            height: 111pt;
            max-width: 104pt;
            max-height: 111pt;
        }
        .kop-text-cell {
            width: 80.26%;
            text-align: center;
            vertical-align: middle;
            padding: 0 0 4pt 0;
            font-family: 'Times New Roman', Times, serif;
        }
        .kop-ministry-line {
            font-family: 'Times New Roman', Times, serif;
            font-size: 16pt;
            font-weight: normal;
            line-height: 1.08;
            margin: 0;
            padding: 0;
            text-transform: uppercase;
        }
        .kop-agency-line {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.12;
            margin: 1pt 0;
            padding: 0;
            text-transform: uppercase;
        }
        .kop-address-line {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            font-weight: normal;
            line-height: 1.12;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }

        /*
         * TABEL KEPALA SURAT (TBL#0 — 4 Kolom Tanpa Border, 3 Baris Tanpa "Sifat")
         * Total Lebar Area Cetak = 16.0 cm (9064 dxa):
         * Col 1 (1226 dxa = 13.5%), Col 2 (285 dxa = 3.1%), Col 3 (3871 dxa = 42.7%), Col 4 (3682 dxa = 40.7%)
         */
        .meta-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 12pt;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        .meta-table td {
            vertical-align: top;
            padding: 1pt 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
            border: none;
        }

        /*
         * TABEL RINCIAN ACARA (TBL#8 — 3 Kolom Tanpa Border)
         * Col 1 (1596 dxa = 17.2% / ~2.81 cm), Col 2 (240 dxa = 2.6% / ~0.42 cm), Col 3 (7449 dxa = 80.2% / ~13.14 cm)
         * Label menggunakan huruf kecil semua: hari, tanggal | pukul | tempat | acara
         */
        .detail-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 6pt 0 10pt 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        .detail-table td {
            vertical-align: top;
            padding: 4.5pt 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
            border: none;
        }

        .prose-content {
            margin: 6pt 0;
            text-align: justify;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        .prose-content p {
            margin: 0 0 6pt 0;
            text-align: justify;
            line-height: 1.15;
        }
        .prose-content p:last-child {
            margin-bottom: 0;
        }
        .prose-content ul, .prose-content ol {
            margin: 0 0 6pt 18pt;
            padding: 0;
        }
        .prose-content li {
            margin-bottom: 2pt;
        }
        .prose-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 6pt 0;
        }
        .prose-content th, .prose-content td {
            border: 1px solid #000000;
            padding: 4pt 6pt;
            vertical-align: top;
        }
        .prose-content font[size="1"] { font-size: 8pt; }
        .prose-content font[size="2"] { font-size: 10pt; }
        .prose-content font[size="3"] { font-size: 12pt; }
        .prose-content font[size="4"] { font-size: 14pt; }
        .prose-content font[size="5"] { font-size: 18pt; }
        .prose-content font[size="6"] { font-size: 24pt; }

        /*
         * BLOK PENANDATANGAN & TEMBUSAN (P#14–22)
         * Posisi blok penandatangan dimulai sekitar 8.89 cm dari margin kiri (55.5% lebar area cetak 16 cm)
         * Baris Tembusan: di kiri sejajar dengan baris NIP <bold>197012051992032002</bold> di kanan
         */
        .footer-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 20pt;
            page-break-inside: avoid;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        .footer-table td {
            vertical-align: top;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
            border: none;
            padding: 0;
        }
        .signature-space {
            height: 56pt;
            vertical-align: middle;
        }
        .tte-placeholder {
            line-height: 56pt;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            font-weight: normal;
        }
        .stamp-mark {
            display: inline-block;
            border: 1.5px solid #1e3a8a;
            color: #1e3a8a;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 10pt;
        }

        /* Varian Surat Edaran (SE) */
        .edaran-title {
            text-align: center;
            margin: 10pt 0 14pt 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        .edaran-title h2 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 3pt 0;
            text-transform: uppercase;
        }
        .edaran-title .edaran-nomor {
            font-size: 12pt;
            margin: 0 0 3pt 0;
        }
        .edaran-title .edaran-tentang {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        /*
         * HALAMAN 2 — LAMPIRAN SURAT (word/header1.xml & P#32–39)
         * Header halaman 2: "- 2 -" (Times New Roman 12pt, rata tengah)
         * Batang tubuh lampiran: Arial 12pt, spasi 1.15
         */
        .page-break {
            page-break-before: always;
        }
        .page-2-header {
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            font-weight: normal;
            margin: 0 0 18pt 0;
        }
        .lampiran-meta-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 2pt 0 14pt 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
        }
        .lampiran-meta-table td {
            vertical-align: top;
            padding: 1pt 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.15;
            border: none;
        }
    </style>
</head>
<body>
@php
    $instansiIndukRaw = trim((string) ($config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI"));
    if ($instansiIndukRaw === 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI') {
        $instansiIndukLines = ['KEMENTERIAN PENDIDIKAN TINGGI,', 'SAINS, DAN TEKNOLOGI'];
    } else {
        $instansiIndukLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $instansiIndukRaw))));
    }

    $instansiPelaksana = trim((string) ($config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X'));

    $alamatRaw = trim((string) ($config['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id"));
    $alamatLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $alamatRaw))));

    $tujuanRaw = trim((string) ($config['tujuan_surat'] ?? ''));
    $tujuanFormatted = preg_replace('/^Yth\.?\s*/i', '', $tujuanRaw);

    $signerRoleClean = rtrim(trim((string) ($config['signer_role'] ?? 'Kepala')), ',');
    $signerNameClean = trim((string) ($config['signer_name'] ?? 'Afdalisma'));
    $signerNipClean = trim((string) ($config['signer_nip'] ?? '197012051992032002'));
    $signatureMode = $config['signature_mode'] ?? (!empty($config['show_signature']) ? 'stamp' : 'blank');

    $defaultHariTanggal = $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('l, d F Y') : '-';
    $defaultPukul = ($agenda->waktu_mulai ? $agenda->waktu_mulai->format('H.i') : '-')
        . ' '
        . ($agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H.i') . ' WIB' : 'WIB s.d. selesai');
    $defaultTempat = match ($agenda->tipe_rapat) {
        'online' => 'Daring (Online Meeting) — ' . ($agenda->link_meeting ?? 'Tautan rapat dibagikan melalui grup koordinasi'),
        'hybrid' => ($agenda->lokasi_ruang ?? 'Ruang Rapat LLDIKTI Wilayah X') . ' (Hybrid / Luring & Daring)',
        default => $agenda->lokasi_ruang ?? 'Ruang Rapat Utama LLDIKTI Wilayah X',
    };
    $resolvedDetails = $eventDetails ?? [
        'hari_tanggal' => trim((string) ($config['hari_tanggal_text'] ?? '')) !== '' ? trim((string) $config['hari_tanggal_text']) : $defaultHariTanggal,
        'pukul' => trim((string) ($config['pukul_text'] ?? '')) !== '' ? trim((string) $config['pukul_text']) : $defaultPukul,
        'tempat' => trim((string) ($config['tempat_text'] ?? '')) !== '' ? trim((string) $config['tempat_text']) : $defaultTempat,
        'acara' => trim((string) ($config['acara_text'] ?? '')) !== '' ? trim((string) $config['acara_text']) : (string) $agenda->judul_rapat,
    ];
    $lampiranTitle = trim((string) ($config['lampiran_title'] ?? 'Daftar Undangan')) ?: 'Daftar Undangan';

    $hasLampiranPage = !empty($config['show_lampiran']) || (!empty($config['lampiran']) && trim((string) $config['lampiran']) !== '-');
@endphp

    {{-- KOP SURAT DINAS RESMI (Times New Roman 16pt Reguler / 14pt Bold / 12pt Reguler) --}}
    @if(!empty($config['show_kop']))
        <div class="kop-wrapper">
            <table class="kop-table">
                <tr>
                    @if(!empty($config['show_logo']) && !empty($logoDataUri))
                        <td class="kop-logo-cell">
                            <img src="{{ $logoDataUri }}" alt="Logo Instansi">
                        </td>
                        <td class="kop-text-cell">
                    @else
                        <td class="kop-text-cell" style="width: 100%;" colspan="2">
                    @endif
                            @foreach($instansiIndukLines as $line)
                                <div class="kop-ministry-line">{{ $line }}</div>
                            @endforeach
                            <div class="kop-agency-line">{{ $instansiPelaksana }}</div>
                            @foreach($alamatLines as $addrLine)
                                <div class="kop-address-line">{{ $addrLine }}</div>
                            @endforeach
                        </td>
                </tr>
            </table>
        </div>
    @endif

    @if(($config['template_type'] ?? 'undangan') === 'edaran')
        {{-- ================= FORMAT SURAT EDARAN RESMI (Arial 12pt) ================= --}}
        <div class="edaran-title">
            <h2>SURAT EDARAN</h2>
            <div class="edaran-nomor">NOMOR {{ $config['nomor_surat'] }}</div>
            <div class="edaran-tentang">TENTANG<br>{!! nl2br(e($config['perihal'])) !!}</div>
        </div>

        <div style="margin: 10pt 0 22pt 0;">
            <div>Yth. {!! nl2br(e($tujuanFormatted)) !!}</div>
        </div>

        @if(!empty($config['pembuka']))
            <div class="prose-content">
                {!! $config['pembuka'] !!}
            </div>
        @endif

        @if(!empty($agenda->surat_edaran_content))
            <div class="prose-content">
                {!! $agenda->surat_edaran_content !!}
            </div>
        @endif

        @if(!empty($config['penutup']))
            <div class="prose-content">
                {!! $config['penutup'] !!}
            </div>
        @endif

        <table class="footer-table">
            <tr>
                <td style="width: 55.5%;"></td>
                <td style="width: 44.5%;">
                    <div>Ditetapkan di {{ $config['tempat_surat'] ?? 'Padang' }}</div>
                    <div>pada tanggal {{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</div>
                    <div style="margin-top: 4pt;">{{ $signerRoleClean }},</div>
                    <div class="signature-space">
                        @if($signatureMode === 'stamp')
                            <div class="stamp-mark">Tanda Tangan Digital Tersertifikasi SIPERAPAT</div>
                        @elseif($signatureMode === 'tte_placeholder')
                            <div class="tte-placeholder">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;${ttd_pengirim}</div>
                        @endif
                    </div>
                    <div>{{ $signerNameClean }}</div>
                </td>
            </tr>
            <tr>
                <td style="width: 55.5%;">
                    @if(!empty($config['tembusan']))
                        <div>Tembusan:</div>
                    @endif
                </td>
                <td style="width: 44.5%;">
                    <div style="font-weight: normal;">NIP {{ $signerNipClean }}</div>
                </td>
            </tr>
            @if(!empty($config['tembusan']))
                <tr>
                    <td colspan="2" style="padding-top: 1pt;">
                        <div>{!! nl2br(e($config['tembusan'])) !!}</div>
                    </td>
                </tr>
            @endif
        </table>

    @else
        {{-- ================= FORMAT SURAT UNDANGAN RAPAT KEDINASAN (Template_Surat_Undangan.docx) ================= --}}
        {{-- Tabel Kepala Surat 4 Kolom, 3 Baris (Nomor + Tanggal di Kanan, Lampiran, Hal) --}}
        <table class="meta-table">
            <tr>
                <td style="width: 13.5%;">Nomor</td>
                <td style="width: 3.1%;">:</td>
                <td style="width: 42.7%;">{{ $config['nomor_surat'] }}</td>
                <td style="width: 40.7%; text-align: right;">{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td style="width: 13.5%;">Lampiran</td>
                <td style="width: 3.1%;">:</td>
                <td colspan="2" style="width: 83.4%;">{{ $config['lampiran'] ?? '-' }}</td>
            </tr>
            <tr>
                <td style="width: 13.5%;">Hal</td>
                <td style="width: 3.1%;">:</td>
                <td colspan="2" style="width: 83.4%; text-align: justify;">{!! nl2br(e($config['perihal'])) !!}</td>
            </tr>
        </table>

        {{-- Tujuan Surat (P#2: Yth. ......... rata kiri-kanan sejajar margin kiri, jeda 2 baris ke pembuka) --}}
        <div style="margin: 10pt 0 22pt 0; text-align: justify;">
            <div>Yth. {!! nl2br(e($tujuanFormatted)) !!}</div>
        </div>

        {{-- Alinea Pembuka (P#5–6: diakhiri kata "pada") --}}
        @if(!empty($config['pembuka']))
            <div class="prose-content">
                {!! $config['pembuka'] !!}
            </div>
        @else
            <p>
                Dalam rangka pelaksanaan program kerja dan koordinasi pelaksanaan tugas kedinasan, dengan hormat kami mengharapkan kehadiran Bapak/Ibu pada
            </p>
        @endif

        {{-- Tabel Rincian Acara (TBL#8: 3 kolom 17.2% | 2.6% | 80.2%, label huruf kecil semua) --}}
        <table class="detail-table">
            <tr>
                <td style="width: 17.2%;">hari, tanggal</td>
                <td style="width: 2.6%;">:</td>
                <td style="width: 80.2%;">{{ $resolvedDetails['hari_tanggal'] }}</td>
            </tr>
            <tr>
                <td style="width: 17.2%;">pukul</td>
                <td style="width: 2.6%;">:</td>
                <td style="width: 80.2%;">{{ $resolvedDetails['pukul'] }}</td>
            </tr>
            <tr>
                <td style="width: 17.2%;">tempat</td>
                <td style="width: 2.6%;">:</td>
                <td style="width: 80.2%;">{{ $resolvedDetails['tempat'] }}</td>
            </tr>
            <tr>
                <td style="width: 17.2%;">acara</td>
                <td style="width: 2.6%;">:</td>
                <td style="width: 80.2%;">{{ $resolvedDetails['acara'] }}</td>
            </tr>
        </table>

        @if(!empty($agenda->surat_edaran_content))
            <div class="prose-content">
                {!! $agenda->surat_edaran_content !!}
            </div>
        @endif

        @if(!empty($config['catatan']))
            <div class="prose-content">
                {!! $config['catatan'] !!}
            </div>
        @endif

        {{-- Alinea Penutup (P#10–11) --}}
        @if(!empty($config['penutup']))
            <div class="prose-content">
                {!! $config['penutup'] !!}
            </div>
        @else
            <p>
                Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.
            </p>
        @endif

        {{-- BLOK PENANDATANGAN & TEMBUSAN (P#14–22) --}}
        <table class="footer-table">
            <tr>
                <td style="width: 55.5%;"></td>
                <td style="width: 44.5%;">
                    <div>{{ $signerRoleClean }},</div>
                    <div class="signature-space">
                        @if($signatureMode === 'stamp')
                            <div class="stamp-mark">Tanda Tangan Digital Tersertifikasi SIPERAPAT</div>
                        @elseif($signatureMode === 'tte_placeholder')
                            <div class="tte-placeholder">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;${ttd_pengirim}</div>
                        @endif
                    </div>
                    <div>{{ $signerNameClean }}</div>
                </td>
            </tr>
            <tr>
                <td style="width: 55.5%;">
                    @if(!empty($config['tembusan']))
                        <div>Tembusan:</div>
                    @endif
                </td>
                <td style="width: 44.5%;">
                    <div style="font-weight: normal;">NIP {{ $signerNipClean }}</div>
                </td>
            </tr>
            @if(!empty($config['tembusan']))
                <tr>
                    <td colspan="2" style="padding-top: 1pt;">
                        <div>{!! nl2br(e($config['tembusan'])) !!}</div>
                    </td>
                </tr>
            @endif
        </table>
    @endif

    {{-- ================= HALAMAN 2: LAMPIRAN SURAT — DAFTAR UNDANGAN ================= --}}
    @if($hasLampiranPage && !empty($config['lampiran_content']))
        <div class="page-break"></div>

        <div style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; margin-top: 14pt;">
            <div>Lampiran Surat</div>
            <table class="lampiran-meta-table">
                <tr>
                    <td style="width: 11.1%;">Nomor</td>
                    <td style="width: 3.1%;">:</td>
                    <td style="width: 85.8%;">{{ $config['nomor_surat'] }}</td>
                </tr>
                <tr>
                    <td style="width: 11.1%;">Tanggal</td>
                    <td style="width: 3.1%;">:</td>
                    <td style="width: 85.8%;">{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</td>
                </tr>
            </table>

            <div style="margin: 16pt 0 10pt 0;">{{ $lampiranTitle }}</div>

            <div class="prose-content">
                {!! $config['lampiran_content'] !!}
            </div>
        </div>
    @endif

</body>
</html>
