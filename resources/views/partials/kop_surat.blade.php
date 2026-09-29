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
@php
    $kopShowLogo = (bool) ($config['show_logo'] ?? true);
    $kopLogo = $kopLogo ?? null;
    $kopHasLogo = $kopShowLogo && is_array($kopLogo) && $kopLogo[0] > 0 && $kopLogo[1] > 0;

    // Logo muat di dalam kotak 2,8 cm pada kedua sisi, rasio asli terjaga.
    // Ketiga nilai turunan hanya boleh dihitung ketika logo benar-benar ada:
    // saat logo dimatikan, $kopLogo bernilai null dan setiap pembacaan indeksnya
    // akan melempar galat.
    $kopLogoBoxPt = 79.4;
    $kopLogoWidthPt = 0;
    $kopLogoHeightPt = 0;

    if ($kopHasLogo) {
        $kopLogoScale = min(1.0, $kopLogoBoxPt / max($kopLogo[0], $kopLogo[1]));
        $kopLogoWidthPt = round($kopLogo[0] * $kopLogoScale, 1);
        $kopLogoHeightPt = round($kopLogo[1] * $kopLogoScale, 1);
    }

    // 1 px pada 96 dpi setara 0.75 pt. Atribut width/height dipakai importer
    // Word, yang mengabaikan object-fit.
    $kopLogoWidthPx = (int) round($kopLogoWidthPt / 0.75);
    $kopLogoHeightPx = (int) round($kopLogoHeightPt / 0.75);

    // Sel logo diberi lebar logo ditambah celah. Saat logo dimatikan sel ini
    // menjadi nol lebar agar blok teks tepat di sumbu halaman.
    //
    // Kedua kolom memakai PERSENTASE, bukan lebar tetap. Dengan table-layout
    // fixed, persentase dihitung terhadap lebar tabel sehingga blok teks
    // selalu urging di ruang yang tersisa di sebelah logo, berapa pun nilai
    // margin halaman. Angka tetap membuat kolom meluber begitu margin berubah.
    $kopLogoCellWidth = $kopHasLogo ? '19%' : '0%';
    $kopTextCellWidth = $kopHasLogo ? '81%' : '100%';
@endphp
<div class="header-kop" id="sheet-header-kop" style="display: {{ ($config['show_kop'] ?? true) ? 'block' : 'none' }}; margin: 0; padding: 0; text-align: center; width: 100%;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; margin: 0; padding: 0;">
        <tr>
            <td id="sheet-logo-cell" width="{{ $kopLogoCellWidth }}" align="center" valign="middle" style="width: {{ $kopLogoCellWidth }}; text-align: center; vertical-align: middle; border: none; padding: 0;">
                @if($kopHasLogo)
                    <img id="sheet-logo-img" src="{{ $kopLogoSrc }}" alt="Logo Instansi" width="{{ $kopLogoWidthPx }}" height="{{ $kopLogoHeightPx }}" style="width: {{ $kopLogoWidthPt }}pt; height: {{ $kopLogoHeightPt }}pt; display: block; margin: 0 auto; border: none;">
                @endif
            </td>
            <td width="{{ $kopTextCellWidth }}" align="center" valign="middle" style="width: {{ $kopTextCellWidth }}; text-align: center; vertical-align: middle; border: none; padding: 0;">
                <p id="sheet-instansi-induk" style="margin: 0; padding: 0; font-size: 16pt; line-height: 19pt; mso-line-height-rule: exactly; font-weight: normal; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}</p>
                <p id="sheet-instansi-pelaksana" style="margin: 0; padding: 0; font-size: 14pt; line-height: 17pt; mso-line-height-rule: exactly; font-weight: bold; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}</p>
                <p id="sheet-alamat-kontak" style="margin: 2pt 0 0 0; padding: 0; font-size: 12pt; line-height: 14pt; mso-line-height-rule: exactly; font-weight: normal; font-style: normal; font-family: 'Times New Roman', Times, serif;">{{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat' }}</p>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border: none; border-bottom: 1pt solid #000000; mso-border-bottom-alt: solid windowtext 1pt; height: 0; font-size: 1pt; line-height: 0; mso-line-height-rule: exactly; padding: 3pt 0 0 0;">&#8203;</td>
        </tr>
    </table>
</div>
