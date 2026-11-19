{{--
    Kop surat dinas - SATU sumber kebenaran.

    Dipakai oleh tiga pemakai sekaligus:
      1. Workstation notulen (WYSIWYG on-screen)
      2. Ekspor Word (.doc) dan PDF
      3. Preview iframe pada halaman detail agenda

    Angka tipografi di sini mengikuti surat resmi Departemen Pendidikan -
    LLDIKTI Wilayah XVII, yang diukur langsung dari berkas aslinya:

      Kementerian      16 pt, Times REGULER  (bukan tebal)
      Lembaga / Wilayah 14 pt, Times BOLD
      Alamat            12 pt, Times reguler, dua baris
      Logo              2,8 cm, rata kiri

    Perhatikan arah hierarkinya: nama Kementerian TIDAK ditebalkan. Yang
    ditebalkan adalah nama lembaga pelaksana. Rancangan sebelumnya justru
    terbalik - Kementerian tebal 14 pt dan lembaga 12 pt - sehingga
    penekanan jatuh ke baris yang seharusnya paling tenang.

    Layout dua kolom, bukan tiga. Kolom kedua tanpa lebar tetap, sehingga
    teks otomatis BERPOSISI DI PUSAT pada ruang yang tersisa di sebelah
    logo. Itulah yang dilakukan surat referensi, dan hasilnya jauh lebih
    rapi daripada memberi persentase tetap pada tiap kolom.

    Parameter (opsional):
      $kopLogoSrc  string|null             sumber logo: data URI untuk ekspor,
                                           URL untuk workstation on-screen
      $kopLogo     null|array{0:int,1:int} ukuran logo dalam piksel
                                           [lebar, tinggi] supaya rasio asli
                                           terjaga dan logo tidak terdistorsi.

    line-height memakai satuan absolut, bukan rasio. Rasio yang digabung
    dengan mso-line-height-rule:exactly menghasilkan tinggi baris berbeda antara
    Word dan LibreOffice, dan itulah sumber "jarak antar baris tidak beraturan".

    Tidak ada <colgroup>/calc(): importer Word dan LibreOffice tidak menghitung
    calc(), dan app.css memaksa .office-paper-sheet table menjadi
    width:100% !important sehingga colgroup ikut diabaikan di workstation.
--}}
@use('App\Support\DocumentLayout')

{{-- Semua angka layout berasal dari app/Support/DocumentLayout.php, yang juga
     dibaca oleh writer .docx. Nilai tidak boleh ditulis ulang di sini. --}}
