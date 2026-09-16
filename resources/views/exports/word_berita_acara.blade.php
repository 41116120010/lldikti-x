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
            margin: 2.0cm 1.5cm 2.0cm 1.5cm;
            mso-header-margin: 36.0pt;
            mso-footer-margin: 36.0pt;
            mso-paper-source: 0;
        }

        div.Section1 {
            page: Section1;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.25;
            color: #000000;
        }

        p, div {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        table.header-kop {
            width: 100%;
            border-bottom: 2.25pt double #000000;
            margin-bottom: 8pt;
            padding-bottom: 4pt;
        }

        table.header-kop td {
            vertical-align: middle;
            border: none;
        }

        table.info-table {
            width: 100%;
            margin-bottom: 8pt;
            font-size: 10pt;
        }

        table.info-table td {
            padding: 2pt 0;
            vertical-align: top;
            border: none;
        }

        table.attendance-table {
            width: 100%;
            border: 0.5pt solid #000000;
            margin-top: 3pt;
            font-size: 9.5pt;
        }

        table.attendance-table th {
            background-color: #f2f2f2;
            border: 0.5pt solid #000000;
            padding: 4pt 3pt;
            font-weight: bold;
        }

        table.attendance-table td {
            border: 0.5pt solid #000000;
            padding: 3pt 4pt;
            vertical-align: middle;
        }

        table.box-container {
            width: 100%;
            border: 0.5pt solid #000000;
            margin-top: 2pt;
            margin-bottom: 6pt;
        }

        table.box-container td {
            border: 0.5pt solid #000000;
            padding: 5pt 7pt;
            font-size: 9.5pt;
            text-align: justify;
        }

        table.doc-grid {
            width: 100%;
            margin-top: 4pt;
        }

        table.doc-grid td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 6pt;
            border: 0.5pt solid #cbd5e1;
        }

        table.signature-table {
            width: 100%;
            margin-top: 14pt;
            page-break-inside: avoid;
            mso-yfti-row: cantSplit;
        }

        table.signature-table td {
            vertical-align: top;
            text-align: center;
            font-size: 10pt;
            border: none;
        }
    </style>
