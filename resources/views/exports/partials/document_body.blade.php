{{-- resources/views/exports/partials/document_body.blade.php --}}
{{-- Single Source of Truth untuk Berita Acara & Daftar Hadir Resmi --}}

<!-- Kop Surat Resmi Instansi - definisi tunggal di resources/views/partials/kop_surat.blade.php -->
@include('partials.kop_surat', ['kopLogoSrc' => $logoBase64 ?? null, 'kopLogo' => $logoSize ?? null])

<!-- Judul Dokumen & Nomor Berita Acara -->
<div class="doc-title" style="text-align: center; margin: 12pt 0 6pt 0;">
    <h1 style="font-size: 14pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">{{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}</h1>
    @if($config['show_document_number'] ?? true)
    <div style="font-size: 12pt; font-family: 'Times New Roman', Times, serif; margin-top: 2px;">Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</div>
    @endif
</div>

<!-- Informasi Pelaksanaan Rapat -->
@if($config['show_meeting_info'] ?? true)
<table class="info-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; margin-bottom: 6pt; border-collapse: collapse; font-size: 12pt; border: none; font-family: 'Times New Roman', Times, serif; word-wrap: break-word; overflow-wrap: break-word;">
    <tr>
        <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none; width: 24%;">Perihal / Agenda</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 2%;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: 74%;"><strong>{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
    </tr>
    <tr>
        <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none; width: 24%;">Hari / Tanggal</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 2%;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 74%;">{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none; width: 24%;">Waktu Pelaksanaan</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 2%;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 74%;">{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none; width: 24%;">Format &amp; Tempat</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 2%;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: 74%;">
            {{ ucfirst($agenda->tipe_rapat) }} &mdash; 
            {{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}
        </td>
    </tr>
    <tr>
        <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none; width: 24%;">Penyelenggara Rapat</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; width: 2%;">:</td>
        <td style="padding: 1.5pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: 74%;">{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
    </tr>
</table>
@endif

<!-- Seksi I: Notulensi & Kesimpulan Rapat -->
@if(($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true))
<div style="margin-top: 6pt;">
    <div class="section-title" style="font-size: 12pt; font-weight: bold; margin: 0 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">I. NOTULENSI &amp; KESIMPULAN RAPAT</div>

    @if($config['show_notulensi'] ?? true)
    <div style="margin-bottom: 6pt;">
        <div style="font-weight: bold; font-size: 12pt; margin-bottom: 1.5pt; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">A. Catatan Jalannya Rapat (Notulensi):</div>
        <div style="border: 1pt solid #000000; padding: 3pt 5pt; text-align: justify; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; font-size: 12pt; line-height: 18pt; mso-line-height-rule: exactly; background: #fafafa; font-family: 'Times New Roman', Times, serif;">
            {!! $agenda->formatted_notulensi ?: '<span style="color: #64748b; font-style: italic;">Tidak ada catatan notulensi khusus yang dicatat.</span>' !!}
        </div>
    </div>
    @endif

    @if($config['show_kesimpulan'] ?? true)
    <div>
        <div style="font-weight: bold; font-size: 12pt; margin-bottom: 1.5pt; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):</div>
        <div style="border: 1pt solid #000000; padding: 3pt 5pt; text-align: justify; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; font-size: 12pt; line-height: 18pt; mso-line-height-rule: exactly; background: #fafafa; font-family: 'Times New Roman', Times, serif;">
            {!! $agenda->formatted_kesimpulan ?: '<span style="color: #64748b; font-style: italic;">Tidak ada catatan kesimpulan khusus yang dicatat.</span>' !!}
        </div>
    </div>
    @endif
</div>
@endif

<!-- Seksi II: Daftar Hadir Peserta -->
@if($config['show_attendees'] ?? true)
<div class="section-title" style="font-size: 12pt; font-weight: bold; margin: 6pt 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; page-break-after: avoid; break-after: avoid;">II. DAFTAR KEHADIRAN PESERTA (<{{ $agenda->attendances->count() }} Orang)</div>
<table class="attendance-table" width="100%" border="1" cellspacing="0" cellpadding="0" bordercolor="#000000" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #000000; margin: 0; font-size: 9pt; line-height: 11pt; font-family: 'Times New Roman', Times, serif; word-wrap: break-word; overflow-wrap: break-word;">
    <thead>
        <tr style="background-color: #f2f2f2; mso-yfti-tblheader: yes; page-break-inside: avoid; break-inside: avoid;">
            <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 4%; font-weight: bold;">No</th>
            <th style="border: 1px solid #000000; padding: 3pt 5pt; text-align: left; font-weight: bold;">Nama Lengkap</th>
            @if($config['show_nip'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: left; width: 15%; font-weight: bold;">NIP</th>
            @endif
            @if($config['show_unit'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: left; width: 12%; font-weight: bold;">Unit Kerja / Pokja</th>
            @endif
            @if($config['show_attendance_time'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 9%; font-weight: bold;">Waktu</th>
            @endif
            @if($config['show_selfie_photos'] ?? true)
                <th style="border: 1px solid #000000; padding: 3pt 2pt; text-align: center; width: 15%; font-weight: bold;">Foto Kehadiran</th>
            @endif
            <th style="border: 1px solid #000000; padding: 3pt 3pt; text-align: center; width: 15%; font-weight: bold;">Tanda Tangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($attendances as $index => $item)
            <tr style="mso-yfti-row: cantSplit; page-break-inside: avoid; break-inside: avoid;">
                <td style="border: 1px solid #000000; text-align: center; padding: 2pt 2pt; vertical-align: middle;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; padding: 2pt 4pt; vertical-align: middle;"><strong>{{ $item['model']->user->name }}</strong></td>
                @if($config['show_nip'] ?? true)
                    <td style="border: 1px solid #000000; padding: 2pt 3pt; font-family: 'Times New Roman', Times, serif; font-size: 9pt; vertical-align: middle;">{{ $item['model']->user->nip }}</td>
                @endif
                @if($config['show_unit'] ?? true)
                    <td style="border: 1px solid #000000; padding: 2pt 3pt; vertical-align: middle; font-size: 9pt;">{{ $item['model']->user->unit?->kode_unit ?? 'Pusat' }}</td>
                @endif
                @if($config['show_attendance_time'] ?? true)
                    <td style="border: 1px solid #000000; text-align: center; padding: 2pt 2pt; font-size: 9pt; vertical-align: middle;">{{ $item['model']->signed_at->format('H:i') }}</td>
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

<!-- Tanda Tangan Pengesahan: kiri (Pimpinan) - tengah (Kepala LLDIKTI, hanya bila
     show_signer3 aktif) - kanan (Notulis). Urutan ini dipatok di sini. -->
@php
    // Lebar kolom dan lebar isi blok dihitung sekali, di satu tempat.
    //
    // Sebelumnya tiap sel menulis ulang ternary yang sama, dan lebar blok
    // di dalam sel berupa angka tetap (175pt / 135pt). Angka tetap itulah
    // sumber letaknya yang berantakan: begitu margin halaman berubah, lebar
    // sel ikut berubah sementara blok tetap, sehingga teks keluar dari kolomnya.
    // Persentase selalu mengikuti lebar sel, berapa pun margin yang dipakai.
    $ttdThird = (bool) ($config['show_signer3'] ?? false);
    $ttdCell = $ttdThird ? '33.3%' : '50%';
    $ttdCellMid = '33.4%';
    $ttdInner = $ttdThird ? '76%' : '68%';
@endphp
<div class="signature-block" style="margin-top: 18pt; page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
    <table class="signature-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: none; margin: 8pt 0 4pt 0; page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    Mengetahui,<br>
                    <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
                </div>
            </td>
            @if($ttdThird)
            <td width="{{ $ttdCellMid }}" style="width: {{ $ttdCellMid }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    Menyetujui,<br>
                    <strong>{{ $config['signer3_role'] ?? 'Kepala LLDIKTI' }}</strong>
                </div>
            </td>
            @endif
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    {{ $config['signing_city'] ?? 'Padang' }}, {{ $config['signing_date'] ?? now()->translatedFormat('d F Y') }}<br>
                    <strong>{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
                </div>
            </td>
        </tr>
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; height: 36pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    @if(($config['show_signer1_signature'] ?? true) && isset($pimpinanSigBase64) && $pimpinanSigBase64)
                        <img src="{{ $pimpinanSigBase64 }}" alt="TTD Pimpinan" width="95" height="32" style="width: 95px; height: 32px; max-height: 34px; max-width: 100px; object-fit: contain; display: block; border: none;">
                    @endif
                </div>
            </td>
            @if($ttdThird)
            <td width="{{ $ttdCellMid }}" style="width: {{ $ttdCellMid }}; height: 36pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                </div>
            </td>
            @endif
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; height: 36pt; text-align: center; vertical-align: middle; border: none; padding: 1pt 0;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    @if(($config['show_signer2_signature'] ?? true) && isset($notulisSigBase64) && $notulisSigBase64)
                        <img src="{{ $notulisSigBase64 }}" alt="TTD Notulis" width="95" height="32" style="width: 95px; height: 32px; max-height: 34px; max-width: 100px; object-fit: contain; display: block; border: none;">
                    @endif
                </div>
            </td>
        </tr>
        <tr style="page-break-inside: avoid; break-inside: avoid; mso-yfti-row: cantSplit;">
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    <strong>{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</strong><br>
                    NIP {{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}
                </div>
            </td>
            @if($ttdThird)
            <td width="{{ $ttdCellMid }}" style="width: {{ $ttdCellMid }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    <strong>{{ $config['signer3_name'] ?? '-' }}</strong><br>
                    NIP {{ $config['signer3_nip'] ?? '-' }}
                </div>
            </td>
            @endif
            <td width="{{ $ttdCell }}" style="width: {{ $ttdCell }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; font-size: 12pt; font-family: 'Times New Roman', Times, serif;">
                <div style="display: inline-block; width: {{ $ttdInner }}; text-align: left;">
                    <strong>{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</strong><br>
                    NIP {{ $config['signer2_nip'] ?? $agenda->nip_notulis }}
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Seksi III: Lampiran Foto Dokumentasi Kegiatan (Annex Resmi) -->
<!-- Lampiran selalu dimulai di halaman baru. Lampiran adalah bahan
     pendukung, bukan bagian dari naskah; bila ia berbagi halaman dengan blok
     tanda tangan, halaman terakhir terlihat setengah kosong dan terpisah dari
     isinya.
     Garis pemisah atas sengaja dihapus: gunanya memisahkan lampiran dari
     konten sebelumnya, dan pemisah halaman sudah itu. Di halaman tersendiri
     garis itu hanya menjadi garis yatim yang melayang di atas. -->
@if(($config['show_documentation'] ?? true) && count($documentations) > 0)
<div class="sheet-documentation-annex" style="page-break-before: always; break-before: page; margin: 0; padding: 0; border: none; page-break-inside: avoid; break-inside: avoid;">
    <div class="section-title" style="font-size: 12pt; font-weight: bold; margin-bottom: 6pt; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; text-align: center; page-break-after: avoid; break-after: avoid;">
        III. LAMPIRAN FOTO DOKUMENTASI KEGIATAN
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse;">
        @foreach($documentations->chunk(2) as $docRow)
        <tr style="page-break-inside: avoid; break-inside: avoid;">
            @foreach($docRow as $docItem)
            <td width="50%" align="center" valign="top" style="width: 50%; padding: 4pt; border: none;">
                <div style="border: 1px solid #94a3b8; padding: 3pt; background: #ffffff; text-align: center;">
                    @if(!empty($docItem['base64']))
                        <img src="{{ $docItem['base64'] }}" alt="Dokumentasi" width="220" height="135" style="width: 220px; height: 135px; max-height: 140px; max-width: 220px; object-fit: contain; margin: 0 auto; display: block; border: none;">
                    @endif
                    @if(!empty($docItem['model']->caption))
                        <div style="font-size: 7.5pt; color: #334155; margin-top: 2pt; font-style: italic; font-family: 'Times New Roman', Times, serif;">{{ $docItem['model']->caption }}</div>
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
</div>
@endif
