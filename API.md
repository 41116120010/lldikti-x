# Spesifikasi REST API v1 — SIPERAPAT (LLDIKTI Wilayah X)

Dokumen ini merupakan **Single Source of Truth (SSOT)** kontrak komunikasi *stateless* antara backend **Laravel 13** (`routes/api.php`) dan klien resmi **React Native / Android** (`siperapat`).

---

## 1. Konvensi Dasar & Keamanan

### 1.1. Base URL, Versi & Header
* **Base URL:** `{APP_URL}/api/v1` (seluruh jalur produksi wajib HTTPS).
* **Header Wajib:**
  * `Accept: application/json`
  * `Content-Type: application/json` (atau `multipart/form-data` untuk unggahan berkas).
  * `Authorization: Bearer <sanctum_token>` (wajib pada seluruh endpoint terproteksi).

### 1.2. Zona Waktu, Enum & Amplop JSON
* **Zona Waktu:** Semua stempel waktu dikembalikan dalam format ISO-8601 WIB (`+07:00`, `Asia/Jakarta`).
* **Enum Status & Peran:** Dikembalikan sebagai objek `{ "value": string, "label": string }`.
* **Amplop Standar Respons:**
```json
{
  "success": true,
  "message": "Pesan ringkas hasil operasi.",
  "data": {},
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 10
  }
}
```
* **Kesalahan Validasi (`422`):** Mengembalikan `{ "message": "...", "errors": { "field": ["..."] } }`. Token tidak valid/kedaluwarsa mengembalikan `401` dan klien wajib membersihkan penyimpanan token lokal.

### 1.3. Autentikasi Sanctum & Kebijakan Keamanan Media
* **Masa Aktif Token:** `config('sanctum.expiration')` disetel bawaan **43.200 menit (30 hari)**.
* **Pencabutan Token:** Setiap pergantian/reset kata sandi, deaktivasi akun, atau penghapusan akun mencabut seluruh token aktif pengguna (`$user->tokens()->delete()`). Khusus `/profile/password` dan `/auth/password`, server menerbitkan kembali satu token baru agar sesi perangkat saat ini tetap berjalan.
* **Proteksi Media Biometrik:** Swafoto dan tanda tangan ASN tidak dibuka melalui URL statis publik. Pengambilan gambar wajib melalui endpoint streaming terotorisasi (`AttendancePolicy::view`) disertai header `Authorization: Bearer <token>`.

### 1.4. Rate Limiting (`AppServiceProvider` & Route Middleware)

| Limiter | Batas | Kunci Pembatasan | Endpoint Terdampak |
|---|---|---|---|
| `login` | 5 req/menit | IP | `POST /auth/login` |
| `throttle:5,1` | 5 req/menit | IP | `POST /auth/forgot-password` |
| `throttle:10,1` | 10 req/menit | IP | `POST /auth/reset-password` |
| `api` | 60 req/menit | `user_id` atau IP | Seluruh grup `auth:sanctum` |
| `password` | 5 req/menit | `user_id` atau IP | `PUT /profile/password`, `PUT /auth/password` |
| `attendance` | 10 req/menit | `user_id` atau IP | `POST /agendas/{agenda}/attendances` |
| `export` | 15 req/menit | `user_id` atau IP | Seluruh endpoint `/reports/*/export/*` & `/reports/summary/csv` |

---

## 2. Daftar Endpoint REST API v1

Seluruh path berikut relatif terhadap prefix `/api/v1`.

### 2.1. Autentikasi & Sesi

| Method | Path | Akses | Deskripsi & Payload Utama |
|---|---|---|---|
| `POST` | `/auth/login` | Publik | Login multi-identifier (`login` NIP/username, `password`, `device_name`). Mengembalikan `{ token, token_type: "Bearer", expires_in_days, user }`. |
| `POST` | `/auth/forgot-password` | Publik | Kirim tautan pemulihan sandi via Brevo (`login` NIP/username/email). |
| `POST` | `/auth/reset-password` | Publik | Simpan sandi baru (`token`, `email`, `password`, `password_confirmation`). |
| `GET` | `/auth/me` | Sanctum | Profil pengguna aktif beserta `security` & `attendance_stats` (`UserResource`). |
| `POST` | `/auth/logout` | Sanctum | Mencabut token Bearer aktif saat ini. |

### 2.2. Dashboard, Profil & Log Aktivitas

| Method | Path | Akses | Deskripsi & Parameter |
|---|---|---|---|
| `GET` | `/dashboard` | Sanctum | Ringkasan statistik sesuai peran (`stats`) dan daftar rapat aktif (`active_agendas`). |
| `GET` | `/profile` | Sanctum | Data profil pengguna saat ini (`UserResource`). |
| `PUT` | `/profile` | Sanctum | Pembaruan data profil (`name`, `email`, `phone`, opsional `password` + `current_password`). |
| `PUT` | `/profile/password` | Sanctum | Ganti kata sandi (`current_password`, `password`, `password_confirmation`, opsional `device_name`); mengembalikan token rotasi baru. Alias: `PUT /auth/password`. |
| `GET` | `/profile/logs` | Sanctum | Riwayat log aktivitas pribadi (`page`, `per_page` max 50, `search`, `activity_type`). |
| `GET` | `/activity-logs` | `administrator` | Log audit seluruh instansi (`page`, `per_page` max 100, `search`, `activity_type`, `user_id`, `start_date`, `end_date`). |

