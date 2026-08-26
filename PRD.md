# Product Requirements Document (PRD)
## Sistem Pencatatan Kehadiran Rapat Berbasis Web (SIPERAPAT)
**Instansi:** Lembaga Layanan Pendidikan Tinggi (LLDIKTI)  
**Versi Dokumen:** 1.0.0  
**Status:** Approved for Development  
**Tanggal:** 26 Agustus 2026  
**Tech Stack Utama:** Laravel 12+ (PHP 8.3+), Tailwind CSS v4, Blade Engine, Vanilla JS / Alpine.js, MySQL / PostgreSQL  

---

## 1. Executive Summary & Latar Belakang

Sistem Pencatatan Kehadiran Rapat (SIPERAPAT) adalah platform enterprise berbasis web yang dirancang khusus untuk memenuhi kebutuhan instansi pemerintahan (LLDIKTI) dalam mencatat, mengelola, dan mendokumentasikan kehadiran rapat kedinasan secara akurat, transparan, dan terintegrasi.

Tantangan utama yang diselesaikan oleh sistem ini:
1. **Integritas Data Kehadiran:** Menggantikan daftar hadir manual berbasis kertas dengan verifikasi ganda (Selfie Kamera Wajah + Tanda Tangan Digital pada layar).
2. **Fleksibilitas Akses:** Kemudahan login dengan 2 metode (NIP 18 digit atau Username) dalam 1 form input tunggal.
3. **Akuntabilitas & Audit Trail:** Setiap aktivitas perubahan data dan pencatatan presensi terekam dalam Log Aktivitas institusi.
4. **Sentralisasi Notulensi & Dokumentasi:** Pengarsipan berkas surat edaran/undangan, notulensi rapat, kesimpulan, dan foto dokumentasi kegiatan dalam satu pintu.
5. **Kesiapan Administrasi:** Ekspor rekapitulasi kehadiran dan berita acara rapat ke format PDF (siap cetak dengan Kop Surat) dan Word (siap sunting).

---

## 2. Sasaran & Parameter Keberhasilan (Success Metrics)

* **Durabilitas & Ketersediaan:** Uptime sistem target 99.9%, tahan terhadap lonjakan akses bersamaan (100–300 peserta presensi dalam rentang 15 menit).
* **Efisiensi Bandwidth & Storage (Zero-Bloat):** File selfie dikompresi di sisi browser (*client-side canvas compression*) sebelum dikirim, menjaga ukuran payload < 150 KB per request.
* **Kecepatan Respons:** Waktu respon API/Halaman rata-rata < 200 ms pada koneksi intranet/internet standar instansi.
* **Aksesibilitas & Kerapian UI:** Standar WCAG 2.1 AA, antarmuka bertema **Modern Gov-Tech** yang ramah digunakan oleh seluruh aparatur sipil negara (ASN) dari berbagai generasi.

---

## 3. Matriks Peran & Hak Akses Pengguna (RBAC)

| Modul / Fitur | Administrator (Superadmin) | Admin (Kepala / PIC Unit) | Staff (Pegawai Unit) |
| :--- | :---: | :---: | :---: |
| **Login Multi-Identifier (NIP / Username)** | ✅ | ✅ | ✅ |
| **Kelola Master Data Unit Kerja** | ✅ (Full CRUD) | ❌ | ❌ |
| **Kelola Pengguna (User Management)** | ✅ (Semua Unit) | ✅ (Hanya Unit Sendiri) | ❌ (Hanya Profil Pribadi) |
| **Buat & Kelola Agenda Rapat** | ✅ (Universal / Semua Unit) | ✅ (Unit Sendiri / Lintas Unit) | ❌ |
| **Upload Surat Edaran / Undangan** | ✅ | ✅ | ❌ |
| **Input Notulensi, Kesimpulan, Foto Dokumentasi** | ✅ | ✅ | ❌ |
| **Presensi Rapat (Selfie + Tanda Tangan Digital)** | ✅ | ✅ | ✅ |
| **Lihat Riwayat Presensi Pribadi** | ✅ | ✅ | ✅ |
| **Lihat Rekap & Monitoring Kehadiran Rapat** | ✅ (Semua Agenda) | ✅ (Agenda Unit / Terkait) | ❌ |
| **Ekspor Laporan (PDF & Word)** | ✅ | ✅ | ❌ |
| **Lihat & Filter Log Aktivitas Sistem** | ✅ (Seluruh Sistem) | ❌ | ❌ |

