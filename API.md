# API.md — Spesifikasi REST API SIPERAPAT (LLDIKTI)
## Panduan Integrasi Klien Mobile (React Native / Android)

Dokumen ini merupakan kontrak resmi antarmuka pemrograman aplikasi (REST API v1) untuk sistem **SIPERAPAT** (Sistem Pencatatan Kehadiran Rapat LLDIKTI Wilayah X). Dokumen ini ditujukan sebagai referensi teknis komprehensif bagi pengembang aplikasi mobile Android (React Native).

---

## 1. Konvensi Dasar & Standar Arsitektur

### 1.1. Base URL & Versi
* **Base URL Pengembangan:** `http://<SERVER_IP>:8000/api/v1`
* **Base URL Produksi:** `https://<DOMAIN_INSTANSI>/api/v1`
* Seluruh endpoint diawali dengan prefix `/api/v1`.

### 1.2. Headers Standar
Setiap permintaan HTTP dari aplikasi mobile wajib menyertakan:
```http
Accept: application/json
Content-Type: application/json
```
Untuk endpoint upload berkas multipart (presensi, avatar, surat edaran), gunakan:
```http
Accept: application/json
Content-Type: multipart/form-data
```
Untuk endpoint terautentikasi, sertakan header Bearer Token:
```http
Authorization: Bearer <PERSONAL_ACCESS_TOKEN>
```

### 1.3. Format Format Waktu & Zona Waktu
* Seluruh format penanggalan dan waktu input/output menggunakan format **ISO-8601** lengkap dengan offset zona waktu Waktu Indonesia Barat (`+07:00`).
* Contoh: `2026-10-07T09:00:00+07:00`.

### 1.4. Format Enum & Status
Enum disajikan dalam bentuk objek `{ "value": string, "label": string }` agar klien mobile dapat langsung menampilkan label bahasa Indonesia resmi tanpa *hardcoding* di klien.
Contoh:
```json
{
  "value": "ongoing",
  "label": "Sedang Berlangsung (Presensi Dibuka)"
}
```

### 1.5. Amplop Respons Standar (Standard JSON Envelope)
Seluruh respons API menggunakan struktur amplop seragam:

#### Respons Berhasil (200 OK / 201 Created):
```json
{
  "success": true,
  "message": "Operasi berhasil dieksekusi.",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 68
  }
}
```

#### Respons Validasi Gagal (422 Unprocessable Entity):
```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "selfie_file": ["Ukuran berkas Foto Wajah Selfie maksimal 512 KB."],
    "signature_file": ["Berkas Tanda Tangan Digital wajib diunggah."]
  }
}
```

#### Respons Tidak Terautentikasi (401 Unauthorized):
```json
{
  "message": "Unauthenticated."
}
```

#### Respons Dilarang / Akses Ditolak (403 Forbidden):
```json
{
  "message": "Anda tidak memiliki hak akses untuk melakukan aksi ini."
}
```

---

## 2. Keamanan Media Biometrik ASN (Wajib Diperhatikan)

Sesuai aturan keamanan instansi (**AGENTS.md §3.2 & §3.6**):
1. **Dilarang Mengakses Berkas Statis Publik:** Foto selfie wajah dan tanda tangan digital ASN **tidak dilayani** melalui URL `/storage/...` publik terbuka.
2. **Streaming Endpoint Terproteksi:** Klien mobile wajib mengunduh atau menampilkan foto selfie/tanda tangan melalui endpoint streaming terautentikasi:
   - `GET /api/v1/attendances/{id}/selfie`
   - `GET /api/v1/attendances/{id}/signature`
3. **Penyajian di React Native:**
   Pada komponen `<Image />` React Native, sertakan header autentikasi:
   ```javascript
   <Image
     source={{
       uri: attendance.selfie_url,
       headers: {
         Authorization: `Bearer ${userToken}`,
       },
     }}
     style={{ width: 120, height: 160, borderRadius: 8 }}
   />
   ```
4. **Header Cache Proteksi:** Server menyertakan `Cache-Control: private, no-cache, no-store, must-revalidate` untuk mencegah penyimpanan foto biometrik di proxy jaringan atau cache publik.

---

## 3. Batas Rate Limiting (Named Throttling)

* **Login:** Maksimal 5 percobaan gagal per menit per identitas/IP (`throttle:login`).
* **Presensi Check-In:** Maksimal 30 percobaan per menit per user (`throttle:attendance`).
* **Ekspor Dokumen:** Maksimal 5 permintaan per menit per user (`throttle:export`).
* **General API:** Maksimal 60 permintaan per menit per token (`throttle:api`).

---

## 4. Daftar Lengkap Endpoint API

### 4.1. Autentikasi (Authentication)

#### 1. Login Multi-Identifier
* **Endpoint:** `POST /api/v1/auth/login`
* **Middleware:** `throttle:login`
* **Request Body (JSON):**
  ```json
  {
    "identifier": "197012051992032002",
    "password": "Password123!",
    "device_name": "Samsung Galaxy S23"
  }
  ```
  *(Catatan: Input `identifier` otomatis mendeteksi apakah berupa NIP 18-digit atau Username).*
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Autentikasi berhasil.",
    "data": {
      "token": "1|qXyZ...",
      "token_type": "Bearer",
      "expires_in_days": 30,
      "user": {
        "id": 1,
        "name": "Afdalisma, SH, M.Pd",
        "nip": "197012051992032002",
        "username": "afdalisma",
        "email": "afdalisma@lldiktiwilayahx.kemdiktisaintek.go.id",
        "phone": null,
        "avatar_url": null,
        "role": {
          "value": "administrator",
          "label": "Administrator"
        },
        "is_active": true,
        "unit_id": null,
        "unit": null,
        "created_at": "2026-10-01T08:00:00+07:00",
        "updated_at": "2026-10-01T08:00:00+07:00"
      }
    }
  }
  ```

#### 2. Profil Pengguna Saat Ini
* **Endpoint:** `GET /api/v1/auth/me`
* **Headers:** `Authorization: Bearer <token>`
* **Response (200 OK):** Mengembalikan data user yang sedang login beserta unit kerja.

#### 3. Logout & Cabut Token
* **Endpoint:** `POST /api/v1/auth/logout`
* **Headers:** `Authorization: Bearer <token>`
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Berhasil keluar dan token dicabut."
  }
  ```

---

### 4.2. Dashboard & Ringkasan

#### 1. Ringkasan Dashboard
* **Endpoint:** `GET /api/v1/dashboard`
* **Headers:** `Authorization: Bearer <token>`
* **Deskripsi:** Mengembalikan metrik telemetri kinerja kehadiran, daftar agenda aktif/terdekat untuk dashboard, dan riwayat presensi terbaru pengguna yang diautentikasi.
* **Response untuk Pegawai (Staff ASN) (200 OK):**
  ```json
  {
    "success": true,
    "message": "Data dashboard berhasil diambil.",
    "data": {
      "stats": {
        "total_agendas": 12,
        "ongoing_agendas": 1,
        "completed_agendas": 9,
        "upcoming_agendas": 2,
        "my_attendances": 8,
        "attendance_rate": 88.9,
        "attendance_rate_label": "88.9%",
        "attendance_rate_subtext": "Dari 9 Rapat Selesai"
      },
      "active_agendas": [ ... ],
      "recent_attendances": [ ... ]
    }
  }
  ```
* **Response untuk Administrator (200 OK):**
  ```json
  {
    "success": true,
    "message": "Data dashboard berhasil diambil.",
    "data": {
      "stats": {
        "total_agendas": 12,
        "ongoing_agendas": 1,
        "completed_agendas": 9,
        "upcoming_agendas": 2,
        "total_users": 79,
        "total_units": 12,
        "total_attendances": 142,
        "attendance_rate": 91.2,
        "attendance_rate_label": "91.2%",
        "attendance_rate_subtext": "Rata-rata Seluruh ASN (9 Rapat)"
      },
      "active_agendas": [ ... ],
      "recent_attendances": [ ... ]
    }
  }
  ```