### 2.3. Agenda Rapat, Surat Edaran, Notulensi & Dokumentasi

| Method | Path | Akses | Deskripsi & Parameter / Payload |
|---|---|---|---|
| `GET` | `/agendas` | Sanctum | Daftar agenda sesuai `visibleTo($user)` (`page`, `per_page` max 50, `status`, `tipe_rapat`, `unit_id`, `search`). |
| `POST` | `/agendas` | `administrator`, `admin` | Buat agenda baru (JSON atau multipart jika disertai berkas `surat_edaran` PDF/gambar maks. 5 MB). Mendukung `target_type` (`all_units`, `selected_units`, `selected_users`), `unit_ids[]`, `user_ids[]`, `pimpinan_id`, `notulis_id`, `surat_edaran_type` (`upload`/`digital`). |
| `GET` | `/agendas/{agenda}` | Policy `view` / `viewStaff` | Detail lengkap agenda termasuk relasi peserta, notulensi tersanitasi, dokumentasi, dan objek `permissions`. |
| `PUT` / `POST` | `/agendas/{agenda}` | Policy `update` | Pembaruan agenda (gunakan `POST` + `_method=PUT` untuk unggahan multipart). |
| `DELETE` | `/agendas/{agenda}` | Policy `delete` | Hapus agenda beserta lampirannya. |
| `PATCH` | `/agendas/{agenda}/status` | Policy `manageStatus` | Ubah status agenda sesuai state machine `ALLOWED_TRANSITIONS` (`draft`, `scheduled`, `ongoing`, `completed`, `cancelled`). Pembukaan `ongoing` divalidasi minimal 15 menit sebelum `waktu_mulai`. |
| `PATCH` | `/agendas/{agenda}/roles` | Policy `update` | Ubah penugasan `pimpinan_id` & `notulis_id`. |
| `GET` | `/agendas/{agenda}/surat-edaran` | Policy `view` / `viewStaff` | Streaming biner berkas `surat_edaran` (PDF/gambar). |
| `POST` | `/agendas/{agenda}/surat-edaran` | Policy `manageCircular` | Unggah/ganti berkas surat edaran (`surat_edaran`: `pdf,jpg,jpeg,png,webp`, maks. 5 MB). |
| `GET` | `/agendas/{agenda}/surat-edaran/digital` | Policy `view` / `viewStaff` | Membaca konfigurasi dan isi HTML surat edaran digital. |
| `PUT` / `POST` | `/agendas/{agenda}/surat-edaran/digital` | Policy `manageCircular` | Menyimpan naskah surat edaran digital (`template_type`, `nomor_surat`, `perihal`, `tanggal_surat`, `tujuan_surat`, `signer_role`, `signer_name`, dll.). |
| `GET` | `/agendas/{agenda}/notulen` | Policy `view` / `viewStaff` | Mengambil data notulensi, kesimpulan, konfigurasi laporan, dan daftar dokumentasi. |
| `PUT` / `POST` | `/agendas/{agenda}/notulen` | Policy `manageMinutes` | Menyimpan `notulensi` dan `kesimpulan` (HTML disanitasi) serta opsional lampiran foto multipart. |
| `POST` | `/agendas/{agenda}/documentations` | Policy `manageMinutes` | Unggah foto dokumentasi (`photo` atau `photos[]` maks. 10 berkas, `jpg,jpeg,png,webp`, masing-masing maks. 4 MB; disertai `caption` / `captions[]`). |
| `DELETE` | `/agendas/{agenda}/documentations/{documentation}` | Policy `manageMinutes` | Menghapus satu foto dokumentasi rapat. |

### 2.4. Presensi Kehadiran & Streaming Media Biometrik

| Method | Path | Akses | Deskripsi & Parameter / Payload |
|---|---|---|---|
| `GET` | `/attendances/portal` | Sanctum | Feed agenda `ongoing` yang berhak dihadiri pengguna saat ini. |
| `GET` | `/agendas/{agenda}/attendance-portal` | Policy `viewStaff` | Status kelayakan & riwayat presensi pribadi pada satu agenda (`is_ongoing`, `is_eligible`, `has_attended`, `my_attendance`). |
| `POST` | `/agendas/{agenda}/attendances` | Policy `attend` | Pengiriman presensi (`selfie_file` atau `selfie_data` base64 maks. 512 KB; `signature_file` atau `signature_data` maks. 512 KB; MIME `jpeg,jpg,png,webp`). Diblokir jika agenda bukan `ongoing` atau peserta sudah hadir (`409`/`422`). |
| `GET` | `/agendas/{agenda}/attendances` | Policy `view` | Daftar peserta hadir & belum hadir terpaginasi (`page`, `per_page` max 100, `status`: `attended`/`unattended`, `search`). |
| `GET` | `/attendances/my-history` | Sanctum | Riwayat presensi pribadi (`page`, `per_page` max 50, `search`, `period`, `start_date`, `end_date`, `tipe_rapat`). |
| `GET` | `/attendances/{attendance}` | Policy `view` | Detail satu rekaman kehadiran (`AttendanceResource`). Alias: `GET /attendances/{attendance}/receipt`. |
| `GET` | `/attendances/{attendance}/selfie` | Policy `view` | Streaming biner foto swafoto peserta dengan header `Content-Type` gambar. |
| `GET` | `/attendances/{attendance}/signature` | Policy `view` | Streaming biner gambar tanda tangan digital peserta. |