---

## 4. Spesifikasi Fungsional Terperinci

### 4.1. Modul Autentikasi & Keamanan Akun
* **Input Universal:** Single input field menerima `NIP` (18 digit angka) atau `Username` (alfanumerik).
* **Password Policy:** Wajib memenuhi standar kuat (minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka/simbol).
* **Proteksi Brute Force:** Rate limiter Laravel (maksimal 5 kali kegagalan login per menit per IP/akun sebelum *cooldown* 60 detik).
* **Reset Password:** Alur pengiriman email token reset password dengan masa kedaluwarsa 60 menit.

### 4.2. Modul Kelola Unit Kerja (Administrator Only)
* Nama Unit (misal: *Bagian Umum*, *Pokja Akademik & Kemahasiswaan*, *Pokja Kelembagaan*, dsb.).
* Kode Unit (singkatan unik, contoh: `POKJA-AKM`, `BAG-UMUM`).
* Status Keaktifan (Aktif / Non-aktif).

### 4.3. Modul Kelola Pengguna (User Management)
* **Atribut Pengguna:** Nama Lengkap (dengan gelar), NIP, Username, Email, Unit Kerja (Relasi ke Unit), Role (`administrator`, `admin`, `staff`), Status Akun.
* **Scoping Rule:**
  * Administrator dapat menambah, melihat, mengedit, dan me-nonaktifkan seluruh pengguna dari semua unit.
  * Admin Unit hanya dapat mengelola data pengguna yang berada di bawah naungan unit kerjanya sendiri. Role yang bisa dibuat oleh Admin Unit terbatas pada `Staff` atau `Admin` tambahan untuk unit tersebut.

### 4.4. Modul Manajemen Agenda Rapat
* **Informasi Dasar:**
  * Judul / Nama Rapat
  * Jenis Rapat (Rapat Koordinasi, Rapat Pleno, Evaluasi, Konsinyasi, Rapat Terbatas)
  * Format Penyelenggaraan: *Tatap Muka (Offline)*, *Daring (Online)*, atau *Hybrid*
  * Lokasi / Ruang Rapat (jika Offline/Hybrid)
  * Tautan Meeting / Zoom / GMeet (jika Online/Hybrid)
  * Waktu Mulai & Waktu Selesai (dengan validasi rentang waktu)
* **Target Partisipan (Audience Scope):**
  * Terbuka untuk Seluruh Unit (Pleno/Universal)
  * Terbuka untuk Unit-Unit Tertentu (Multi-select unit)
* **Berkas Surat Edaran / Undangan:**
  * Upload berkas opsional (PDF, JPG, PNG; max 5 MB).
  * Dilengkapi tombol pratinjau (*embedded viewer*) dan unduh bagi peserta.
* **Status Siklus Agenda:**
  * `Draft` (Belum dipublikasikan)
  * `Scheduled` (Terjadwal)
  * `Ongoing` (Rapat Sedang Berlangsung / Presensi Dibuka)
  * `Completed` (Rapat Selesai / Presensi Ditutup)

### 4.5. Modul Presensi Interaktif (Selfie & Tanda Tangan)
* **Pencegahan Fraud & Validasi:**
  * Pegawai hanya dapat melakukan presensi pada agenda yang berstatus `Ongoing` dan unit kerjanya termasuk dalam daftar undangan.
  * Validasi *One-Time Check-in*: Pegawai tidak dapat melakukan presensi ganda pada agenda yang sama.
* **Perekaman Kamera Wajah (Selfie):**
  * Akses WebRTC `navigator.mediaDevices.getUserMedia` dengan interface *live preview* berbentuk oval frame.
  * Fallback otomatis ke `<input type="file" accept="image/*" capture="user">` jika browser tidak mendukung WebRTC stream.
  * **Client-Side Compression:** Gambar di-render ke Canvas HTML5 dan diekspor sebagai JPEG/WebP dengan resolusi optimal (maks 600x800px, kualitas 0.75, ukuran berkas < 150 KB).