* **Response untuk Admin Unit Kerja (200 OK):**
  ```json
  {
    "success": true,
    "message": "Data dashboard berhasil diambil.",
    "data": {
      "stats": {
        "total_agendas": 12,
        "ongoing_agendas": 1,
        "completed_agendas": 9,
        "upcoming_agendas": 2,
        "total_users": 8,
        "total_attendances": 32,
        "attendance_rate": 85.0,
        "attendance_rate_label": "85.0%",
        "attendance_rate_subtext": "Kehadiran Unit (8 Pegawai)"
      },
      "active_agendas": [ ... ],
      "recent_attendances": [ ... ]
    }
  }
  ```

#### 2. Kamus Properti Statistik (`stats`)
| Properti | Tipe | Deskripsi & Peran Pengguna |
| :--- | :--- | :--- |
| `total_agendas` | `integer` | Total seluruh agenda rapat dinas yang terlihat oleh pengguna. |
| `ongoing_agendas` | `integer` | Jumlah rapat dinas yang saat ini sedang berlangsung (`status = ongoing`). |
| `upcoming_agendas` | `integer` | Jumlah rapat dinas terjadwal mendatang (`status = scheduled`). |
| `completed_agendas` | `integer` | Jumlah rapat dinas yang telah selesai dilaksanakan (`status = completed`). |
| `my_attendances` | `integer` | *(Khusus Staff)* Jumlah seluruh presensi kehadiran yang tercatat atas nama pengguna. |
| `total_users` | `integer` | *(Admin/Administrator)* Total pengguna aktif (instansi atau unit terkait). |
| `total_units` | `integer` | *(Khusus Administrator)* Total unit kerja resmi yang terdaftar di instansi. |
| `total_attendances` | `integer` | *(Admin/Administrator)* Total presensi ASN (instansi atau unit terkait). |
| `attendance_rate` | `float` | Persentase tingkat kehadiran ASN (0.0 - 100.0). |
| `attendance_rate_label` | `string` | Format string siap saji untuk kartu metrik UI (contoh: `"88.9%"` atau `"100.0%"`). |
| `attendance_rate_subtext` | `string` | Keterangan deskriptif (contoh: `"Dari 9 Rapat Selesai"` atau `"Belum ada rapat dinas selesai"`). |

#### 3. Panduan Integrasi Klien Mobile (React Native / Android)

##### A. Pemetaan Komponen BentoGrid Telemetri
* **Kartu 1 (Presensi Rapat):**
  * Nilai Utama (`value`): `stats.my_attendances ?? stats.total_attendances ?? '0'`
  * Sub-nilai (`subValue`): `'Tercatat'`
* **Kartu 2 (Jadwal Mendatang):**
  * Nilai Utama (`value`): `String(stats.upcoming_agendas ?? 0)`
  * Sub-nilai (`subValue`): `'Agenda'`
* **Kartu 3 (Tingkat Kehadiran):**
  * Nilai Utama (`value`): `stats.attendance_rate_label ?? (stats.attendance_rate !== undefined ? `${stats.attendance_rate}%` : '—')`
  * Sub-nilai (`subValue`): `stats.attendance_rate_subtext ?? 'Tidak tersedia'`

##### B. Pemetaan Tab Kategori Rapat (Horizontal Category Pills)
Setiap tab pada daftar rapat dinas dihubungkan dengan endpoint `GET /api/v1/agendas`:
* **Tab "Semua":**
  * Counter Badge: `stats.total_agendas`
  * Panggilan API: `GET /api/v1/agendas?page=1`
* **Tab "Berlangsung":**
  * Counter Badge: `stats.ongoing_agendas`
  * Panggilan API: `GET /api/v1/agendas?status=ongoing&page=1`
* **Tab "Mendatang":**
  * Counter Badge: `stats.upcoming_agendas`
  * Panggilan API: `GET /api/v1/agendas?status=scheduled&page=1`
* **Tab "Selesai":**
  * Counter Badge: `stats.completed_agendas`
  * Panggilan API: `GET /api/v1/agendas?status=completed&page=1`

##### C. Tombol Aksi pada Kartu Rapat Aktif (`active_agendas`)
* **Tombol "Buka PDF Undangan":** Membuka URL `agenda.surat_edaran.url` jika tersedia (bernilai `null` jika tidak diunggah).
* **Tombol "Join Meeting":** Membuka tautan video conference `agenda.link_meeting` (Zoom, Google Meet, dll.).
* **Tombol "Presensi Sekarang":** Aktif secara otomatis saat `agenda.status.value === 'ongoing'` dan `agenda.is_eligible === true` serta `agenda.has_attended === false`.


---

### 4.3. Agenda Rapat (Agendas)

#### 1. Daftar Agenda Rapat
* **Endpoint:** `GET /api/v1/agendas`
* **Headers:** `Authorization: Bearer <token>`
* **Query Parameters:**
  - `status`: `draft`, `scheduled`, `ongoing`, `completed`, `cancelled`
  - `tipe_rapat`: `offline` (luring), `online` (daring), `hybrid`
  - `jenis_rapat`: string jenis rapat
  - `search`: kata kunci judul rapat / lokasi ruang
  - `unit_id`: ID unit (khusus Administrator)
  - `per_page`: jumlah item per halaman (default 15, max 50)
  - `page`: nomor halaman

#### 2. Detail Agenda Rapat
* **Endpoint:** `GET /api/v1/agendas/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Detail agenda rapat berhasil diambil.",
    "data": {
      "id": 1,
      "judul_rapat": "Rapat Koordinasi Evaluasi PDDikti",
      "slug": "rakor-evaluasi-pddikti",
      "jenis_rapat": "koordinasi",
      "tipe_rapat": "hybrid",
      "lokasi_ruang": "Ruang Sidang Utama Lantai 2",
      "link_meeting": "https://zoom.us/j/1234567890",
      "waktu_mulai": "2026-10-07T09:00:00+07:00",
      "waktu_selesai": "2026-10-07T12:00:00+07:00",
      "rentang_waktu": "09:00 - 12:00 WIB",
      "jadwal_lengkap": "Rabu, 07 Okt 2026 • 09:00 - 12:00 WIB",
      "is_all_units": true,
      "status": {
        "value": "ongoing",
        "label": "Sedang Berlangsung (Presensi Dibuka)"
      },
      "surat_edaran": {
        "url": "https://siperapat.lldikti.go.id/storage/circulars/edaran_123.pdf",
        "extension": "pdf",
        "is_pdf": true,
        "is_image": false
      },
      "creator": { ... },
      "pimpinan": { ... },
      "notulis": { ... },
      "units": [ ... ],
      "attendances_count": 24,
      "nomor_ba": "BA-RAPAT/2026/0001",
      "total_invited": 79,
      "total_attended": 24,
      "unattended_count": 55,
      "participation_rate": 30.4,
      "participation_rate_label": "30.4%",
      "export_links": {
        "pdf": "https://siperapat.lldikti.go.id/api/v1/reports/agendas/1/export/pdf",
        "word": "https://siperapat.lldikti.go.id/api/v1/reports/agendas/1/export/word",
        "csv": "https://siperapat.lldikti.go.id/api/v1/reports/agendas/1/export/csv"
      },
      "is_eligible": true,
      "has_attended": false,
      "my_attendance": null,
      "notulensi_html": "<p>Rapat dibuka pukul 09.15 WIB oleh Pimpinan Rapat...</p>",
      "kesimpulan_html": "<p>Tindak lanjut perbaikan data semester genap dijadwalkan...</p>",
      "documentations": [ ... ]
    }
  }
  ```

