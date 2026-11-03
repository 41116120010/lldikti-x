<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $config['perihal'] ?? 'Surat Undangan Rapat' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm 2cm 1.5cm 2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        p {
            margin: 0 0 8pt 0;
            text-align: justify;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .kop-table {
            width: 100%;
            border-bottom: 3px double #000000;
            padding-bottom: 8px;
            margin-bottom: 16pt;
        }
        .kop-logo {
            width: 75px;
            text-align: center;
            vertical-align: middle;
        }
        .kop-logo img {
            width: 70px;
            height: auto;
            max-height: 80px;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
            padding-left: 10px;
        }
        .kop-instansi-induk {
            font-size: 13pt;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.2;
        }
        .kop-instansi-pelaksana {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 2px 0;
            line-height: 1.2;
        }
        .kop-alamat {
            font-size: 9pt;
            margin: 0;
            line-height: 1.2;
        }
        .meta-table td {
            vertical-align: top;
            padding: 1.5pt 0;
            font-size: 11pt;
        }
        .detail-table {
            margin: 8pt 0 12pt 0;
            width: 100%;
        }
        .detail-table td {
            vertical-align: top;
            padding: 2.5pt 0;
            font-size: 11pt;
        }
        .prose-content {
            margin: 8pt 0;
            text-align: justify;
        }
        .prose-content p {
            margin: 0 0 6pt 0;
        }
        .prose-content ul, .prose-content ol {
            margin: 0 0 8pt 18pt;
            padding: 0;
        }
        .prose-content li {
            margin-bottom: 3pt;
        }
        .footer-table {
            margin-top: 18pt;
            page-break-inside: avoid;
        }
        .footer-table td {
            vertical-align: top;
        }
        .tembusan-box {
            font-size: 9.5pt;
            line-height: 1.35;
        }
        .tembusan-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2pt;
        }
        .signature-box {
            text-align: left;
            padding-left: 20pt;
            font-size: 11pt;
        }
        .signature-space {
            height: 60pt;
        }
        .stamp-mark {
            display: inline-block;
            border: 1.5px solid #1e3a8a;
            color: #1e3a8a;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-family: sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }
        .edaran-title {
            text-align: center;
            margin: 12pt 0 16pt 0;
        }
        .edaran-title h2 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 4pt 0;
            text-transform: uppercase;
        }
        .edaran-title .edaran-nomor {
            font-size: 11pt;
            margin: 0 0 4pt 0;
        }
        .edaran-title .edaran-tentang {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT DINAS --}}
    @if(!empty($config['show_kop']))
        <table class="kop-table">
            <tr>
                @if(!empty($config['show_logo']) && !empty($logoDataUri))
                    <td class="kop-logo">
                        <img src="{{ $logoDataUri }}" alt="Logo Instansi">
                    </td>
                @endif
                <td class="kop-text">
                    <div class="kop-instansi-induk">{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</div>
                    <div class="kop-instansi-pelaksana">{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</div>
                    <div class="kop-alamat">{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}</div>
                </td>
            </tr>
        </table>
    @endif

    @if(($config['template_type'] ?? 'undangan') === 'edaran')
        {{-- ================= FORMAT SURAT EDARAN RESMI ================= --}}
        <div class="edaran-title">
            <h2>SURAT EDARAN</h2>
            <div class="edaran-nomor">NOMOR: {{ $config['nomor_surat'] }}</div>
            <div class="edaran-tentang">TENTANG<br>{{ $config['perihal'] }}</div>
        </div>

        <div style="margin-bottom: 12pt;">
            <p style="margin: 0;"><strong>Yth.</strong></p>
            <div style="margin-left: 14pt;">{!! nl2br(e($config['tujuan_surat'])) !!}</div>
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

        <table class="footer-table" width="100%">
            <tr>
                <td width="50%">
                    @if(!empty($config['tembusan']))
                        <div class="tembusan-box">
                            <div class="tembusan-title">Tembusan:</div>
                            <div>{!! nl2br(e($config['tembusan'])) !!}</div>
                        </div>
                    @endif
                </td>
                <td width="50%">
                    <div class="signature-box">
                        <div>Ditetapkan di {{ $config['tempat_surat'] ?? 'Padang' }}</div>
                        <div>pada tanggal {{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</div>
                        <div style="margin-top: 4pt; font-weight: bold;">{{ $config['signer_role'] }},</div>
                        <div class="signature-space">
                            @if(!empty($config['show_signature']))
                                <div class="stamp-mark">Tanda Tangan Digital Tersertifikasi SIPERAPAT</div>
                            @endif
                        </div>
                        <div style="font-weight: bold; text-decoration: underline;">{{ $config['signer_name'] }}</div>
                        <div>NIP {{ $config['signer_nip'] ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>

    @else
        {{-- ================= FORMAT SURAT UNDANGAN RAPAT KEDINASAN ================= --}}
        <table class="meta-table" width="100%">
            <tr>
                <td width="58%">
                    <table width="100%">
                        <tr>
                            <td width="65">Nomor</td>
                            <td width="10">:</td>
                            <td>{{ $config['nomor_surat'] }}</td>
                        </tr>
                        <tr>
                            <td>Sifat</td>
                            <td>:</td>
                            <td>{{ $config['sifat'] ?? 'Biasa' }}</td>
                        </tr>
                        <tr>
                            <td>Lampiran</td>
                            <td>:</td>
                            <td>{{ $config['lampiran'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Hal</td>
                            <td>:</td>
                            <td><strong>{{ $config['perihal'] }}</strong></td>
                        </tr>
                    </table>
                </td>
                <td width="42%" style="text-align: right;">
                    {{ $config['tempat_surat'] ?? 'Padang' }}, {{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}
                </td>
            </tr>
        </table>

        <div style="margin: 12pt 0 10pt 0;">
            <div>Yth.</div>
            <div style="font-weight: bold; margin-left: 10pt; line-height: 1.4;">
                {!! nl2br(e($config['tujuan_surat'])) !!}
            </div>
            <div style="margin-left: 10pt; margin-top: 2pt;">di Tempat</div>
        </div>

        @if(!empty($config['pembuka']))
            <div class="prose-content">
                {!! $config['pembuka'] !!}
            </div>
        @else
            <p>
                Dalam rangka pelaksanaan program kerja dan koordinasi pelaksanaan tugas kedinasan, dengan hormat kami mengharapkan kehadiran Bapak/Ibu pada rapat yang akan diselenggarakan pada:
            </p>
        @endif

        {{-- Tabel Rincian Pelaksanaan Rapat (Koneksi Otomatis ke Data Agenda) --}}
        <table class="detail-table">
            <tr>
                <td width="120" style="padding-left: 15pt;">Hari, Tanggal</td>
                <td width="10">:</td>
                <td><strong>{{ $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('l, d F Y') : '-' }}</strong></td>
            </tr>
            <tr>
                <td style="padding-left: 15pt;">Pukul</td>
                <td>:</td>
                <td>
                    {{ $agenda->waktu_mulai ? $agenda->waktu_mulai->format('H:i') : '-' }}
                    {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}
                </td>
            </tr>
            <tr>
                <td style="padding-left: 15pt;">Tempat / Media</td>
                <td>:</td>
                <td>
                    @if($agenda->tipe_rapat === 'online')
                        Daring (Online Meeting) &mdash; <span style="font-family: monospace; font-size: 10pt;">{{ $agenda->link_meeting ?? 'Tautan akan dibagikan via grup koordinasi' }}</span>
                    @elseif($agenda->tipe_rapat === 'hybrid')
                        Hybrid (Luring &amp; Daring) &mdash; {{ $agenda->lokasi_ruang ?? 'Ruang Rapat LLDIKTI X' }}
                    @else
                        Luring &mdash; {{ $agenda->lokasi_ruang ?? 'Ruang Rapat Utama LLDIKTI Wilayah X' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding-left: 15pt;">Agenda / Acara</td>
                <td>:</td>
                <td><strong>{{ $agenda->judul_rapat }}</strong></td>
            </tr>
        </table>

        @if(!empty($agenda->surat_edaran_content))
            <div class="prose-content">
                {!! $agenda->surat_edaran_content !!}
            </div>
        @endif

        @if(!empty($config['catatan']))
            <div class="prose-content" style="font-size: 10.5pt; font-style: italic;">
                {!! $config['catatan'] !!}
            </div>
        @endif

        @if(!empty($config['penutup']))
            <div class="prose-content">
                {!! $config['penutup'] !!}
            </div>
        @else
            <p>
                Mengingat pentingnya agenda rapat ini, dimohon kehadiran Bapak/Ibu tepat pada waktunya. Atas perhatian dan kerja sama yang baik, kami ucapkan terima kasih.
            </p>
        @endif

        {{-- PENGESAHAN & TEMBUSAN --}}
        <table class="footer-table" width="100%">
            <tr>
                <td width="50%">
                    @if(!empty($config['tembusan']))
                        <div class="tembusan-box">
                            <div class="tembusan-title">Tembusan:</div>
                            <div>{!! nl2br(e($config['tembusan'])) !!}</div>
                        </div>
                    @endif
                </td>
                <td width="50%">
                    <div class="signature-box">
                        <div>{{ $config['signer_role'] }},</div>
                        <div class="signature-space">
                            @if(!empty($config['show_signature']))
                                <div class="stamp-mark">Tanda Tangan Digital Tersertifikasi SIPERAPAT</div>
                            @endif
                        </div>
                        <div style="font-weight: bold; text-decoration: underline;">{{ $config['signer_name'] }}</div>
                        <div>NIP {{ $config['signer_nip'] ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
