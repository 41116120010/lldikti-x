{{-- resources/views/exports/partials/document_body.blade.php --}}
{{-- Single Source of Truth untuk Berita Acara & Daftar Hadir Resmi --}}

<!-- Kop Surat Resmi Instansi -->
@if($config['show_kop'] ?? true)
<div class="header-kop" style="margin-bottom: 8px; text-align: center;">
    @if(($config['show_logo'] ?? true) && isset($logoBase64) && $logoBase64)
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
        <tr>
            <td width="60" align="center" valign="middle" style="width: 60px; text-align: center; vertical-align: middle; border: none; padding: 0 0 4px 0;">
                <img src="{{ $logoBase64 }}" alt="Logo Instansi" width="50" height="50" style="width: 50px; height: 50px; max-height: 50px; max-width: 50px; object-fit: contain; display: block; margin: 0 auto; border: none;">
            </td>
            <td align="center" valign="middle" style="text-align: center; vertical-align: middle; border: none; padding: 0 8px 4px 8px;">
                <h3 style="margin: 0; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</h3>
                <h2 style="margin: 1px 0; font-size: 11.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</h2>
                <p style="margin: 0; font-size: 8.5pt; font-style: normal; font-family: 'Times New Roman', Times, serif;">{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}</p>
            </td>
            <td width="60" style="width: 60px; border: none; padding: 0 0 4px 0;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="3" style="border: none; border-bottom: 2.25pt double #000000; mso-border-bottom-alt: double windowtext 2.25pt; height: 1px; font-size: 1pt; line-height: 1pt; padding: 0 0 2pt 0;">&nbsp;</td>
        </tr>
    </table>
    @else
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
        <tr>
            <td align="center" valign="middle" style="text-align: center; vertical-align: middle; border: none; padding: 0 8px 4px 8px;">
                <h3 style="margin: 0; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</h3>
                <h2 style="margin: 1px 0; font-size: 11.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</h2>
                <p style="margin: 0; font-size: 8.5pt; font-style: normal; font-family: 'Times New Roman', Times, serif;">{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}</p>
            </td>
        </tr>
        <tr>
            <td style="border: none; border-bottom: 2.25pt double #000000; mso-border-bottom-alt: double windowtext 2.25pt; height: 1px; font-size: 1pt; line-height: 1pt; padding: 0 0 2pt 0;">&nbsp;</td>
        </tr>
    </table>
    @endif
</div>
@else
<div style="height: 14px;"></div>
@endif