##### Kamus Properti Telemetri & Berita Acara Agenda
| Properti | Tipe | Deskripsi & Kegunaan UI Mobile |
| :--- | :--- | :--- |
| `nomor_ba` | `string` | Nomor dokumen Berita Acara resmi (contoh: `"BA-RAPAT/2026/0001"`). Siap disalin pada kartu hero dokumen. |
| `total_invited` | `integer` | Jumlah seluruh ASN aktif yang menjadi target undangan rapat (berdasarkan seluruh instansi atau unit kerja terkait). |
| `total_attended` | `integer` | Jumlah ASN yang telah berhasil melakukan presensi dan tercatat sah pada agenda rapat. |
| `unattended_count` | `integer` | Jumlah ASN yang diundang namun belum melakukan presensi (`max(0, total_invited - total_attended)`). |
| `participation_rate` | `float` | Persentase rasio kehadiran terhadap target undangan (0.0 s.d. 100.0) untuk kalkulasi progress bar. |
| `participation_rate_label` | `string` | Label rasio kehadiran terformat siap saji (contoh: `"30.4%"`). |
| `export_links` | `object` | Tautan langsung pengunduhan dokumen resmi (`pdf`, `word`, `csv`) terproteksi token. |

#### 3. Buat Agenda Rapat (Admin / Administrator)
* **Endpoint:** `POST /api/v1/agendas`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`
* **Form Fields:**
  - `judul_rapat` (string, required)
  - `jenis_rapat` (string, required)
  - `tipe_rapat` (`offline`, `online`, `hybrid` / `luring`, `daring`)
  - `lokasi_ruang` (string, required jika offline/hybrid)
  - `link_meeting` (string URL, required jika online/hybrid)
  - `waktu_mulai` (datetime string, required)
  - `waktu_selesai` (datetime string, optional)
  - `is_all_units` (boolean, optional, default true)
  - `unit_ids` (array integer, required jika is_all_units = false)
  - `surat_edaran` (file PDF/Image max 5MB, optional)

#### 4. Perbarui Status Agenda Rapat
* **Endpoint:** `PATCH /api/v1/agendas/{id}/status`
* **Request Body:**
  ```json
  {
    "status": "ongoing"
  }
  ```
  *(Status valid: `draft`, `scheduled`, `ongoing`, `completed`, `cancelled`).*

#### 5. Penugasan Pimpinan & Notulis Rapat
* **Endpoint:** `PATCH /api/v1/agendas/{id}/roles`
* **Request Body:**
  ```json
  {
    "pimpinan_id": 1,
    "notulis_id": 2
  }
  ```

#### 6. Simpan Notulensi, Kesimpulan & Foto Dokumentasi Kegiatan
* **Endpoint:** `PUT /api/v1/agendas/{id}/notulen`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`
* **Otorisasi:** Notulis rapat yang ditugaskan, Admin Unit penyelenggara, atau Administrator (`manageMinutes`).
* **Form Fields:**
  - `notulensi` (string HTML, optional, otomatis disanitasi dari script XSS)
  - `kesimpulan` (string HTML, optional, otomatis disanitasi dari script XSS)
  - `photos[]` (array file gambar JPEG/PNG/WebP, max 3 MB per foto, max 10 foto)
  - `captions[]` (array string judul/keterangan tiap foto)
  - Pengaturan tata letak dokumen (opsional: `show_kop`, `show_meeting_info`, `show_attendees`, `signer1_name`, dll.)
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Notulensi dan dokumentasi rapat berhasil disimpan.",
    "data": { ... }
  }
  ```

#### 7. Hapus Foto Dokumentasi Kegiatan
* **Endpoint:** `DELETE /api/v1/agendas/{agenda_id}/documentations/{documentation_id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Notulis atau Admin (`manageMinutes`).
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Foto dokumentasi kegiatan berhasil dihapus."
  }
  ```

---

### 4.4. Presensi Kehadiran & Biometrik (Attendances)

#### 1. Feed Portal Presensi Utama (Mobile Attendance Feed)
* **Endpoint:** `GET /api/v1/attendances/portal`
* **Headers:** `Authorization: Bearer <token>`
* **Tujuan:** Menyajikan feed gabungan untuk layar utama presensi aplikasi Android: daftar rapat yang sedang buka presensi saat ini (`ongoing`), jadwal rapat mendatang (`upcoming`), dan 5 riwayat kehadiran terbaru pegawai tersebut.
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Feed portal presensi berhasil diambil.",
    "data": {
      "ongoing_agendas": [ ... ],
      "upcoming_agendas": [ ... ],
      "recent_attendances": [ ... ]
    }
  }
  ```

#### 2. Periksa Kesiapan Presensi Agenda (Portal Check Per-Agenda)
* **Endpoint:** `GET /api/v1/agendas/{id}/attendance-portal`
* **Headers:** `Authorization: Bearer <token>`
* **Tujuan:** Dipanggil saat halaman presensi dibuka di mobile untuk memeriksa apakah sesi aktif, apakah user berhak hadir, dan apakah user sudah presensi sebelumnya.
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Status portal presensi berhasil diperiksa.",
    "data": {
      "agenda": { ... },
      "is_ongoing": true,
      "is_eligible": true,
      "has_attended": false,
      "my_attendance": null
    }
  }
  ```

#### 3. Rekam Presensi (Check-In)
* **Endpoint:** `POST /api/v1/agendas/{id}/attendances`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`
* **Middleware:** `throttle:attendance` (max 30 request/menit)
* **Form Data:**
  - `selfie_file` (File gambar selfie, format JPEG/PNG/WebP, max 512 KB)
  - `signature_file` (File gambar tanda tangan transparan, PNG, max 512 KB)
  *(Klien web juga dapat mengirim `selfie_data` & `signature_data` base64 URI jika diperlukan).*
* **Response (201 Created):**
  ```json
  {
    "success": true,
    "message": "Presensi pada agenda 'Rakor Evaluasi' berhasil disimpan.",
    "data": {
      "id": 45,
      "agenda_id": 1,
      "user_id": 2,
      "signed_at": "2026-10-07T09:14:22+07:00",
      "selfie_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/selfie",
      "signature_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/signature",
      "ip_address": "192.168.1.100",
      "user_agent": "SIPERAPAT-Android/1.0"
    }
  }
  ```

#### 4. Unduh/Streaming Selfie Terproteksi
* **Endpoint:** `GET /api/v1/attendances/{id}/selfie`
* **Headers:** `Authorization: Bearer <token>`
* **Response:** Binary image response (`image/jpeg`) dengan header `Cache-Control: private, no-cache, no-store, must-revalidate`.

#### 5. Unduh/Streaming Tanda Tangan Terproteksi
* **Endpoint:** `GET /api/v1/attendances/{id}/signature`
* **Headers:** `Authorization: Bearer <token>`
* **Response:** Binary image response (`image/png`).

