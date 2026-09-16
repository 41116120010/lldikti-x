<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara — {{ $agenda->judul_rapat }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
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
            letter-spacing: 0.5px;
        }

        .header-kop h2 {
            margin: 2px 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-kop p {
            margin: 0;
            font-size: 9pt;
            font-style: normal;
        }

        .doc-title {
            text-align: center;
            margin: 15px 0 10px 0;
        }

        .doc-title h1 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-title span {
            font-size: 10pt;
            font-family: 'Courier New', Courier, monospace;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
            font-size: 10.5pt;
            border: none;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
            border: none;
        }

        .info-table td.label {
            width: 25%;
            font-weight: bold;
        }

        .info-table td.colon {
            width: 2%;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin: 15px 0 6px 0;
            text-transform: uppercase;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 5px;
            font-size: 10pt;
        }

        .attendance-table th, .attendance-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        .attendance-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .sig-cell {
            text-align: center;
            height: 40px;
        }

        .sig-cell img {
            max-height: 36px;
            max-width: 80px;
            display: block;
            margin: 0 auto;
        }

        .box-text {
            border: 1px solid #000;
            padding: 8px 10px;
            font-size: 10pt;
            min-height: 40px;
            text-align: justify;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .box-text ul, .box-text ol {
            margin: 4px 0 4px 20px;
            padding: 0;
        }

        .box-text li {
            margin: 2px 0;
        }

        .box-text p {
            margin: 4px 0;
        }

        .box-text blockquote {
            border-left: 2px solid #333;
            padding-left: 8px;
            margin: 4px 0;
            font-style: italic;
        }

        .signature-block {
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid;
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
            font-size: 10.5pt;
            border: none;
        }

        .signature-space {
            height: 60px;
        }

        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: sans-serif;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .btn-print {
            background: #059669;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-close {
            background: #475569;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                background: none;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <!-- Top Print Bar for Browser -->
    <div class="no-print-bar">
        <div><strong>SIPERAPAT LLDIKTI</strong> &bull; Pratinjau Dokumen Berita Acara & Daftar Hadir Resmi</div>
        <div style="display: flex; gap: 8px;">
            <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
            <button class="btn-close" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <div style="padding: 20px 30px;">
        <!-- Kop Surat Resmi Instansi -->
        @if($config['show_kop'] ?? true)
        <div class="header-kop">
            @if(($config['show_logo'] ?? true) && isset($logoBase64) && $logoBase64)
            <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
                <tr>
                    <td style="width: 75px; text-align: center; vertical-align: middle; border: none; padding: 0;">
                        <img src="{{ $logoBase64 }}" alt="Logo Instansi" style="max-height: 70px; max-width: 70px; object-fit: contain; display: block; margin: 0 auto;">
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
            <span>Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</span>
            @endif
        </div>

        <!-- Informasi Pelaksanaan -->
        @if($config['show_meeting_info'] ?? true)
        <table class="info-table">
            <tr>
                <td class="label">Perihal / Agenda</td>
                <td class="colon">:</td>
                <td><strong>{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
            </tr>
            <tr>
                <td class="label">Hari / Tanggal</td>
                <td class="colon">:</td>
                <td>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Waktu Pelaksanaan</td>
                <td class="colon">:</td>
                <td>{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
            </tr>
            <tr>
                <td class="label">Format & Tempat</td>
                <td class="colon">:</td>
                <td>
                    {{ ucfirst($agenda->tipe_rapat) }} &mdash; 
                    {{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}
                </td>
            </tr>
            <tr>
                <td class="label">Penyelenggara Rapat</td>
                <td class="colon">:</td>
                <td>{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
            </tr>
        </table>
        @endif

        <!-- Daftar Hadir Peserta -->
        @if($config['show_attendees'] ?? true)
        <div class="section-title">I. DAFTAR KEHADIRAN PESERTA ({{ $agenda->attendances->count() }} Orang)</div>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Nama Lengkap</th>
                    @if($config['show_nip'] ?? true)
                        <th style="width: 20%;">NIP</th>
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
                    <th style="width: 12%;">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td><strong>{{ $item['model']->user->name }}</strong></td>
                        @if($config['show_nip'] ?? true)
                            <td style="font-family: monospace; font-size: 9pt;">{{ $item['model']->user->nip }}</td>
                        @endif
                        @if($config['show_unit'] ?? true)
                            <td>{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                        @endif
                        @if($config['show_attendance_time'] ?? true)
                            <td style="text-align: center; font-size: 9pt;">{{ $item['model']->signed_at->format('H:i') }}</td>
                        @endif
                        @if($config['show_selfie_photos'] ?? true)
                            <td style="text-align: center; vertical-align: middle; padding: 3px;">
                                @if(!empty($item['selfie_base64']))
                                    <img src="{{ $item['selfie_base64'] }}" alt="Selfie" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; display: inline-block;">
                                @else
                                    <span style="font-size: 8pt; color: #94a3b8; font-style: italic;">Tanpa Foto</span>
                                @endif
                            </td>
                        @endif
                        <td class="sig-cell">
                            @if(($config['show_attendee_signatures'] ?? true) && $item['sig_base64'])
                                <img src="{{ $item['sig_base64'] }}" alt="TTD">
                            @else
                                <span style="font-size: 8pt; color: #166534; font-weight: bold;">(HADIR)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    @php
                        $colCount = 3; // No, Nama Lengkap, Tanda Tangan
                        if ($config['show_nip'] ?? true) $colCount++;
                        if ($config['show_unit'] ?? true) $colCount++;
                        if ($config['show_attendance_time'] ?? true) $colCount++;
                        if ($config['show_selfie_photos'] ?? true) $colCount++;
                    @endphp
                    <tr>
                        <td colspan="{{ $colCount }}" style="text-align: center; padding: 15px; font-style: italic; color: #666;">
                            Tidak ada data kehadiran peserta yang tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @endif

        <!-- Notulensi & Kesimpulan -->
        @if(($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true))
        <div class="section-title" style="margin-top: 20px;">II. NOTULENSI &amp; KESIMPULAN RAPAT</div>
        @if($config['show_notulensi'] ?? true)
        <div style="margin-bottom: 10px;">
            <div style="font-weight: bold; font-size: 10pt; margin-bottom: 3px;">A. Catatan Jalannya Rapat (Notulensi):</div>
            <div class="box-text">
                {!! $agenda->formatted_notulensi ?: 'Tidak ada catatan notulensi khusus yang dicatat.' !!}
            </div>
        </div>
        @endif

        @if($config['show_kesimpulan'] ?? true)
        <div>
            <div style="font-weight: bold; font-size: 10pt; margin-bottom: 3px;">B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</div>
            <div class="box-text">
                {!! $agenda->formatted_kesimpulan ?: 'Tidak ada catatan kesimpulan khusus yang dicatat.' !!}
            </div>
        </div>
        @endif
        @endif

        <!-- Lampiran Foto Dokumentasi -->
        @if(($config['show_documentation'] ?? true) && count($documentations) > 0)
        <div class="section-title page-break" style="margin-top: 20px;">III. DOKUMENTASI KEGIATAN</div>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 10px;">
            @foreach($documentations as $docItem)
                <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; text-align: center; background: #fff;">
                    @if($docItem['base64'])
                        <img src="{{ $docItem['base64'] }}" alt="Dokumentasi" style="max-height: 180px; width: auto; max-width: 100%; object-fit: contain; margin: 0 auto; display: block;">
                    @endif
                    @if($docItem['model']->caption)
                        <div style="font-size: 9pt; color: #475569; margin-top: 6px; font-style: italic;">{{ $docItem['model']->caption }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        <!-- Tanda Tangan Pengesahan -->
        <div class="signature-block">
            <table>
                <tr>
                    <td style="width: {{ ($config['show_signer3'] ?? false) ? '33.3%' : '50%' }};">
                        Mengetahui,<br>
                        <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
                        <div class="signature-space" style="display: flex; align-items: center; justify-content: center; height: 60px;">
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
                        <div class="signature-space" style="display: flex; align-items: center; justify-content: center; height: 60px;">
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
    </div>
</body>
</html>