### 2.5. Pengguna, Unit Kerja & Laporan

| Method | Path | Akses | Deskripsi & Parameter / Payload |
|---|---|---|---|
| `GET` | `/users/candidates` | `administrator`, `admin` | Daftar pegawai ASN aktif lintas seluruh unit untuk dropdown penugasan Pemimpin Rapat & Notulis. |
| `GET` | `/users` | `administrator`, `admin` | Daftar pegawai terpaginasi (`page`, `per_page` max 50, `search`, `role`, `status`, `unit_id`) + statistik ringkas. |
| `POST` | `/users` | Policy `create` | Tambah pegawai baru (`name`, `nip`, `username`, `email`, `role`, `unit_id`, `phone`, `is_active`, `password` opsional dengan fallback `Password123!`). |
| `GET` / `PUT` / `DELETE` | `/users/{user}` | `UserPolicy` | Detail, ubah, dan hapus akun pengguna sesuai batasan unit/peran. |
| `PATCH` | `/users/{user}/toggle-status` | Policy `update` | Aktif/nonaktifkan akun (sekaligus mencabut token saat dinonaktifkan). |
| `GET` | `/units` | Sanctum (Terbatas pada write) | Daftar unit kerja (`all=true` untuk seluruh daftar dropdown tanpa paginasi, `status`, `search`, `page`, `per_page` max 50). |
| `POST` / `GET` / `PUT` / `DELETE` | `/units`, `/units/{unit}` | `UnitPolicy` (`administrator` untuk mutasi) | Pengelolaan master data unit kerja. |
| `PATCH` | `/units/{unit}/toggle-status` | `administrator` | Aktif/nonaktifkan unit kerja. |
| `GET` | `/reports/summary` | `administrator`, `admin`, `staff` | Ringkasan analitik rapat sesuai cakupan akses (`period`, `start_date`, `end_date`, `status`, `unit_id`) memuat `format_stats`, `governance_rate`, `count_with_notulen`, `attendance_rate`, `unit_stats`, `member_stats`. |
| `GET` | `/reports/agendas` | `administrator`, `admin`, `staff` | Tabel daftar rekap agenda terpaginasi (`page`, `per_page` max 50). |
| `GET` | `/reports/summary/csv` | Policy laporan | Unduh rekapitulasi CSV 13 kolom berbahasa Indonesia (UTF-8 BOM + proteksi CWE-1236). |
| `GET` | `/reports/agendas/{agenda}` | Policy `view` / `viewStaff` | Rekapitulasi lengkap satu agenda beserta daftar hadir dan `export_links`. |
| `POST` | `/reports/agendas/{agenda}/config/reset` | Policy `manageMinutes` | Reset `report_config` agenda ke setelan bawaan instansi. |
| `GET` / `POST` | `/reports/agendas/{agenda}/export/pdf` | Policy laporan | Unduh Berita Acara PDF A4 (mendukung override parameter `report_config` via `POST`). |
| `GET` / `POST` | `/reports/agendas/{agenda}/export/word` | Policy laporan | Unduh Berita Acara `.docx` OpenXML (mendukung override parameter `report_config` via `POST`). |
| `GET` | `/reports/agendas/{agenda}/export/csv` | Policy laporan | Unduh CSV daftar hadir satu agenda. |

---

## 3. Ketentuan Teknis Integrasi Klien Mobile

1. **Penyimpanan Token & Keamanan Log:** Klien mobile menyimpan Bearer token menggunakan `expo-secure-store` (Android/iOS Keychain/Keystore) serta tidak mencetak header `Authorization`, kata sandi, atau body sensitif ke console log.
2. **Kompresi Swafoto Sebelum Unggah:** Kamera aplikasi (`expo-camera` / galeri) wajib mengubah ukuran & mengompresi foto di sisi perangkat (`expo-image-manipulator`: target lebar `600px`, JPEG quality `0.75`, dan fallback `480px` quality `0.5` apabila perkiraan payload melebihi 512 KB).
3. **Pengiriman Multipart & Binary Download:** Pengunggahan berkas surat edaran/foto dokumentasi memakai `FormData` dengan MIME type terdeteksi; pengunduhan PDF/DOCX/CSV ke perangkat dilakukan dengan `FileSystem.downloadAsync` disertai header `Authorization: Bearer` dan dibagikan via `expo-sharing`.
4. **Rujukan Detail Klien:** Arsitektur komponen, daftar layar, dan matriks keselarasan aktual didokumentasikan pada repositori `siperapat` (`README.md` dan `docs/api-contract-alignment.md`).