</head>
<body>
<div class="Section1">

    <!-- Kop Surat Resmi Instansi -->
    @if($config['show_kop'] ?? true)
    <table class="header-kop" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border-bottom: 2.25pt double #000000; margin-bottom: 8pt; padding-bottom: 4pt;">
        <tr>
            @if(($config['show_logo'] ?? true) && isset($logoBase64) && $logoBase64)
            <td width="75" align="center" valign="middle" style="width: 75pt; padding-right: 10pt; padding-bottom: 4pt;">
                <img src="{{ $logoBase64 }}" alt="Logo Instansi" width="65" height="65" style="max-height: 65px; max-width: 65px; display: block; margin: 0 auto;">
            </td>
            @endif
            <td align="center" valign="middle" style="padding-bottom: 4pt;">
                <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase;">{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</div>
                <div style="font-size: 12.5pt; font-weight: bold; text-transform: uppercase; margin: 2pt 0;">{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</div>
                <div style="font-size: 9pt; font-style: normal;">{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat &bull; Laman: lldikti10.kemdikbud.go.id' }}</div>
            </td>
            @if(($config['show_logo'] ?? true) && isset($logoBase64) && $logoBase64)
            <td width="75" style="width: 75pt;">&nbsp;</td>
            @endif
        </tr>
    </table>
    @else
    <div style="height: 12pt;"></div>
    @endif

    <!-- Judul Dokumen -->
    <div style="text-align: center; margin: 8pt 0 6pt 0;">
        <div style="font-size: 12pt; font-weight: bold; text-decoration: underline;">{{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}</div>
        @if($config['show_document_number'] ?? true)
        <div style="font-size: 9.5pt; margin-top: 2pt;">Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</div>
        @endif
    </div>

    <!-- Data Pelaksanaan Rapat (Info Table) -->
    @if($config['show_meeting_info'] ?? true)
    <table class="info-table" width="100%" border="0" cellspacing="0" cellpadding="2" style="width: 100%; border-collapse: collapse; margin-bottom: 8pt; font-size: 10pt;">
        <tr>
            <td width="135" style="width: 135pt; font-weight: bold;">Perihal / Agenda</td>
            <td width="15" style="width: 15pt;" align="center">:</td>
            <td><strong>{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Hari / Tanggal</td>
            <td align="center">:</td>
            <td>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Waktu Pelaksanaan</td>
            <td align="center">:</td>
            <td>{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Format &amp; Tempat</td>
            <td align="center">:</td>
            <td>{{ ucfirst($agenda->tipe_rapat) }} &mdash; {{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Penyelenggara</td>
            <td align="center">:</td>
            <td>{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
        </tr>
    </table>
    @endif

    <!-- I. Daftar Hadir Peserta -->
    @if($config['show_attendees'] ?? true)
    @php
        $hasNip = $config['show_nip'] ?? true;
        $hasUnit = $config['show_unit'] ?? true;
        $hasTime = $config['show_attendance_time'] ?? true;
        $hasSelfie = $config['show_selfie_photos'] ?? true;

        $colCount = 2; // No, Nama Lengkap
        if ($hasNip) $colCount++;
        if ($hasUnit) $colCount++;
        if ($hasTime) $colCount++;
        if ($hasSelfie) $colCount++;
        $colCount++; // Status / TTD

        // Dynamic column percentage calculation ensuring exact 100% total
        $wNo = 5;
        $wSelfie = $hasSelfie ? 11 : 0;
        $wTime = $hasTime ? 9 : 0;
        $wSig = 15;
        $wUnit = $hasUnit ? 18 : 0;
        $wNip = $hasNip ? 20 : 0;
        $wNama = 100 - $wNo - $wSelfie - $wTime - $wSig - $wUnit - $wNip;
    @endphp
    <div style="font-size: 10.5pt; font-weight: bold; margin: 8pt 0 3pt 0;">I. DAFTAR KEHADIRAN PESERTA ({{ count($attendances) }} Orang)</div>
    <table class="attendance-table" width="100%" border="1" cellspacing="0" cellpadding="0" bordercolor="#000000" style="width: 100%; border-collapse: collapse; border: 0.5pt solid #000000; margin-top: 3pt; font-size: 9.5pt;">
        <thead style="mso-yfti-tblheader: yes; background-color: #f2f2f2;">
            <tr style="mso-yfti-tblheader: yes; background-color: #f2f2f2;">
                <th width="{{ $wNo }}%" align="center" style="width: {{ $wNo }}%; border: 0.5pt solid #000000; padding: 4pt 3pt;">No</th>
                <th width="{{ $wNama }}%" align="left" style="width: {{ $wNama }}%; border: 0.5pt solid #000000; padding: 4pt 5pt;">Nama Lengkap</th>
                @if($hasNip)
                    <th width="{{ $wNip }}%" align="left" style="width: {{ $wNip }}%; border: 0.5pt solid #000000; padding: 4pt 5pt;">NIP</th>
                @endif
                @if($hasUnit)
                    <th width="{{ $wUnit }}%" align="left" style="width: {{ $wUnit }}%; border: 0.5pt solid #000000; padding: 4pt 5pt;">Unit Kerja / Pokja</th>
                @endif
                @if($hasTime)
                    <th width="{{ $wTime }}%" align="center" style="width: {{ $wTime }}%; border: 0.5pt solid #000000; padding: 4pt 3pt;">Waktu</th>
                @endif
                @if($hasSelfie)
                    <th width="{{ $wSelfie }}%" align="center" style="width: {{ $wSelfie }}%; border: 0.5pt solid #000000; padding: 4pt 3pt;">Foto Kehadiran</th>
                @endif
                <th width="{{ $wSig }}%" align="center" style="width: {{ $wSig }}%; border: 0.5pt solid #000000; padding: 4pt 3pt;">Status / TTD</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $item)
                <tr style="mso-yfti-row: cantSplit; page-break-inside: avoid;">
                    <td align="center" style="border: 0.5pt solid #000000; padding: 3pt;">{{ $index + 1 }}</td>
                    <td style="border: 0.5pt solid #000000; padding: 3pt 5pt;"><strong>{{ $item['model']->user->name }}</strong></td>
                    @if($hasNip)
                        <td style="border: 0.5pt solid #000000; padding: 3pt 5pt; font-family: monospace; font-size: 8.5pt;">{{ $item['model']->user->nip }}</td>
                    @endif
                    @if($hasUnit)
                        <td style="border: 0.5pt solid #000000; padding: 3pt 5pt;">{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                    @endif
                    @if($hasTime)
                        <td align="center" style="border: 0.5pt solid #000000; padding: 3pt;">{{ $item['model']->signed_at->format('H:i') }}</td>
                    @endif
                    @if($hasSelfie)
                        <td align="center" valign="middle" style="border: 0.5pt solid #000000; padding: 2pt;">
                            @if(!empty($item['selfie_base64']))
                                <img src="{{ $item['selfie_base64'] }}" alt="Selfie" width="38" height="38" style="max-height: 38px; max-width: 38px; display: inline-block;">
                            @else
                                <span style="font-size: 8pt; color: #94a3b8; font-style: italic;">-</span>
                            @endif
                        </td>
                    @endif
                    <td align="center" valign="middle" style="border: 0.5pt solid #000000; padding: 3pt;">
                        @if(($config['show_attendee_signatures'] ?? true) && $item['sig_base64'])
                            <img src="{{ $item['sig_base64'] }}" alt="TTD" style="max-height: 30px; max-width: 75px;">
                        @else
                            <span style="color: #166534; font-weight: bold; font-size: 8.5pt;">HADIR</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colCount }}" align="center" style="border: 0.5pt solid #000000; padding: 8pt; font-style: italic; color: #64748b;">
                        Tidak ada data kehadiran yang tercatat.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <!-- II. Notulensi & Kesimpulan (1x1 Table Container for 100% border durability) -->
    @if(($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true))
    <div style="font-size: 10.5pt; font-weight: bold; margin: 8pt 0 3pt 0;">II. NOTULENSI &amp; KESIMPULAN RAPAT</div>
    @if($config['show_notulensi'] ?? true)
    <div style="margin-bottom: 4pt;">
        <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 1pt;">A. Catatan Jalannya Rapat (Notulensi):</div>
        <table class="box-container" width="100%" border="1" cellspacing="0" cellpadding="5" bordercolor="#000000" style="width: 100%; border-collapse: collapse; border: 0.5pt solid #000000; margin-top: 2pt; margin-bottom: 4pt;">
            <tr>
                <td style="border: 0.5pt solid #000000; padding: 5pt 7pt; font-size: 9.5pt; text-align: justify;">
                    {!! $agenda->formatted_notulensi ?: 'Tidak ada catatan notulensi khusus yang dicatat.' !!}
                </td>
            </tr>
        </table>
    </div>
    @endif

    @if($config['show_kesimpulan'] ?? true)
    <div style="margin-bottom: 4pt;">
        <div style="font-weight: bold; font-size: 9.5pt; margin-bottom: 1pt;">B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</div>
        <table class="box-container" width="100%" border="1" cellspacing="0" cellpadding="5" bordercolor="#000000" style="width: 100%; border-collapse: collapse; border: 0.5pt solid #000000; margin-top: 2pt; margin-bottom: 4pt;">
            <tr>
                <td style="border: 0.5pt solid #000000; padding: 5pt 7pt; font-size: 9.5pt; text-align: justify;">
                    {!! $agenda->formatted_kesimpulan ?: 'Tidak ada catatan kesimpulan khusus yang dicatat.' !!}
                </td>
            </tr>
        </table>
    </div>
    @endif
    @endif

    <!-- III. Foto Dokumentasi Kegiatan (Opsional) -->
    @if(($config['show_documentation'] ?? true) && isset($documentations) && count($documentations) > 0)
    <div style="font-size: 10.5pt; font-weight: bold; margin: 8pt 0 3pt 0;">III. DOKUMENTASI KEGIATAN</div>
    <table class="doc-grid" width="100%" border="0" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse; margin-top: 4pt;">
        @foreach($documentations->chunk(2) as $chunk)
            <tr style="mso-yfti-row: cantSplit; page-break-inside: avoid;">
                @foreach($chunk as $docItem)
                    <td width="50%" align="center" valign="top" style="width: 50%;">
                        @if($docItem['base64'])
                            <img src="{{ $docItem['base64'] }}" alt="Dokumentasi" style="max-height: 150px; max-width: 230px; display: block; margin: 0 auto;">
                        @endif
                        @if($docItem['model']->caption)
                            <div style="font-size: 8.5pt; color: #475569; margin-top: 4pt; font-style: italic;">{{ $docItem['model']->caption }}</div>
                        @endif
                    </td>
                @endforeach
                @if(count($chunk) < 2)
                    <td width="50%" style="width: 50%; border: none;">&nbsp;</td>
                @endif
            </tr>
        @endforeach
    </table>
    @endif

    <!-- Tanda Tangan Pengesahan (3-Row Structure: Header, Space/Image, Name/NIP) -->
    @php
        $showSigner3 = $config['show_signer3'] ?? false;
        $sigColWidth = $showSigner3 ? '33.3%' : '50%';
    @endphp
    <table class="signature-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; margin-top: 12pt; page-break-inside: avoid; mso-yfti-row: cantSplit;">
        <tr style="page-break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                Mengetahui,<br>
                <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
            </td>
            @if($showSigner3)
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                Menyetujui,<br>
                <strong>{{ $config['signer3_role'] ?? 'Kepala LLDIKTI' }}</strong>
            </td>
            @endif
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                {{ $config['signing_city'] ?? 'Padang' }}, {{ $config['signing_date'] ?? now()->translatedFormat('d F Y') }}<br>
                <strong>{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
            </td>
        </tr>
        <tr height="55" style="height: 42pt; mso-height-rule: exactly; page-break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $sigColWidth }}" align="center" valign="middle" height="55" style="width: {{ $sigColWidth }}; height: 42pt; text-align: center; vertical-align: middle;">
                @if(($config['show_signer1_signature'] ?? true) && isset($pimpinanSigBase64) && $pimpinanSigBase64)
                    <img src="{{ $pimpinanSigBase64 }}" alt="TTD Pimpinan" height="42" style="max-height: 42pt; max-width: 120pt;">
                @else
                    &nbsp;
                @endif
            </td>
            @if($showSigner3)
            <td width="{{ $sigColWidth }}" align="center" valign="middle" height="55" style="width: {{ $sigColWidth }}; height: 42pt; text-align: center; vertical-align: middle;">
                &nbsp;
            </td>
            @endif
            <td width="{{ $sigColWidth }}" align="center" valign="middle" height="55" style="width: {{ $sigColWidth }}; height: 42pt; text-align: center; vertical-align: middle;">
                @if(($config['show_signer2_signature'] ?? true) && isset($notulisSigBase64) && $notulisSigBase64)
                    <img src="{{ $notulisSigBase64 }}" alt="TTD Notulis" height="42" style="max-height: 42pt; max-width: 120pt;">
                @else
                    &nbsp;
                @endif
            </td>
        </tr>
        <tr style="page-break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                <strong><u>{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</u></strong><br>
                NIP. {{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}
            </td>
            @if($showSigner3)
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                <strong><u>{{ $config['signer3_name'] ?? '-' }}</u></strong><br>
                NIP. {{ $config['signer3_nip'] ?? '-' }}
            </td>
            @endif
            <td width="{{ $sigColWidth }}" align="center" valign="top" style="width: {{ $sigColWidth }}; text-align: center; vertical-align: top;">
                <strong><u>{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</u></strong><br>
                NIP. {{ $config['signer2_nip'] ?? $agenda->nip_notulis }}
            </td>
        </tr>
    </table>

    <!-- Catatan Kaki Resmi (Footer Note) -->
    @if($config['show_footer_note'] ?? true)
    <div style="margin-top: 14pt; padding-top: 6pt; border-top: 1pt dashed #94a3b8; font-size: 8pt; color: #64748b; text-align: center; font-style: italic;">
        {{ $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X' }} &bull; Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>
    @endif

</div>
</body>
</html>