<!-- Judul Dokumen & Nomor Berita Acara -->
<div class="doc-title" style="text-align: center; margin: 8px 0 6px 0;">
    <h1 style="font-size: 11.5pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">{{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}</h1>
    @if($config['show_document_number'] ?? true)
    <div style="font-size: 9.5pt; font-family: 'Courier New', Courier, monospace; margin-top: 2px;">Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</div>
    @endif
</div>

<!-- Informasi Pelaksanaan Rapat -->
@if($config['show_meeting_info'] ?? true)
<table class="info-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; margin-bottom: 5pt; border-collapse: collapse; font-size: 9.5pt; border: none; font-family: 'Times New Roman', Times, serif; word-wrap: break-word; overflow-wrap: break-word;">
    <tr>
        <td style="width: 25%; font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Perihal / Agenda</td>
        <td style="width: 2%; padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word;"><strong>{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
    </tr>
    <tr>
        <td style="width: 25%; font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Hari / Tanggal</td>
        <td style="width: 2%; padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
    </tr>
    <tr>
        <td style="width: 25%; font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Waktu Pelaksanaan</td>
        <td style="width: 2%; padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
    </tr>
    <tr>
        <td style="width: 25%; font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Format &amp; Tempat</td>
        <td style="width: 2%; padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word;">
            {{ ucfirst($agenda->tipe_rapat) }} &mdash; 
            {{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}
        </td>
    </tr>
    <tr>
        <td style="width: 25%; font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Penyelenggara Rapat</td>
        <td style="width: 2%; padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word;">{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
    </tr>
</table>
@endif

<!-- Daftar Hadir Peserta -->
@if($config['show_attendees'] ?? true)
<div class="section-title" style="font-size: 10pt; font-weight: bold; margin: 8pt 0 4pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">I. DAFTAR KEHADIRAN PESERTA ({{ $agenda->attendances->count() }} Orang)</div>
<table class="attendance-table" width="100%" border="1" cellspacing="0" cellpadding="0" bordercolor="#000000" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #000000; margin-top: 2pt; font-size: 8.5pt; font-family: 'Times New Roman', Times, serif; word-wrap: break-word; overflow-wrap: break-word;">
    <thead>
        <tr style="background-color: #f2f2f2; mso-yfti-tblheader: yes; page-break-inside: avoid; break-inside: avoid;">
            <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 4%; font-weight: bold; white-space: nowrap;">No</th>
            <th style="border: 1px solid #000000; padding: 3pt 5pt; text-align: left; font-weight: bold;">Nama Lengkap</th>
            @if($config['show_nip'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: left; width: 18%; font-weight: bold; white-space: nowrap;">NIP</th>
            @endif
            @if($config['show_unit'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: left; width: 15%; font-weight: bold;">Unit Kerja / Pokja</th>
            @endif
            @if($config['show_attendance_time'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 9%; font-weight: bold; white-space: nowrap;">Waktu</th>
            @endif
            @if($config['show_selfie_photos'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 11%; font-weight: bold; white-space: nowrap;">Foto Kehadiran</th>
            @endif
            <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: center; width: 16%; font-weight: bold; white-space: nowrap;">Tanda Tangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($attendances as $index => $item)
            <tr style="mso-yfti-row: cantSplit; page-break-inside: avoid; break-inside: avoid;">
                <td style="border: 1px solid #000000; text-align: center; padding: 2pt 2pt; vertical-align: middle; white-space: nowrap;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; padding: 2pt 4pt; vertical-align: middle;"><strong>{{ $item['model']->user->name }}</strong></td>
                @if($config['show_nip'] ?? true)
                    <td style="border: 1px solid #000000; padding: 2pt 3pt; font-family: monospace; font-size: 8pt; vertical-align: middle; white-space: nowrap;">{{ $item['model']->user->nip }}</td>
                @endif
                @if($config['show_unit'] ?? true)
                    <td style="border: 1px solid #000000; padding: 2pt 3pt; vertical-align: middle; font-size: 8pt;">{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                @endif
                @if($config['show_attendance_time'] ?? true)
                    <td style="border: 1px solid #000000; text-align: center; padding: 2pt 2pt; font-size: 8pt; vertical-align: middle; white-space: nowrap;">{{ $item['model']->signed_at->format('H:i') }}</td>
                @endif
                @if($config['show_selfie_photos'] ?? true)
                    <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; padding: 1.5pt;">
                        @if(!empty($item['selfie_base64']))
                            <img src="{{ $item['selfie_base64'] }}" alt="Selfie" width="26" height="26" style="width: 26px; height: 26px; max-width: 26px; max-height: 26px; object-fit: cover; border-radius: 2px; border: 1px solid #cbd5e1; display: inline-block;">
                        @else
                            <span style="font-size: 7pt; color: #94a3b8; font-style: italic;">Tanpa Foto</span>
                        @endif
                    </td>
                @endif
                <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; padding: 1.5pt;">
                    @if(($config['show_attendee_signatures'] ?? true) && $item['sig_base64'])
                        <img src="{{ $item['sig_base64'] }}" alt="TTD" width="75" height="22" style="width: 75px; height: 22px; max-height: 24px; max-width: 80px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                    @else
                        <span style="font-size: 7.5pt; color: #166534; font-weight: bold;">(HADIR)</span>
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
                <td colspan="{{ $colCount }}" style="border: 1px solid #000000; text-align: center; padding: 8px; font-style: italic; color: #64748b;">
                    Tidak ada data kehadiran peserta yang tercatat.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif

<!-- Notulensi & Kesimpulan Rapat -->
@if(($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true))
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: none; margin-top: 6pt; page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
    <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
        <td style="border: none; padding: 0; width: 100%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
            <div class="section-title" style="font-size: 10pt; font-weight: bold; margin: 0 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">II. NOTULENSI &amp; KESIMPULAN RAPAT</div>

            @if($config['show_notulensi'] ?? true)
            <div style="margin-bottom: 4pt;">
                <div style="font-weight: bold; font-size: 8.5pt; margin-bottom: 1.5pt; font-family: 'Times New Roman', Times, serif;">A. Catatan Jalannya Rapat (Notulensi):</div>
                <table width="100%" border="1" cellspacing="0" cellpadding="0" bordercolor="#000000" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #000000; font-size: 8.5pt; line-height: 1.25; background: #fafafa; font-family: 'Times New Roman', Times, serif;">
                    <tr>
                        <td style="border: 1px solid #000000; padding: 2pt 4pt; text-align: justify; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
                            {!! $agenda->formatted_notulensi ?: '<span style="color: #64748b; font-style: italic;">Tidak ada catatan notulensi khusus yang dicatat.</span>' !!}
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            @if($config['show_kesimpulan'] ?? true)
            <div>
                <div style="font-weight: bold; font-size: 8.5pt; margin-bottom: 1.5pt; font-family: 'Times New Roman', Times, serif;">B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</div>
                <table width="100%" border="1" cellspacing="0" cellpadding="0" bordercolor="#000000" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #000000; font-size: 8.5pt; line-height: 1.25; background: #fafafa; font-family: 'Times New Roman', Times, serif;">
                    <tr>
                        <td style="border: 1px solid #000000; padding: 2pt 4pt; text-align: justify; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
                            {!! $agenda->formatted_kesimpulan ?: '<span style="color: #64748b; font-style: italic;">Tidak ada catatan kesimpulan khusus yang dicatat.</span>' !!}
                        </td>
                    </tr>
                </table>
            </div>
            @endif
        </td>
    </tr>
</table>
@endif

<!-- Lampiran Foto Dokumentasi -->
@if(($config['show_documentation'] ?? true) && count($documentations) > 0)
<div class="section-title" style="font-size: 10pt; font-weight: bold; margin: 12pt 0 4pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; page-break-before: always; break-before: page; page-break-after: avoid; break-after: avoid;">III. DOKUMENTASI KEGIATAN</div>
<table width="100%" border="0" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse; margin-top: 4pt;">
    @foreach($documentations->chunk(2) as $docRow)
    <tr style="page-break-inside: avoid; break-inside: avoid;">
        @foreach($docRow as $docItem)
        <td width="50%" align="center" valign="top" style="width: 50%; padding: 4pt; border: none;">
            <div style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 4pt; background: #ffffff; text-align: center;">
                @if(!empty($docItem['base64']))
                    <img src="{{ $docItem['base64'] }}" alt="Dokumentasi" width="220" height="135" style="width: 220px; height: 135px; max-height: 140px; max-width: 220px; object-fit: contain; margin: 0 auto; display: block; border: none;">
                @endif
                @if(!empty($docItem['model']->caption))
                    <div style="font-size: 8pt; color: #475569; margin-top: 3pt; font-style: italic; font-family: 'Times New Roman', Times, serif;">{{ $docItem['model']->caption }}</div>
                @endif
            </div>
        </td>
        @endforeach
        @if($docRow->count() === 1)
        <td width="50%" style="width: 50%; border: none;">&nbsp;</td>
        @endif
    </tr>
    @endforeach
</table>
@endif

<!-- Tanda Tangan Pengesahan (Stable 3-Row Non-Collapsing Table) -->
<div class="signature-block" style="margin-top: 8pt; page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
    <table class="signature-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border: none; margin-top: 4pt; page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td style="width: {{ ($config['show_signer3'] ?? false) ? '33.3%' : '50%' }}; text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                Mengetahui,<br>
                <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
            </td>
            @if($config['show_signer3'] ?? false)
            <td style="width: 33.3%; text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                Menyetujui,<br>
                <strong>{{ $config['signer3_role'] ?? 'Kepala LLDIKTI' }}</strong>
            </td>
            @endif
            <td style="width: {{ ($config['show_signer3'] ?? false) ? '33.3%' : '50%' }}; text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                {{ $config['signing_city'] ?? 'Padang' }}, {{ $config['signing_date'] ?? now()->translatedFormat('d F Y') }}<br>
                <strong>{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
            </td>
        </tr>
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td style="height: 32pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
                @if(($config['show_signer1_signature'] ?? true) && isset($pimpinanSigBase64) && $pimpinanSigBase64)
                    <img src="{{ $pimpinanSigBase64 }}" alt="TTD Pimpinan" width="95" height="32" style="width: 95px; height: 32px; max-height: 34px; max-width: 100px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                @endif
            </td>
            @if($config['show_signer3'] ?? false)
            <td style="height: 32pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
            </td>
            @endif
            <td style="height: 32pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
                @if(($config['show_signer2_signature'] ?? true) && isset($notulisSigBase64) && $notulisSigBase64)
                    <img src="{{ $notulisSigBase64 }}" alt="TTD Notulis" width="95" height="32" style="width: 95px; height: 32px; max-height: 34px; max-width: 100px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                @endif
            </td>
        </tr>
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td style="text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                <strong><u>{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</u></strong><br>
                NIP. {{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}
            </td>
            @if($config['show_signer3'] ?? false)
            <td style="text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                <strong><u>{{ $config['signer3_name'] ?? '-' }}</u></strong><br>
                NIP. {{ $config['signer3_nip'] ?? '-' }}
            </td>
            @endif
            <td style="text-align: center; vertical-align: top; border: none; padding: 0 6pt; font-size: 9.5pt; font-family: 'Times New Roman', Times, serif;">
                <strong><u>{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</u></strong><br>
                NIP. {{ $config['signer2_nip'] ?? $agenda->nip_notulis }}
            </td>
        </tr>
    </table>
</div>

<!-- Catatan Kaki / Integritas Dokumen -->
@if($config['show_footer_note'] ?? true)
<div style="margin-top: 6pt; padding-top: 2pt; border-top: 1px solid #cbd5e1; font-size: 7pt; color: #64748b; text-align: center; font-family: 'Times New Roman', Times, serif; white-space: nowrap;">
    {{ $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X' }} &bull; <span style="white-space: nowrap;">Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} WIB</span>
</div>
@endif