#### 6. Riwayat Kehadiran Saya
* **Endpoint:** `GET /api/v1/attendances/my-history`
* **Headers:** `Authorization: Bearer <token>`
* **Query Parameters:**
  - `page`: Nomor halaman paginasi (integer, default: `1`).
  - `per_page`: Jumlah rekaman per halaman (integer, default: `15`, min: `1`, max: `50`).
  - `search`: Kata kunci pencarian agenda rapat berdasarkan judul rapat atau nama ruangan rapat (string).
  - `period`: Filter periode bulan dan tahun dengan format `YYYY-MM` (contoh: `2026-10`, `2026-09`) atau `all` untuk seluruh periode (string).
  - `start_date` & `end_date`: Filter rentang tanggal kehadiran spesifik dengan format `YYYY-MM-DD` (string).
  - `tipe_rapat`: Filter format pelaksanaan rapat: `offline` (atau `luring`), `online` (atau `daring`), `hybrid`, atau `all` (string).
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Riwayat presensi berhasil diambil.",
    "data": [
      {
        "id": 101,
        "agenda_id": 1,
        "user_id": 7,
        "agenda": {
          "id": 1,
          "judul_rapat": "Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap",
          "slug": "rakor-evaluasi-pddikti-genap-2026",
          "jenis_rapat": "koordinasi",
          "tipe_rapat": "hybrid",
          "lokasi_ruang": "Ruang Sidang Utama Lantai 2, Gedung LLDIKTI",
          "link_meeting": "https://zoom.us/j/1234567890?pwd=lldikti-rakor",
          "waktu_mulai": "2026-10-19T09:00:00+07:00",
          "waktu_selesai": "2026-10-19T12:00:00+07:00",
          "rentang_waktu": "09:00 - 12:00 WIB",
          "status": {
            "value": "completed",
            "label": "Selesai"
          },
          "nomor_ba": "BA-RAPAT/2026/0001"
        },
        "signed_at": "2026-10-19T09:14:22+07:00",
        "selfie_url": "http://localhost:8001/api/v1/attendances/101/selfie",
        "signature_url": "http://localhost:8001/api/v1/attendances/101/signature",
        "ip_address": "192.168.1.100",
        "user_agent": "SIPERAPAT-Android/1.0",
        "created_at": "2026-10-19T09:14:22+07:00",
        "updated_at": "2026-10-19T09:14:22+07:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    },
    "summary": {
      "total_attended": 1,
      "total_eligible_agendas": 1,
      "attendance_rate": 100.0,
      "attendance_rate_label": "100.0%",
      "period_label": "Tahun Anggaran 2026"
    }
  }
  ```

#### 7. Daftar Presensi Rapat & Pegawai Belum Hadir (Agenda Attendances & Monitoring)
* **Endpoint:** `GET /api/v1/agendas/{id}/attendances`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Pimpinan rapat, Notulis, Admin Unit penyelenggara, atau Administrator (`AttendancePolicy::viewAny` / `AgendaPolicy::view`).
* **Query Parameters:**
  - `status`:
    - `all` (default): Mengembalikan daftar seluruh peserta ASN yang telah melakukan presensi sah.
    - `unattended`: Mengembalikan daftar seluruh ASN aktif yang diundang tetapi **belum melakukan presensi** (untuk konsumsi Tab "Belum Hadir").
  - `search`: Kata kunci pencarian nama atau NIP pegawai.
  - `page`: Nomor halaman (default 1).
  - `per_page`: Jumlah data per halaman (default 25, max 100).
* **Response untuk Presensi Tercatat (`?status=all`, 200 OK):**
  ```json
  {
    "success": true,
    "message": "Daftar kehadiran agenda rapat berhasil diambil.",
    "data": [
      {
        "id": 101,
        "agenda_id": 1,
        "user_id": 14,
        "user": {
          "id": 14,
          "name": "Drs. M. Syahril, M.M.",
          "nip": "196803151994031003",
          "email": "syahril@lldikti.go.id",
          "jabatan": "Pengelola Sarana Prasarana",
          "unit": { "id": 4, "nama_unit": "Subbagian Sarana & Prasarana" }
        },
        "signed_at": "2026-10-07T09:15:30+07:00",
        "selfie_url": "https://siperapat.lldikti.go.id/api/v1/attendances/101/selfie",
        "signature_url": "https://siperapat.lldikti.go.id/api/v1/attendances/101/signature",
        "ip_address": "192.168.1.104",
        "user_agent": "SIPERAPAT-Android/1.0"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 25,
      "total": 1
    },
    "summary": {
      "total_invited": 79,
      "total_attended": 1,
      "unattended_count": 78,
      "participation_rate": 1.3,
      "participation_rate_label": "1.3%"
    }
  }
  ```
* **Response untuk Belum Hadir (`?status=unattended`, 200 OK):**
  ```json
  {
    "success": true,
    "message": "Daftar ASN yang belum presensi berhasil diambil.",
    "data": [
      {
        "id": 15,
        "agenda_id": 1,
        "user_id": 15,
        "user": {
          "id": 15,
          "name": "Dr. Hj. Siti Fatimah, S.Pd., M.Hum",
          "nip": "197205121998022001",
          "email": "fatimah@lldikti.go.id",
          "jabatan": "Analis SDM Aparatur",
          "unit": { "id": 2, "nama_unit": "Subbagian Kepegawaian" }
        },
        "signed_at": null,
        "selfie_url": null,
        "signature_url": null,
        "ip_address": null,
        "user_agent": null,
        "status": "belum_hadir",
        "created_at": null,
        "updated_at": null
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 4,
      "per_page": 25,
      "total": 78
    },
    "summary": {
      "total_invited": 79,
      "total_attended": 1,
      "unattended_count": 78,
      "participation_rate": 1.3,
      "participation_rate_label": "1.3%"
    }
  }
  ```

#### 8. Detail Tanda Terima Presensi (Single Attendance Receipt)
* **Endpoint:** `GET /api/v1/attendances/{id}`
* **Alias Endpoint:** `GET /api/v1/attendances/{id}/receipt`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** `AttendancePolicy::view`. Pegawai pemilik kehadiran (`$user->id === $attendance->user_id`), Admin Unit terkait, atau Administrator. (Akses ke ID kehadiran milik pegawai lain tanpa wewenang akan ditolak dengan `403 Forbidden`).
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Detail tanda terima presensi berhasil diambil.",
    "data": {
      "id": 101,
      "agenda_id": 1,
      "user_id": 7,
      "verification_token": "SIP-20261019-1-101-E4F2B1A9",
      "user": {
        "id": 7,
        "name": "Prof. Dr. Ir. Budi Santoso, M.T.",
        "nip": "197508101999031002",
        "email": "budi.santoso@lldikti.go.id",
        "jabatan": "Ketua Kelompok Kerja",
        "role": {
          "value": "staff",
          "label": "Pegawai / Peserta"
        },
        "unit": {
          "id": 2,
          "nama_unit": "Subbagian Akademik & Kemahasiswaan",
          "kode_unit": "AKAD"
        }
      },
      "agenda": {
        "id": 1,
        "judul_rapat": "Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap",
        "slug": "rakor-evaluasi-pddikti-genap-2026",
        "jenis_rapat": "koordinasi",
        "tipe_rapat": "hybrid",
        "format_kedinasan": "Hybrid (Luring di Ruang Sidang Utama Lantai 2 & Daring via Tautan Virtual)",
        "lokasi_ruang": "Ruang Sidang Utama Lantai 2, Gedung LLDIKTI",
        "link_meeting": "https://zoom.us/j/1234567890?pwd=lldikti-rakor",
        "waktu_mulai": "2026-10-19T09:00:00+07:00",
        "waktu_selesai": "2026-10-19T12:00:00+07:00",
        "rentang_waktu": "09:00 - 12:00 WIB",
        "jadwal_lengkap": "Senin, 19 Oktober 2026 • 09:00 - 12:00 WIB",
        "status": {
          "value": "completed",
          "label": "Selesai"
        },
        "nomor_ba": "BA-RAPAT/2026/0001"
      },
      "receipt": {
        "nomor_bukti": "SIP-20261019-1-101-E4F2B1A9",
        "verification_token": "SIP-20261019-1-101-E4F2B1A9",
        "institutional_header": {
          "kementerian": "KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI",
          "instansi": "LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X",
          "alamat": "Jl. Khatib Sulaiman Padang, Sumatera Barat"
        },
        "meeting": {
          "judul_rapat": "Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap",
          "nomor_ba": "BA-RAPAT/2026/0001",
          "format_kedinasan": "Hybrid (Luring di Ruang Sidang Utama Lantai 2 & Daring via Tautan Virtual)",
          "tipe_rapat": "hybrid",
          "lokasi_ruang": "Ruang Sidang Utama Lantai 2, Gedung LLDIKTI",
          "link_meeting": "https://zoom.us/j/1234567890?pwd=lldikti-rakor",
          "rentang_waktu": "09:00 - 12:00 WIB",
          "jadwal_lengkap": "Senin, 19 Oktober 2026 • 09:00 - 12:00 WIB"
        },
        "participant": {
          "name": "Prof. Dr. Ir. Budi Santoso, M.T.",
          "nip": "197508101999031002",
          "jabatan": "Pegawai / Peserta",
          "unit_kerja": "Subbagian Akademik & Kemahasiswaan"
        },
        "validation": {
          "status": "tercatat",
          "status_label": "Hadir Sah Terverifikasi",
          "metode_verifikasi": "Swafoto Wajah & Tanda Tangan Digital",
          "ip_address": "192.168.1.100",
          "user_agent": "SIPERAPAT-Android/1.0",
          "signed_at": "2026-10-19T09:14:22+07:00"
        }
      },
      "signed_at": "2026-10-19T09:14:22+07:00",
      "selfie_url": "https://siperapat.lldikti.go.id/api/v1/attendances/101/selfie",
      "signature_url": "https://siperapat.lldikti.go.id/api/v1/attendances/101/signature",
      "ip_address": "192.168.1.100",
      "user_agent": "SIPERAPAT-Android/1.0",
      "created_at": "2026-10-19T09:14:22+07:00",
      "updated_at": "2026-10-19T09:14:22+07:00"
    }
  }
  ```

---

### 4.5. Manajemen Pengguna (Users)

* **`GET /api/v1/users`**: Daftar pengguna (tersaring otomatis berdasarkan cakupan unit Admin).
* **`POST /api/v1/users`**: Tambah pengguna baru.
* **`GET /api/v1/users/{id}`**: Detail pengguna.
* **`PUT /api/v1/users/{id}`**: Perbarui profil/jabatan/unit pengguna.
* **`DELETE /api/v1/users/{id}`**: Hapus pengguna (mencabut seluruh token aktif).
* **`PATCH /api/v1/users/{id}/toggle-status`**: Aktifkan / Nonaktifkan akun (mencabut seluruh token aktif jika dinonaktifkan).

---

### 4.6. Manajemen Unit Kerja (Units)

* **`GET /api/v1/units`**: Daftar unit kerja (`?all=true` untuk opsi dropdown tanpa paginasi).
* **`POST /api/v1/units`**: Tambah unit kerja.
* **`GET /api/v1/units/{id}`**: Detail unit kerja.
* **`PUT /api/v1/units/{id}`**: Perbarui unit kerja.
* **`DELETE /api/v1/units/{id}`**: Hapus unit kerja.
* **`PATCH /api/v1/units/{id}/toggle-status`**: Aktifkan / Nonaktifkan unit kerja.

---

### 4.7. Profil Akun & Log Aktivitas (Profile)

* **`GET /api/v1/profile`**: Mengambil profil akun pengguna aktif.
* **`PUT /api/v1/profile`**: Mengubah nama, email, nomor HP, atau kata sandi. *(Penggantian kata sandi otomatis mencabut token sesi lama).*
* **`GET /api/v1/profile/logs`**: Riwayat log audit trail aktivitas pengguna terotentikasi.
  * **Headers:** `Authorization: Bearer <token>`
  * **Query Parameters:**
    - `category`: Filter kategori makro (`login`, `agenda`, `presensi`, `user`, atau `all`).
    - `search`: Kata kunci pencarian dalam deskripsi kegiatan atau alamat IP.
    - `start_date` & `end_date`: Filter rentang tanggal aktivitas (`YYYY-MM-DD`).
    - `type`: Tipe aktivitas spesifik (`AUTH_LOGIN_API`, `RECORD_ATTENDANCE`, dll.).
    - `per_page`: Jumlah log per halaman (default 15, max 50).
    - `page`: Nomor halaman paginasi (default 1).
  * **Response (200 OK):**
    ```json
    {
      "success": true,
      "message": "Riwayat aktivitas berhasil diambil.",
      "data": [
        {
          "id": 142,
          "user_id": 7,
          "user": {
            "id": 7,
            "name": "Prof. Dr. Ir. Budi Santoso, M.T.",
            "nip": "197508101999031002"
          },
          "activity_type": "AUTH_LOGIN_API",
          "category": {
            "value": "login",
            "label": "Login"
          },
          "description": "Pengguna Prof. Dr. Ir. Budi Santoso, M.T. berhasil masuk via API Mobile",
          "target_model": null,
          "target_id": null,
          "properties": null,
          "ip_address": "192.168.1.100",
          "user_agent": "SIPERAPAT-Android/1.0",
          "created_at": "2026-10-19T08:30:15+07:00"
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 15,
        "total": 24
      },
      "summary": {
        "total_logs": 24,
        "auth_success": 18,
        "incident_count": 0,
        "today_count": 4,
        "categories": {
          "login": 18,
          "agenda": 2,
          "presensi": 4,
          "user": 0
        }
      }
    }
    ```

---

### 4.8. Laporan & Ekspor Berkas (Reports)

* **`GET /api/v1/reports/summary`**: Statistik rekapitulasi rapat (total agenda, tingkat kehadiran).
* **`GET /api/v1/reports/summary/csv`**: Unduh berkas rekapitulasi agenda spreadsheet CSV/Excel (`throttle:export`). Otomatis disanitasi terhadap bahaya CSV Formula Injection (CWE-1236) dan menyertakan UTF-8 BOM.
* **`GET /api/v1/reports/agendas/{id}`**: Rekapitulasi agenda tunggal beserta tautan unduh dokumen.
* **`POST /api/v1/reports/agendas/{id}/config/reset`**: Mereset konfigurasi kop surat, penandatangan, dan tata letak dokumen agenda kembali ke standar sistem instansi.
* **`GET /api/v1/reports/agendas/{id}/export/pdf`**: Unduh dokumen resmi Berita Acara & Daftar Hadir format PDF (`throttle:export`).
* **`GET /api/v1/reports/agendas/{id}/export/word`**: Unduh dokumen resmi Berita Acara format Microsoft Word (.doc) (`throttle:export`).
* **`GET /api/v1/reports/agendas/{id}/export/csv`**: Unduh berkas tabulasi daftar kehadiran ASN untuk satu agenda rapat spesifik format CSV spreadsheet (Excel-ready) (`throttle:export`). Otomatis disanitasi terhadap bahaya CSV Formula Injection (CWE-1236) dan menyertakan UTF-8 BOM.

---

### 4.9. Log Audit Seluruh Instansi (Activity Logs - Khusus Superadmin)

* **Endpoint:** `GET /api/v1/activity-logs`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Khusus peran `Administrator`. (Peran lain akan menerima respons `403 Forbidden`).
* **Query Parameters:**
  - `category`: Filter kategori makro aktivitas kedinasan: `login`, `agenda`, `presensi`, `user`, atau `all` (default: seluruh kategori).
  - `type`: Tipe aktivitas spesifik (contoh: `AUTH_LOGIN_API`, `CREATE_AGENDA`, `RECORD_ATTENDANCE`, `EXPORT_PDF_API`, dll.)
  - `user_id`: ID pengguna tertentu
  - `start_date`: Tanggal awal aktivitas (`YYYY-MM-DD`)
  - `end_date`: Tanggal akhir aktivitas (`YYYY-MM-DD`)
  - `search`: Kata kunci pencarian dalam deskripsi kegiatan, alamat IP, atau nama/NIP pegawai
  - `per_page`: Jumlah log per halaman (default 15, max 100)
  - `page`: Nomor halaman
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Daftar log aktivitas sistem berhasil diambil.",
    "data": [
      {
        "id": 128,
        "user_id": 1,
        "user": {
          "id": 1,
          "name": "Afdalisma, SH, M.Pd",
          "nip": "197012051992032002",
          "role": { "value": "administrator", "label": "Administrator" }
        },
        "activity_type": "EXPORT_CSV_API",
        "category": {
          "value": "agenda",
          "label": "Agenda"
        },
        "description": "Mengekspor rekapitulasi data agenda rapat ke format CSV via API Mobile",
        "target_model": "App\\Models\\Agenda",
        "target_id": null,
        "properties": null,
        "ip_address": "192.168.1.100",
        "user_agent": "SIPERAPAT-Android/1.0",
        "created_at": "2026-10-07T09:45:10+07:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 10,
      "per_page": 15,
      "total": 150
    },
    "summary": {
      "total_logs": 150,
      "auth_success": 42,
      "incident_count": 0,
      "today_count": 12,
      "categories": {
        "login": 42,
        "agenda": 68,
        "presensi": 25,
        "user": 15
      }
    }
  }
  ```

---

### 4.10. Matriks Hak Akses RBAC 44 Endpoint REST API v1

```
┌────┬────────────────────────────────────────────────┬───────────────┬────────────┬─────────┐
│ No │ Endpoint REST API v1                           │ Administrator │ Admin Unit │ Staff   │
├────┼────────────────────────────────────────────────┼───────────────┼────────────┼─────────┤
│ 1  │ POST   /api/v1/auth/login                      │ Publik        │ Publik     │ Publik  │
│ 2  │ GET    /api/v1/auth/me                         │ Ya            │ Ya         │ Ya      │
│ 3  │ POST   /api/v1/auth/logout                     │ Ya            │ Ya         │ Ya      │
│ 4  │ GET    /api/v1/dashboard                       │ Ya (Semua)    │ Ya (Scoped)│ Ya      │
│ 5  │ GET    /api/v1/profile                         │ Ya            │ Ya         │ Ya      │
│ 6  │ PUT    /api/v1/profile                         │ Ya            │ Ya         │ Ya      │
│ 7  │ GET    /api/v1/profile/logs                    │ Ya (Pribadi)  │ Ya(Pribadi)│ Ya      │
│ 8  │ GET    /api/v1/activity-logs                   │ Ya (Semua)    │ ❌ (403)   │ ❌ (403)│
│ 9  │ GET    /api/v1/agendas                         │ Ya (Semua)    │ Ya (Scoped)│ Ya(Sesi)│
│ 10 │ POST   /api/v1/agendas                         │ Ya            │ Ya         │ ❌ (403)│
│ 11 │ GET    /api/v1/agendas/{id}                    │ Ya            │ Ya (Scoped)│ Ya(Sesi)│
│ 12 │ PUT    /api/v1/agendas/{id}                    │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 13 │ DELETE /api/v1/agendas/{id}                    │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 14 │ PATCH  /api/v1/agendas/{id}/status             │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 15 │ PATCH  /api/v1/agendas/{id}/roles              │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 16 │ PUT    /api/v1/agendas/{id}/notulen            │ Ya            │ Ya (Scoped)│ Ya(Notu)│
│ 17 │ DELETE /api/v1/agendas/{a}/documentations/{d}  │ Ya            │ Ya (Scoped)│ Ya(Notu)│
│ 18 │ GET    /api/v1/attendances/portal              │ Ya            │ Ya         │ Ya      │
│ 19 │ GET    /api/v1/agendas/{id}/attendance-portal  │ Ya            │ Ya         │ Ya      │
│ 20 │ POST   /api/v1/agendas/{id}/attendances        │ Ya            │ Ya         │ Ya      │
│ 21 │ GET    /api/v1/agendas/{id}/attendances        │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 22 │ GET    /api/v1/attendances/my-history          │ Ya            │ Ya         │ Ya      │
│ 23 │ GET    /api/v1/attendances/{id}                │ Ya            │ Ya (Scoped)│ Ya(Milik│
│ 24 │ GET    /api/v1/attendances/{id}/selfie         │ Ya            │ Ya (Scoped)│ Ya(Milik│
│ 25 │ GET    /api/v1/attendances/{id}/signature      │ Ya            │ Ya (Scoped)│ Ya(Milik│
│ 26 │ GET    /api/v1/users                           │ Ya (Semua)    │ Ya (Scoped)│ ❌ (403)│
│ 27 │ POST   /api/v1/users                           │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 28 │ GET    /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 29 │ PUT    /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 30 │ DELETE /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 31 │ PATCH  /api/v1/users/{id}/toggle-status        │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 32 │ GET    /api/v1/units                           │ Ya (Semua)    │ Ya (Baca)  │ Ya(Baca)│
│ 33 │ POST   /api/v1/units                           │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 34 │ GET    /api/v1/units/{id}                      │ Ya            │ Ya         │ Ya      │
│ 35 │ PUT    /api/v1/units/{id}                      │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 36 │ DELETE /api/v1/units/{id}                      │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 37 │ PATCH  /api/v1/units/{id}/toggle-status        │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 38 │ GET    /api/v1/reports/summary                 │ Ya (Semua)    │ Ya (Scoped)│ Ya      │
│ 39 │ GET    /api/v1/reports/summary/csv             │ Ya (Semua)    │ Ya (Scoped)│ Ya      │
│ 40 │ GET    /api/v1/reports/agendas/{id}            │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 41 │ POST   /api/v1/reports/agendas/{id}/config/rst │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 42 │ GET    /api/v1/reports/agendas/{id}/export/pdf │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 43 │ GET    /api/v1/reports/agendas/{id}/export/word│ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 44 │ GET    /api/v1/reports/agendas/{id}/export/csv │ Ya            │ Ya (Scoped)│ ❌ (403)│
└────┴────────────────────────────────────────────────┴───────────────┴────────────┴─────────┘
*Keterangan: Ya(Notu) = Hanya jika pegawai bertugas sebagai Notulis rapat yang bersangkutan. 
Ya(Milik) = Hanya jika data kehadiran tersebut adalah milik pegawai itu sendiri.*
```

---

## 5. Panduan Praktis Integrasi React Native

### 5.1. Penyimpanan Token yang Aman
Gunakan `@react-native-async-storage/async-storage` atau `react-native-keychain` untuk menyimpan `Bearer token`:
```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';

export const saveToken = async (token) => {
  await AsyncStorage.setItem('siperapat_auth_token', token);
};

export const getToken = async () => {
  return await AsyncStorage.getItem('siperapat_auth_token');
};
```

### 5.2. Axios / Fetch Interceptor
```javascript
import axios from 'axios';
import { getToken } from './auth';

const api = axios.create({
  baseURL: 'https://siperapat.lldikti.go.id/api/v1',
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### 5.3. Kompresi Foto Kamera Selfie Sebelum Upload
Gunakan `react-native-image-resizer` atau opsi bawaan kamera agar foto tidak melebihi 512 KB:
```javascript
import ImageResizer from '@bam.tech/react-native-image-resizer';

const compressedImage = await ImageResizer.createResizedImage(
  rawPhotoUri,
  600,       // Max Width
  800,       // Max Height
  'JPEG',    // Format
  75,        // Quality (0-100)
  0          // Rotation
);
```

### 5.4. Pengiriman Presensi Multipart
```javascript
const formData = new FormData();
formData.append('selfie_file', {
  uri: compressedImage.uri,
  name: 'selfie.jpg',
  type: 'image/jpeg',
});
formData.append('signature_file', {
  uri: signaturePngUri,
  name: 'signature.png',
  type: 'image/png',
});

await api.post(`/agendas/${agendaId}/attendances`, formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
});
```

### 5.5. Menampilkan Notulensi & Kesimpulan (Read-Only)
Gunakan pustaka `react-native-render-html` untuk menyajikan `notulensi_html` dan `kesimpulan_html`:
```javascript
import RenderHtml from 'react-native-render-html';
import { useWindowDimensions } from 'react-native';

const MeetingMinutesView = ({ htmlContent }) => {
  const { width } = useWindowDimensions();
  return (
    <RenderHtml
      contentWidth={width}
      source={{ html: htmlContent || '<p>Belum ada notulensi.</p>' }}
    />
  );
};
```

---

### 5.6. Panduan Integrasi Khusus Layar Monitoring Presensi (MonitoringScreen.tsx)

Bagi pengembang aplikasi mobile yang mengintegrasikan layar pemantauan kehadiran rapat ([`MonitoringScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/MonitoringScreen.tsx)), berikut adalah panduan binding data dan konsumsi endpoint:

#### A. Hero Header & Nomor Berita Acara
* **Data Sumber:** `GET /api/v1/agendas/{id}`
* **Nomor Dokumen Resmi (`nomor_ba`):**
  * Tampilkan langsung pada badge/kotak info berita acara: `meeting?.nomor_ba`.
  * Aksi Tombol Salin:
    ```typescript
    await Clipboard.setStringAsync(meeting?.nomor_ba || '');
    Alert.alert('Berhasil', 'Nomor berita acara berhasil disalin ke clipboard.');
    ```

#### B. Kartu Rekapitulasi Presensi & Rasio Partisipasi
* **Statistik Kehadiran:**
  * **Hadir Sah:** Tampilkan `meeting?.total_attended` (atau `meeting?.attendances_count`).
  * **Belum Hadir:** Tampilkan `meeting?.unattended_count` (dengan subteks: `"Dari target undangan"`).
  * **Target Undangan:** Tampilkan `meeting?.total_invited` (dengan subteks: `meeting?.is_all_units ? "Seluruh ASN Instansi" : "Unit Kerja Terpilih"`).
* **Partisipasi Kehadiran (Progress Bar):**
  * Label: `meeting?.participation_rate_label` (contoh: `"30.4%"`).
  * Lebar Bar Dinamis:
    ```typescript
    style={{ width: `${Math.min(meeting?.participation_rate ?? 0, 100)}%` }}
    ```

#### C. Navigasi & Konsumsi Tab Kategori
Layar monitoring memiliki 3 tab utama:
1. **Tab 1: "Tercatat" (Daftar Hadir Sah):**
   * Endpoint: `GET /api/v1/agendas/{id}/attendances?page=1`
   * Menampilkan daftar pegawai yang telah melakukan presensi beserta waktu `signed_at`, foto selfie terproteksi (`selfie_url`), dan tanda tangan terproteksi (`signature_url`).
2. **Tab 2: "Belum Hadir" (ASN Terundang Belum Presensi):**
   * Endpoint: `GET /api/v1/agendas/{id}/attendances?status=unattended&page=1`
   * Menampilkan daftar pegawai yang belum hadir (`status: 'belum_hadir'`) dengan nama lengkap, NIP, jabatan, dan unit kerja.
3. **Tab 3: "Notulensi & Hasil Sidang":**
   * Sumber Data: `meeting?.notulensi_html` dan `meeting?.kesimpulan_html`.
   * Render menggunakan `RenderHtml` (read-only).

#### D. Aksi Ekspor & Unduh Dokumen Kedinasan
Gunakan properti `meeting?.export_links` untuk aksi unduh:
1. **Unduh Berita Acara (PDF):**
   * URL: `meeting?.export_links?.pdf` (`GET /api/v1/reports/agendas/{id}/export/pdf`)
   * Buka via browser atau downloader terotentikasi Bearer token.
2. **Unduh Risalah Sidang (DOCX):**
   * URL: `meeting?.export_links?.word` (`GET /api/v1/reports/agendas/{id}/export/word`)
3. **Unduh Tabulasi Kehadiran (Excel/CSV):**
   * URL: `meeting?.export_links?.csv` (`GET /api/v1/reports/agendas/{id}/export/csv`)
   * Menghasilkan file CSV biner dengan encoding UTF-8 BOM yang langsung terbaca rapi pada Microsoft Excel dan Google Sheets tanpa karakter rusak (*mojibake*).

---

### 5.7. Panduan Integrasi Khusus Layar Riwayat Presensi (RiwayatScreen.tsx)

Bagi pengembang aplikasi mobile yang mengintegrasikan layar catatan kehadiran pegawai ([`RiwayatScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/RiwayatScreen.tsx)), berikut adalah panduan binding data dan konsumsi endpoint:

#### A. Konsumsi & Binding Bento Overview Cards
Pada baris 124–150 di `RiwayatScreen.tsx`, data agregat telemetri kini tersedia langsung pada objek `summary` di respons `GET /api/v1/attendances/my-history`:
1. **Kartu 1 (Total Rapat):**
   * Nilai: `response.summary.total_attended` (atau `response.meta.total`).
   * Subteks: `response.summary.period_label` (contoh: `"Tahun Anggaran 2026"` atau `"Okt 2026"`).
2. **Kartu 2 (Tingkat Kehadiran):**
   * Nilai: Gantikan strip `—` dengan `response.summary.attendance_rate_label` (contoh: `"100.0%"` atau `"88.9%"`).
   * Subteks: Gantikan pesan *"Persentase tidak disediakan API"* dengan `"Dari " + response.summary.total_eligible_agendas + " Rapat Dinas"`.
3. **Kartu 3 (Periode Aktif):**
   * Nilai: `response.summary.period_label`.
   * Subteks: Periode aktif filter kalender berjalan.

#### B. Kontrol Pencarian & Filter Dinamis Sisi Server
Alihkan penyaringan data dari *client-side in-memory* ke *server-side dynamic query params*:
1. **Pencarian Nama Agenda (`search`):**
   * Hubungkan input search bar ke query parameter: `GET /api/v1/attendances/my-history?search={encodeURIComponent(search)}` (rekomendasi debounce 300ms).
   * Backend melakukan pencarian sargable multi-kolom (`judul_rapat`, `lokasi_ruang`, dan ID agenda).
2. **Filter Periode Bulan/Tahun (`DateFilterModal.tsx`):**
   * Saat user memilih bulan tertentu (misal `'2026-10'`):
     `GET /api/v1/attendances/my-history?period=2026-10`
   * Saat user memilih `"Semua Periode"`:
     `GET /api/v1/attendances/my-history?period=all` (atau tanpa parameter period).
   * Backend juga mendukung rentang tanggal kustom via `?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`.
3. **Filter Format Pelaksanaan Rapat (`tipe_rapat`):**
   * Mendukung opsi: `hybrid`, `offline` (atau `luring`), `online` (atau `daring`), `all`.
   * Endpoint: `GET /api/v1/attendances/my-history?tipe_rapat={selectedType}`.
   * *Catatan:* Toast pembatas sebelumnya (`"API riwayat belum menyertakan tipe rapat"`) kini dapat dihapus karena backend sudah mendukung filter `tipe_rapat` secara penuh.

#### C. Kartu Daftar Riwayat Presensi (historyCard)
Setiap item dalam array `data` pada respons `myHistory` kini telah diperkaya dengan relasi lengkap `agenda`:
1. **Nama Ruangan / Tautan Virtual:**
   * Gantikan teks fallback *"Detail agenda tidak disertakan pada API riwayat"* dengan:
     `item.agenda?.lokasi_ruang || item.agenda?.link_meeting || 'Lokasi tidak ditentukan'`.
2. **Judul Rapat:**
   * Ambil langsung dari `item.agenda?.judul_rapat` (menggantikan judul sintetis `"Agenda #" + dto.agenda_id`).
3. **Nomor Dokumen Berita Acara:**
   * Ambil dari `item.agenda?.nomor_ba` (contoh: `"BA-RAPAT/2026/0001"`).
4. **Format Pertemuan:**
   * Ambil dari `item.agenda?.tipe_rapat` (`'hybrid'`, `'offline'`, atau `'online'`).
5. **Waktu Pelaksanaan:**
   * Ambil dari `item.agenda?.rentang_waktu` atau waktu presensi `item.signed_at`.
6. **Aksi Tanda Terima:**
   * Tombol *"Tanda Terima"* tetap mengarahkan ke `onSelectReceipt(item.id)` dengan data presensi yang valid.

---

### 5.8. Panduan Integrasi Khusus Layar Tanda Terima Presensi (ReceiptScreen.tsx & PerforatedTicket.tsx)

Bagi pengembang aplikasi mobile yang mengintegrasikan layar tanda terima presensi kedinasan ([`ReceiptScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/ReceiptScreen.tsx) dan [`PerforatedTicket.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/components/receipt/PerforatedTicket.tsx)), berikut adalah pedoman binding data dan pemanfaatan endpoint:

#### A. Pengambilan Data Presensi Tunggal O(1) (Eliminasi Loop Paginasi)
* **Endpoint:** `GET /api/v1/attendances/{id}` (atau alias `GET /api/v1/attendances/{id}/receipt`)
* **Headers:** `Authorization: Bearer <token>`
* **Optimasi Arsitektur:**
  Sebelumnya pada `AttendanceRepositoryImpl.ts#getAttendanceById`, klien mobile terpaksa melakukan loop paginasi `while (page <= lastPage)` memanggil `getAttendanceHistory(page)` hanya untuk mencari 1 catatan presensi. Dengan adanya endpoint tunggal ini, klien dapat mengambil detail tanda terima secara instan dalam **1 HTTP Request tunggal ($O(1)$ complexity)**.

  ```typescript
  // Implementasi yang direkomendasikan pada AttendanceRepositoryImpl.ts:
  async getAttendanceById(id: number): Promise<AttendanceReceipt> {
    const response = await this.httpClient.get<ApiResponse<AttendanceDto>>(`/api/v1/attendances/${id}`);
    if (!response.data.success || !response.data.data) {
      throw new Error(response.data.message || 'Tanda terima presensi tidak ditemukan');
    }
    return AttendanceMapper.toReceipt(response.data.data);
  }
  ```

#### B. Nomor Bukti Presensi & Token Kriptografis (PerforatedTicket Header)
* **Sumber Data:** `data.verification_token` atau `data.receipt.nomor_bukti`.
* **Format:** `SIP-{YYYYMMDD}-{agenda_id}-{attendance_id}-{SHORT_HASH}` (contoh: `SIP-20261019-1-101-E4F2B1A9`).
* **Binding UI di `PerforatedTicket.tsx` (baris 38–41):**
  Gantikan fallback text:
  ```typescript
  // SEBELUM (Fallback Hardcoded):
  <Text style={styles.ticketId}>ID {attendance.id} • token tidak disediakan API</Text>

  // SESUDAH (Terintegrasi Penuh):
  <Text style={styles.ticketId}>{attendance.verification_token || `ID ${attendance.id}`}</Text>
  ```

#### C. Format Kedinasan & Lokasi Pertemuan (PerforatedTicket Meeting Box)
* **Sumber Data:** `data.agenda.format_kedinasan` atau `data.receipt.meeting.format_kedinasan`.
* **Format Nilai Dinamis:**
  - Hybrid: `"Hybrid (Luring di Ruang Sidang Utama Lantai 2 & Daring via Tautan Virtual)"`
  - Offline: `"Luring di Ruang Rapat Pimpinan Lantai 1"`
  - Online: `"Daring via Tautan Virtual (https://zoom.us/...)"`
* **Binding UI di `PerforatedTicket.tsx` (baris 74–80):**
  Gantikan fallback text:
  ```typescript
  // SEBELUM (Fallback Hardcoded):
  <Text style={styles.locationDetail}>Lokasi dan format tidak disediakan API</Text>

  // SESUDAH (Terintegrasi Penuh):
  <Text style={styles.locationDetail}>
    {attendance.receipt?.meeting?.format_kedinasan || attendance.agenda?.format_kedinasan || 'Format kedinasan resmi LLDIKTI'}
  </Text>
  ```

#### D. Metadata Institusional & Validasi Otentisitas
Objek `data.receipt` menyediakan seluruh metadata kedinasan siap saji yang dibutuhkan untuk rendering tiket:
1. **Header Instansi (`receipt.institutional_header`):**
   - `kementerian`: `"KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI"`
   - `instansi`: `"LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X"`
   - `alamat`: `"Jl. Khatib Sulaiman Padang, Sumatera Barat"`
2. **Data Peserta (`receipt.participant`):**
   - `name`: Nama lengkap ASN beserta gelar.
   - `nip`: NIP resmi 18 digit.
   - `jabatan`: Label peran/jabatan kedinasan ASN.
   - `unit_kerja`: Nama subbagian / unit kerja ASN.
3. **Validasi Bukti Presensi (`receipt.validation`):**
   - `status`: `"tercatat"`
   - `status_label`: `"Hadir Sah Terverifikasi"`
   - `metode_verifikasi`: `"Swafoto Wajah & Tanda Tangan Digital"`
   - `signed_at`: Stempel waktu ISO-8601 presensi ASN.
   - `ip_address`: Alamat IP jaringan perangkat saat presensi.

---

### 5.9. Panduan Integrasi Khusus Layar Log Aktivitas Sistem (AuditLogScreen.tsx)

Bagi pengembang aplikasi mobile yang mengintegrasikan layar audit trail kedinasan ([`AuditLogScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/AuditLogScreen.tsx) dan [`userApi.ts`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/data/datasources/userApi.ts)), berikut adalah pedoman binding data dan pemanfaatan endpoint:

#### A. Konsumsi & Binding Ringkasan Aktivitas (Integrity Card)
Pada baris 88–112 di `AuditLogScreen.tsx`, data agregat telemetri kini tersedia langsung pada objek `summary` di respons `GET /api/v1/activity-logs` (Superadmin) dan `GET /api/v1/profile/logs` (Non-Admin):
1. **Kartu 1 (Total Log):**
   * Nilai: `response.summary.total_logs` (atau `response.meta.total`).
   * Subteks: `"Total Log"`.
2. **Kartu 2 (Otentikasi Sah):**
   * Nilai: Gantikan strip `'-'` dengan `response.summary.auth_success.toLocaleString('id-ID')`.
   * Subteks: `"Otentikasi Sah"` (total aktivitas login kedinasan yang valid).
3. **Kartu 3 (Insiden Keamanan):**
   * Nilai: Gantikan strip `'-'` dengan `response.summary.incident_count.toLocaleString('id-ID')` (menampilkan `0` sebagai indikasi zero-incident kedinasan).
   * Subteks: `"Insiden"`.

#### B. Filter Kategori Sisi Server (Server-Side Category Query)
Alihkan penyaringan filter pills `['Semua', 'Login', 'Agenda', 'Presensi', 'User']` dari *client-side in-memory filter* ke *server-side dynamic query parameter*:
1. **Mengapa Harus Server-Side:**
   Penyaringan lokal (`logs.filter(...)`) hanya memfilter 50 item yang sedang aktif di-load. Jika di halaman aktif tidak ada aktivitas kategori yang dipilih, daftar akan kosong dan memunculkan kebingungan bagi pengguna.
2. **Implementasi Parameter URL di `userApi.ts`:**
   ```typescript
   export const activityLogApi = {
     personal: (page = 1, options: { search?: string; category?: string } = {}) =>
       apiClient.get<ApiPaginated<ApiActivityLogDto>>(
         `/profile/logs?${query({ page, per_page: 50, search: options.search, category: options.category !== 'Semua' ? options.category?.toLowerCase() : undefined })}`
       ),
     system: (page = 1, options: { search?: string; category?: string } = {}) =>
       apiClient.get<ApiPaginated<ApiActivityLogDto>>(
         `/activity-logs?${query({ page, per_page: 50, search: options.search, category: options.category !== 'Semua' ? options.category?.toLowerCase() : undefined })}`
       ),
   };
   ```
3. **Nilai Parameter yang Didukung Backend:**
   - `category=login` (seluruh aktivitas login/logout)
   - `category=agenda` (pembuatan, pembaruan, notulensi, dan ekspor dokumen agenda)
   - `category=presensi` (seluruh aktivitas presensi kehadiran ASN)
   - `category=user` (manajemen akun, unit kerja, profil, dan sandi)
   - `category=all` (atau abaikan parameter untuk seluruh kategori)

#### C. Pencarian Aktivitas Real-Time Debounce (`search`)
* Input pencarian mendukung pencarian multi-kolom sargable:
  - Deskripsi kegiatan kedinasan
  - Alamat IP jaringan (`ip_address`)
  - Nama dan NIP pegawai pelaksana kegiatan
* Hubungkan input search bar dengan debounce 350ms ke query parameter:
  `GET /api/v1/activity-logs?search={encodeURIComponent(search)}`

#### D. Pemanfaatan Objek Kategori Terstruktur (`item.category`)
Setiap rekaman log kini menyertakan objek `category` siap pakai:
```typescript
log.category: {
  value: 'login' | 'agenda' | 'presensi' | 'user' | 'lainnya',
  label: 'Login' | 'Agenda' | 'Presensi' | 'User' | 'Lainnya'
}
```
Klien mobile dapat langsung menampilkan `log.category.label` pada badge log item tanpa perlu menjalankan fungsi parsing string manual `toCategory(log.activity_type)`.



