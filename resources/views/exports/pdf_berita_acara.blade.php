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
            font-style: italic;
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
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
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
            white-space: pre-line;
            text-align: justify;
        }

        .signature-block {
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-block table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-block td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 10.5pt;
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
        <div class="header-kop">
            <h3>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
            <h2>LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X</h2>
            <p>Jalan Khatib Sulaiman, Padang, Sumatera Barat &bull; Laman: lldikti10.kemdikbud.go.id</p>
        </div>

        <!-- Judul Dokumen -->
        <div class="doc-title">
            <h1>BERITA ACARA DAN DAFTAR HADIR RAPAT</h1>
            <span>Nomor: BA-RAPAT/{{ date('Y') }}/{{ str_pad($agenda->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        <!-- Informasi Pelaksanaan -->
        <table class="info-table">
            <tr>
                <td class="label">Perihal / Agenda</td>
                <td class="colon">:</td>
                <td><strong>{{ $agenda->judul_rapat }}</strong></td>
            </tr>
            <tr>
                <td class="label">Hari / Tanggal</td>
                <td class="colon">:</td>
                <td>{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Waktu Pelaksanaan</td>
                <td class="colon">:</td>
                <td>{{ $agenda->waktu_mulai->format('H:i') }} s.d. {{ $agenda->waktu_selesai->format('H:i') }} WIB</td>
            </tr>
            <tr>
                <td class="label">Format & Tempat</td>
                <td class="colon">:</td>
                <td>
                    {{ ucfirst($agenda->tipe_rapat) }} &mdash; 
                    {{ $agenda->lokasi_ruang ?? 'Daring (Online Meeting)' }}
                </td>
            </tr>
            <tr>
                <td class="label">Penyelenggara Rapat</td>
                <td class="colon">:</td>
                <td>{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
            </tr>
        </table>

        <!-- Daftar Hadir Peserta -->
        <div class="section-title">I. DAFTAR KEHADIRAN PESERTA ({{ $agenda->attendances->count() }} Orang)</div>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 32%;">Nama Lengkap</th>
                    <th style="width: 23%;">NIP</th>
                    <th style="width: 22%;">Unit Kerja / Pokja</th>
                    <th style="width: 9%;">Waktu</th>
                    <th style="width: 9%;">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td><strong>{{ $item['model']->user->name }}</strong></td>
                        <td style="font-family: monospace; font-size: 9pt;">{{ $item['model']->user->nip }}</td>
                        <td>{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                        <td style="text-align: center; font-size: 9pt;">{{ $item['model']->signed_at->format('H:i') }}</td>
                        <td class="sig-cell">
                            @if($item['sig_base64'])
                                <img src="{{ $item['sig_base64'] }}" alt="TTD">
                            @else
                                <span style="font-size: 8pt; color: #666;">(Tervalidasi)</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 15px; font-style: italic; color: #666;">
                            Tidak ada data kehadiran peserta yang tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Notulensi & Kesimpulan -->
        <div class="section-title" style="margin-top: 20px;">II. NOTULENSI & KESIMPULAN RAPAT</div>
        <div style="margin-bottom: 10px;">
            <div style="font-weight: bold; font-size: 10pt; margin-bottom: 3px;">A. Catatan Jalannya Rapat (Notulensi):</div>
            <div class="box-text">
                {{ $agenda->notulensi ?: 'Tidak ada catatan notulensi khusus yang dicatat.' }}
            </div>
        </div>

        <div>
            <div style="font-weight: bold; font-size: 10pt; margin-bottom: 3px;">B. Kesimpulan & Rencana Tindak Lanjut (RTL):</div>
            <div class="box-text">
                {{ $agenda->kesimpulan ?: 'Tidak ada catatan kesimpulan khusus yang dicatat.' }}
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
                        <strong><u>{{ $agenda->creator?->name ?? 'Pimpinan Rapat' }}</u></strong><br>
                        NIP. {{ $agenda->creator?->nip ?? '-' }}
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
    </div>
</body>
</html>
