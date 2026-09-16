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
            font-style: normal;
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
            border: none;
            margin-bottom: 15px;
            font-size: 11pt;
        }

        table.info-table td {
            padding: 4px 0;
            vertical-align: top;
            border: none;
        }

        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
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
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .signature-block {
            margin-top: 30px;
            width: 100%;
        }

        .signature-block table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .signature-block td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 11pt;
            border: none;
        }

        .signature-space {
            height: 65px;
        }
    </style>
</head>
<body>
    <!-- Kop Surat Resmi -->
    @if($config['show_kop'] ?? true)
    <div class="header-kop">
        @if(($config['show_logo'] ?? true) && isset($logoBase64) && $logoBase64)
        <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
            <tr>
                <td style="width: 75px; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ $logoBase64 }}" alt="Logo Instansi" width="65" height="65" style="max-height: 65px; max-width: 65px; display: block; margin: 0 auto;">
                </td>
                <td style="text-align: center; vertical-align: middle; border: none; padding: 0 10px;">
                    <h3>{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</h3>
                    <h2>{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</h2>
                    <p>{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}</p>
                </td>
                <td style="width: 75px; border: none; padding: 0;"></td>
            </tr>
        </table>
        @else
        <h3>{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</h3>
        <h2>{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</h2>
        <p>{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}</p>
        @endif
    </div>
    @else
    <div style="height: 25px;"></div>
    @endif

    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h1>{{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}</h1>
        @if($config['show_document_number'] ?? true)
        <div>Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</div>
        @endif
    </div>

    <!-- Data Rapat -->
    @if($config['show_meeting_info'] ?? true)
    <table class="info-table">
        <tr>
            <td style="width: 25%;"><strong>Perihal / Agenda</strong></td>
            <td style="width: 2%;">:</td>
            <td><strong>{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
        </tr>
        <tr>
            <td><strong>Hari / Tanggal</strong></td>
            <td>:</td>
            <td>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>Waktu Pelaksanaan</strong></td>
            <td>:</td>
            <td>{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
        </tr>
        <tr>
            <td><strong>Format & Tempat</strong></td>
            <td>:</td>
            <td>{{ ucfirst($agenda->tipe_rapat) }} &mdash; {{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}</td>
        </tr>
        <tr>
            <td><strong>Penyelenggara</strong></td>
            <td>:</td>
            <td>{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
        </tr>
    </table>
    @endif

    <!-- I. Daftar Hadir Peserta -->
    @if($config['show_attendees'] ?? true)
    <div class="section-title">I. DAFTAR KEHADIRAN PESERTA ({{ $agenda->attendances->count() }} Orang)</div>
    <table class="attendance-table" border="1" cellspacing="0" cellpadding="0" style="border-collapse: collapse; border: 1px solid #000000; width: 100%;">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th>Nama Lengkap</th>
                @if($config['show_nip'] ?? true)
                    <th style="width: 22%;">NIP</th>
                @endif
                @if($config['show_unit'] ?? true)
                    <th style="width: 18%;">Unit Kerja / Pokja</th>
                @endif
                @if($config['show_attendance_time'] ?? true)
                    <th style="width: 10%;">Waktu</th>
                @endif
                @if($config['show_selfie_photos'] ?? true)
                    <th style="width: 12%;">Foto Kehadiran</th>
                @endif
                <th style="width: 12%;">Status / TTD</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $item['model']->user->name }}</strong></td>
                    @if($config['show_nip'] ?? true)
                        <td style="font-family: monospace;">{{ $item['model']->user->nip }}</td>
                    @endif
                    @if($config['show_unit'] ?? true)
                        <td>{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                    @endif
                    @if($config['show_attendance_time'] ?? true)
                        <td style="text-align: center;">{{ $item['model']->signed_at->format('H:i') }}</td>
                    @endif
                    @if($config['show_selfie_photos'] ?? true)
                        <td style="text-align: center; vertical-align: middle; padding: 4px;">
                            @if(!empty($item['selfie_base64']))
                                <img src="{{ $item['selfie_base64'] }}" alt="Selfie" width="40" height="40" style="max-height: 40px; max-width: 40px; display: inline-block;">
                            @else
                                <span style="font-size: 8pt; color: #94a3b8; font-style: italic;">-</span>
                            @endif
                        </td>
                    @endif
                    <td style="text-align: center; vertical-align: middle;">
                        @if(($config['show_attendee_signatures'] ?? true) && $item['sig_base64'])
                            <img src="{{ $item['sig_base64'] }}" alt="TTD" style="max-height: 35px; max-width: 90px;">
                        @else
                            <span style="color: green; font-weight: bold; font-size: 9pt;">HADIR</span>
                        @endif
                    </td>
                </tr>
            @empty
                @php
                    $colCount = 3;
                    if ($config['show_nip'] ?? true) $colCount++;
                    if ($config['show_unit'] ?? true) $colCount++;
                    if ($config['show_attendance_time'] ?? true) $colCount++;
                    if ($config['show_selfie_photos'] ?? true) $colCount++;
                @endphp
                <tr>
                    <td colspan="{{ $colCount }}" style="text-align: center; padding: 15px; font-style: italic;">
                        Tidak ada data kehadiran yang tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <!-- II. Notulensi & Kesimpulan -->
    @if(($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true))
    <div class="section-title" style="margin-top: 20px;">II. NOTULENSI &amp; KESIMPULAN RAPAT</div>
    @if($config['show_notulensi'] ?? true)
    <div style="margin-bottom: 10px;">
        <strong>A. Catatan Jalannya Rapat (Notulensi):</strong>
        <div class="box-text">
            {!! $agenda->formatted_notulensi ?: 'Tidak ada catatan notulensi khusus yang dicatat.' !!}
        </div>
    </div>
    @endif

    @if($config['show_kesimpulan'] ?? true)
    <div>
        <strong>B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</strong>
        <div class="box-text">
            {!! $agenda->formatted_kesimpulan ?: 'Tidak ada catatan kesimpulan khusus yang dicatat.' !!}
        </div>
    </div>
    @endif
    @endif

    <!-- III. Foto Dokumentasi Kegiatan (Opsional) -->
    @if(($config['show_documentation'] ?? true) && isset($documentations) && count($documentations) > 0)
    <div class="section-title" style="margin-top: 20px;">III. DOKUMENTASI KEGIATAN</div>
    <table style="width: 100%; border: none;">
        @foreach($documentations->chunk(2) as $chunk)
            <tr>
                @foreach($chunk as $docItem)
                    <td style="width: 50%; text-align: center; padding: 10px; border: 1px solid #cbd5e1;">
                        @if($docItem['base64'])
                            <img src="{{ $docItem['base64'] }}" alt="Dokumentasi" style="max-height: 180px; max-width: 250px;">
                        @endif
                        @if($docItem['model']->caption)
                            <div style="font-size: 9pt; color: #475569; margin-top: 5px; font-style: italic;">{{ $docItem['model']->caption }}</div>
                        @endif
                    </td>
                @endforeach
                @if(count($chunk) < 2)
                    <td style="width: 50%; border: none;"></td>
                @endif
            </tr>
        @endforeach
    </table>
    @endif

    <!-- Tanda Tangan Pengesahan -->
    <div class="signature-block" style="margin-top: 25px;">
        <table>
            <tr>
                <td style="width: {{ ($config['show_signer3'] ?? false) ? '33.3%' : '50%' }};">
                    Mengetahui,<br>
                    <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
                    <div class="signature-space" style="height: 60px;">
                        @if(($config['show_signer1_signature'] ?? true) && isset($pimpinanSigBase64) && $pimpinanSigBase64)
                            <img src="{{ $pimpinanSigBase64 }}" alt="TTD Pimpinan" style="max-height: 55px; max-width: 140px;">
                        @endif
                    </div>
                    <strong><u>{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</u></strong><br>
                    NIP. {{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}
                </td>

                @if($config['show_signer3'] ?? false)
                <td style="width: 33.3%;">
                    Menyetujui,<br>
                    <strong>{{ $config['signer3_role'] ?? 'Kepala LLDIKTI' }}</strong>
                    <div class="signature-space" style="height: 60px;"></div>
                    <strong><u>{{ $config['signer3_name'] ?? '-' }}</u></strong><br>
                    NIP. {{ $config['signer3_nip'] ?? '-' }}
                </td>
                @endif

                <td style="width: {{ ($config['show_signer3'] ?? false) ? '33.3%' : '50%' }};">
                    {{ $config['signing_city'] ?? 'Padang' }}, {{ $config['signing_date'] ?? now()->translatedFormat('d F Y') }}<br>
                    <strong>{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
                    <div class="signature-space" style="height: 60px;">
                        @if(($config['show_signer2_signature'] ?? true) && isset($notulisSigBase64) && $notulisSigBase64)
                            <img src="{{ $notulisSigBase64 }}" alt="TTD Notulis" style="max-height: 55px; max-width: 140px;">
                        @endif
                    </div>
                    <strong><u>{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</u></strong><br>
                    NIP. {{ $config['signer2_nip'] ?? $agenda->nip_notulis }}
                </td>
            </tr>
        </table>
    </div>

    @if($config['show_footer_note'] ?? true)
    <div style="margin-top: 30px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 8pt; color: #64748b; text-align: center; font-style: italic;">
        {{ $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X' }} &bull; Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>
    @endif
</body>
</html>