* **Tanda Tangan Digital (Canvas Signature Pad):**
  * Kanvas interaktif dengan dukungan *touch-event* di layar smartphone/tablet dan *mouse-event* di desktop.
  * Tombol "Bersihkan / Ulangi" dan "Konfirmasi".
  * Output disimpan dalam format PNG transparan berukuran ringan (< 30 KB).
* **Metadata Tambahan:** Sistem secara otomatis merekam *IP Address*, *User-Agent / Device Info*, dan *Timestamp Presensi* (`signed_at`).

### 4.6. Modul Notulensi, Kesimpulan, & Dokumentasi Rapat
* Form input teks terstruktur untuk:
  * **Notulensi Jalannya Rapat** (Poin-poin pembahasan)
  * **Kesimpulan & Tindak Lanjut** (Action items & penanggung jawab)
* **Foto Dokumentasi:** Unggah multi-foto kegiatan rapat (maksimal 5 foto per agenda, format JPG/PNG/WebP, batas 2 MB per foto).

### 4.7. Modul Ekspor Laporan & Berita Acara
* **Ekspor PDF (Official Printable):**
  * Dilengkapi Kop Resmi LLDIKTI.
  * Ringkasan metadata agenda (Judul, Waktu, Tempat, Pemimpin Rapat).
  * Tabel Rekapitulasi Daftar Hadir (Nomor, Nama Lengkap, NIP, Unit Kerja, Jam Hadir, Thumbnail Selfie, dan Gambar Tanda Tangan).
  * Lembar Notulensi, Kesimpulan, dan Lampiran Foto Dokumentasi.
* **Ekspor Word (.docx):**
  * Menghasilkan dokumen Microsoft Word yang siap diedit untuk kebutuhan laporan administrasi ke pimpinan/kementerian.

### 4.8. Modul Audit Trail (Log Aktivitas)
* Merekam seluruh tindakan mutasi data penting:
  * `AUTH_LOGIN`, `AUTH_LOGOUT`
  * `CREATE_AGENDA`, `UPDATE_AGENDA`, `DELETE_AGENDA`
  * `RECORD_ATTENDANCE`
  * `UPDATE_USER`, `DELETE_USER`
  * `EXPORT_REPORT_PDF`, `EXPORT_REPORT_WORD`
* Tabel log menampilkan: Waktu kejadian, Nama Aktor, NIP/Role, Unit Kerja, Tipe Aktivitas, Deskripsi, dan Alamat IP.

---

## 5. Standar Desain UI/UX & Antarmuka

* **Gaya Visual:** *Modern Institutional / Clean Gov-Tech*.
* **Palet Warna:**
  * **Primary (Institusi):** Deep Navy Blue (`#0F172A` / `#1E3A8A`)
  * **Secondary / Surface:** Slate Neutral (`#F8FAFC`, `#F1F5F9`, `#E2E8F0`)
  * **Success / Hadir:** Emerald Green (`#059669`)
  * **Warning / Ongoing:** Amber (`#D97706`)
  * **Danger / Batal:** Rose Red (`#E11D48`)
* **Tipografi:** Sans-Serif modern & sangat mudah dibaca (*Inter* / *Plus Jakarta Sans* / *System UI Font*).
* **Responsivitas:** 100% Mobile-first responsive (dioptimalkan untuk layar ponsel ASN 375px hingga monitor pimpinan 1920px).
* **Ikonografi:** Ikon vektor SVG konsisten (Phosphor Icons / Heroicons). Tanpa emoji struktural pada tombol kendali.

---

## 6. Persyaratan Non-Fungsional (NFR)

1. **Security:**
   * CSRF protection pada seluruh request formulir.
   * XSS sanitization pada input rich-text/notulensi.
   * File upload validation (MIME-type check, ekstensi berkas, dan sanitasi nama file dengan hash acak).
2. **Performance:**
   * Tidak menyimpan raw base64 berukuran besar pada kolom database. Seluruh media disimpan di filesystem dengan symlink terproteksi.
   * Query database dioptimalkan dengan *eager loading* (`with(['unit', 'attendances'])`) untuk mencegah problem *N+1 query*.
3. **Maintainability:**
   * Mengikuti standar PSR-12 dan Clean Laravel Architecture.
   * Tidak menggunakan dependensi/package pihak ketiga yang tidak esensial (*YAGNI & Ponytail principle*).