@php
    $kopShowLogo = (bool) ($config['show_logo'] ?? true);
    $kopLogo = $kopLogo ?? null;
    $kopHasLogo = $kopShowLogo && is_array($kopLogo) && $kopLogo[0] > 0 && $kopLogo[1] > 0;

    $kopLogoWidthPt = 0;
    $kopLogoHeightPt = 0;

    if ($kopHasLogo) {
        $kopLogoScale = min(1.0, DocumentLayout::KOP_LOGO_WIDTH_PT / $kopLogo[0], DocumentLayout::KOP_LOGO_HEIGHT_PT / $kopLogo[1]);
        $kopLogoWidthPt = round($kopLogo[0] * $kopLogoScale, 1);
        $kopLogoHeightPt = round($kopLogo[1] * $kopLogoScale, 1);
    }

    $kopLogoWidthPx = (int) round($kopLogoWidthPt / 0.75);
    $kopLogoHeightPx = (int) round($kopLogoHeightPt / 0.75);

    $kopLogoCellWidth = $kopHasLogo ? DocumentLayout::KOP_LOGO_COLUMN : '0%';
    $kopTextCellWidth = $kopHasLogo ? DocumentLayout::KOP_TEXT_COLUMN : '100%';

    $instansiIndukDisplay = trim((string) ($config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI"));
    if ($instansiIndukDisplay === 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI') {
        $instansiIndukDisplay = "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI";
    }

    $instansiPelaksanaDisplay = trim((string) ($config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X'));
    if ($instansiPelaksanaDisplay === 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X') {
        $instansiPelaksanaDisplay = 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X';
    }

    $alamatKontakDisplay = trim((string) ($config['alamat_kontak'] ?? \App\Models\Agenda::DEFAULT_REPORT_ALAMAT_KONTAK));
    $kopRuleBorder = 'border: none; border-bottom: ' . DocumentLayout::RULE_WEIGHT_PT . 'pt solid #000000; mso-border-bottom-alt: solid windowtext ' . DocumentLayout::RULE_WEIGHT_PT . 'pt;';
    $kopLogoPad = $kopHasLogo
        ? 'padding: 0 ' . DocumentLayout::KOP_CELL_GAP_PT . 'pt ' . DocumentLayout::KOP_CELL_PAD_BOTTOM_PT . 'pt 0;'
        : 'padding: 0;';
    $kopTextPad = $kopHasLogo
        ? 'padding: 0 0 ' . DocumentLayout::KOP_CELL_PAD_BOTTOM_PT . 'pt ' . DocumentLayout::KOP_CELL_GAP_PT . 'pt;'
        : 'padding: 0 0 ' . DocumentLayout::KOP_CELL_PAD_BOTTOM_PT . 'pt 0;';
@endphp
<div class="header-kop" id="sheet-header-kop" style="display: {{ ($config['show_kop'] ?? true) ? 'block' : 'none' }}; width: {{ DocumentLayout::KOP_WIDTH_CM }}cm; margin: 0 {{ DocumentLayout::KOP_BLEED_RIGHT_CM }}cm {{ DocumentLayout::KOP_GAP_BOTTOM_PT }}pt {{ DocumentLayout::KOP_BLEED_LEFT_CM }}cm; padding: 0; text-align: center; font-family: {!! DocumentLayout::KOP_FONT_CSS !!};">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; margin: 0; padding: 0; {{ $kopRuleBorder }}">
        <tr>
            <td id="sheet-logo-cell" width="{{ $kopLogoCellWidth }}" align="center" valign="middle" style="width: {{ $kopLogoCellWidth }}; text-align: center; vertical-align: middle; {{ $kopRuleBorder }} {{ $kopLogoPad }}">
                @if($kopHasLogo)
                    <img id="sheet-logo-img" src="{{ $kopLogoSrc }}" alt="Logo Instansi" width="{{ $kopLogoWidthPx }}" height="{{ $kopLogoHeightPx }}" style="width: {{ $kopLogoWidthPt }}pt; height: {{ $kopLogoHeightPt }}pt; display: block; margin: 0 auto; border: none;">
                @endif
            </td>
            <td width="{{ $kopTextCellWidth }}" align="center" valign="middle" style="width: {{ $kopTextCellWidth }}; text-align: center; vertical-align: middle; {{ $kopRuleBorder }} {{ $kopTextPad }} font-family: {!! DocumentLayout::KOP_FONT_CSS !!};">
                <p id="sheet-instansi-induk" style="margin: 0; padding: 0; font-size: {{ DocumentLayout::MINISTRY_SIZE_PT }}pt; line-height: {{ DocumentLayout::MINISTRY_LINE_PT }}pt; mso-line-height-rule: exactly; font-weight: normal; text-transform: uppercase; font-family: {!! DocumentLayout::KOP_FONT_CSS !!};">{!! nl2br(e($instansiIndukDisplay)) !!}</p>
                <p id="sheet-instansi-pelaksana" style="margin: 1pt 0; padding: 0; font-size: {{ DocumentLayout::AGENCY_SIZE_PT }}pt; line-height: {{ DocumentLayout::AGENCY_LINE_PT }}pt; mso-line-height-rule: exactly; font-weight: bold; text-transform: uppercase; font-family: {!! DocumentLayout::KOP_FONT_CSS !!};">{{ $instansiPelaksanaDisplay }}</p>
                <p id="sheet-alamat-kontak" style="margin: 0; padding: 0; font-size: {{ DocumentLayout::ADDRESS_SIZE_PT }}pt; line-height: {{ DocumentLayout::ADDRESS_LINE_PT }}pt; mso-line-height-rule: exactly; font-weight: normal; font-style: normal; font-family: {!! DocumentLayout::KOP_FONT_CSS !!}; white-space: nowrap;">{!! nl2br(e($alamatKontakDisplay)) !!}</p>
            </td>
        </tr>
    </table>
</div>
