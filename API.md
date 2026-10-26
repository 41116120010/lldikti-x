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
* **Presensi Check-In:** Maksimal 10 percobaan per menit per user (`throttle:attendance`).
* **Ekspor Dokumen:** Maksimal 15 permintaan per menit per user (`throttle:export`).
* **Ubah Kata Sandi:** Maksimal 5 permintaan per menit per user/IP (`throttle:password`).
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
* **Deskripsi:** Mengembalikan profil data ASN yang sedang login, mencakup unit kerja, jabatan, pangkat/golongan, metadata keamanan akun, dan statistik kehadiran.
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Profil pengguna berhasil diambil.",
    "data": {
      "id": 14,
      "name": "Drs. M. Syahril, M.M.",
      "nip": "196803151994031003",
      "username": "msyahril",
      "email": "syahril@lldikti.go.id",
      "phone": "081266128901",
      "jabatan": "Admin Subbagian Sarana & Prasarana",
      "pangkat_golongan": "Penata Tingkat I (Gol. III/d)",
      "avatar_url": null,
      "role": {
        "value": "admin_unit",
        "label": "Admin Unit"
      },
      "is_active": true,
      "unit_id": 4,
      "unit": {
        "id": 4,
        "nama_unit": "Subbagian Sarana & Prasarana",
        "kode_unit": "SARPRAS",
        "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
        "is_active": true
      },
      "security": {
        "last_password_changed_at": "2026-09-19T10:15:00+07:00",
        "last_password_changed_label": "Terakhir diperbarui 30 hari yang lalu",
        "last_login_at": "2026-10-19T08:15:20+07:00",
        "last_login_label": "Baru saja"
      },
      "attendance_stats": {
        "total_agendas": 24,
        "total_agendas_label": "24",
        "total_attended": 23,
        "attendance_rate": 98.5,
        "attendance_rate_label": "98.5%",
        "on_time_count": 23,
        "on_time_rate": 100.0,
        "on_time_rate_label": "100%",
        "period_label": "Tahun Anggaran 2026"
      },
      "created_at": "2026-09-01T08:00:00+07:00",
      "updated_at": "2026-10-19T08:15:20+07:00"
    }
  }
  ```

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
      "allowed_transitions": [
        "completed",
        "scheduled"
      ],
      "can_start_now": false,
      "is_overdue": false,
      "earliest_start_at": "2026-10-07T08:45:00+07:00",
      "effective_end_at": "2026-10-07T12:15:00+07:00",
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

##### Kamus Properti Telemetri, Status Lifecycle & Berita Acara Agenda
| Properti | Tipe | Deskripsi & Kegunaan UI Mobile |
| :--- | :--- | :--- |
| `status` | `object` | Status operasional saat ini (`{ value, label }`). Nilai: `draft`, `scheduled`, `ongoing`, `completed`, `cancelled`. |
| `allowed_transitions` | `array` | Daftar status tujuan yang sah dapat ditransisikan dari status saat ini sesuai State Machine resmi. |
| `can_start_now` | `boolean` | `true` bila rapat berstatus `scheduled` dan waktu saat ini telah memasuki jendela buka rapat (**15 menit sebelum waktu mulai** s.d. batas waktu selesai efektif). |
| `is_overdue` | `boolean` | `true` bila rapat `scheduled` telah melewati waktu pelaksanaan tanpa pernah dibuka presensinya. Mobile UI wajib menampilkan chip/badge **"Terlewat"**. |
| `earliest_start_at` | `string` | Waktu paling awal tombol "Mulai Rapat" dapat ditekan (ISO-8601: `waktu_mulai - 15 menit`). |
| `effective_end_at` | `string` | Waktu batas akhir toleransi presensi (ISO-8601: `waktu_selesai/endOfDay + 15 menit`). Melewati waktu ini, presensi ditolak dan scheduler menutup rapat otomatis. |
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
  - `status` (string, optional: `draft` atau `scheduled`, default `scheduled`. Pembuatan agenda langsung dengan status `ongoing` atau `completed` dilarang demi integritas alur).

#### 4. Perbarui Agenda Rapat Secara Lengkap (Admin / Administrator)
* **Endpoint:** `PUT /api/v1/agendas/{id}`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json` (tanpa berkas baru) atau `Content-Type: multipart/form-data` (dengan berkas baru dan header `X-HTTP-Method-Override: PUT` atau form `_method=PUT`).
* **Otorisasi:** Administrator (seluruh agenda), atau Admin Unit (agenda yang dibuat oleh admin/unitnya atau agenda unit non-pleno).
* **Payload Fields:**
  - `judul_rapat` (string, required, max 255)
  - `jenis_rapat` (string, required, max 100)
  - `tipe_rapat` (`offline`, `online`, `hybrid`)
  - `lokasi_ruang` (string, required jika offline/hybrid, max 150)
  - `link_meeting` (string URL, required jika online/hybrid, max 500)
  - `waktu_mulai` (datetime ISO-8601 dengan offset `+07:00`, required)
  - `waktu_selesai` (datetime ISO-8601 dengan offset `+07:00`, optional, wajib setelah waktu_mulai)
  - `pimpinan_id` (integer, optional, ID ASN pimpinan rapat)
  - `notulis_id` (integer, optional, ID ASN notulis rapat)
  - `is_all_units` (boolean, optional)
  - `unit_ids` (array integer, required jika is_all_units = false)
  - `surat_edaran` (file PDF/Image max 5MB, optional pengganti berkas lama)
  - `status` (string, optional: status yang diizinkan sesuai State Machine `allowed_transitions`)
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Agenda rapat 'Rapat Koordinasi' berhasil diperbarui.",
    "data": { ... }
  }
  ```

#### 5. Hapus Agenda Rapat (Admin / Administrator)
* **Endpoint:** `DELETE /api/v1/agendas/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** 
  - Administrator: Berhak menghapus agenda pada seluruh unit kerja.
  - Admin Unit: Hanya berhak menghapus agenda yang dibuat oleh dirinya/unit kerjanya, atau agenda unit khusus non-pleno (`is_all_units = false`). Dilarang menghapus agenda pleno institusi yang dibuat pihak lain.
* **Aturan Integritas Arsip:**
  - Agenda yang **telah memiliki catatan presensi kehadiran pegawai ASN DILARANG dihapus**. Permintaan akan ditolak dengan respons **HTTP 422**. Untuk membatalkan agenda yang batal terlaksana, gunakan perubahan status menjadi `cancelled` (`Dibatalkan`).
* **Response Berhasil (200 OK):**
  ```json
  {
    "success": true,
    "message": "Agenda rapat 'Rapat Koordinasi' berhasil dihapus."
  }
  ```
* **Response Penolakan Integritas Arsip (422 Unprocessable Entity):**
  ```json
  {
    "success": false,
    "message": "Agenda rapat 'Rapat Koordinasi' telah memiliki catatan presensi kehadiran pegawai. Agenda tidak dapat dihapus demi integritas arsip kegiatan. Ubah status agenda menjadi 'Dibatalkan' jika agenda batal terlaksana."
  }
  ```

#### 6. Unggah / Ganti Berkas Surat Edaran Rapat (Admin / Administrator)
* **Endpoint:** `POST /api/v1/agendas/{id}/surat-edaran`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: multipart/form-data`
* **Otorisasi:** Administrator atau Admin Unit penyelenggara / pembuat agenda (`update`).
* **Form Fields:**
  - `surat_edaran`: Berkas PDF atau Gambar (JPEG/PNG/WebP, max: 5120 KB / 5 MB).
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Berkas surat edaran rapat berhasil diunggah.",
    "data": { ... }
  }
  ```

#### 7. Perbarui Status Agenda Rapat (State Machine)
* **Endpoint:** `PATCH /api/v1/agendas/{id}/status`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Otorisasi:** Administrator atau Admin Unit penyelenggara / pembuat agenda (`manageStatus`).
* **Request Body:**
  ```json
  {
    "status": "ongoing"
  }
  ```
* **Matriks Transisi Status Resmi (State Machine):**
  | Status Asal | Target Diizinkan (`allowed_transitions`) | Syarat & Penegakan Bisnis |
  | :--- | :--- | :--- |
  | `draft` | `scheduled`, `cancelled` | Transisi ke `scheduled` melewati validasi room advisory lock dan deteksi konflik bentrok jadwal. Waktu mulai tidak boleh berada di masa lampau. |
  | `scheduled` | `ongoing`, `draft`, `cancelled` | Transisi ke `ongoing` (Buka Presensi) **hanya diizinkan dalam jendela 15 menit sebelum `waktu_mulai`** hingga `effective_end_at`. Jika lebih awal dari 15 menit sebelum rapat, API menolak dengan HTTP 422. |
  | `ongoing` | `completed`, `scheduled` | Transisi ke `completed` (Tutup Presensi) dapat dilakukan kapan saja oleh penyelenggara atau otomatis oleh scheduler. Rollback ke `scheduled` hanya diizinkan bila `attendances_count === 0`. |
  | `completed` | *None* (`[]`) | **Final & Permanen**. Berkas notulensi dan daftar hadir terkunci dari perubahan status. |
  | `cancelled` | `draft` | Mengaktifkan kembali agenda yang batal untuk dijadwalkan ulang sebagai konsep. Diizinkan untuk Administrator dan Admin Unit penyelenggara rapat. |
* **Response Berhasil (200 OK):**
  ```json
  {
    "success": true,
    "message": "Status agenda rapat berhasil diubah menjadi Sedang Berlangsung (Presensi Dibuka).",
    "data": { ... }
  }
  ```
* **Response Penolakan Transisi Tidak Sah (422 Unprocessable Entity):**
  ```json
  {
    "success": false,
    "message": "Perubahan status agenda dari 'completed' menjadi 'scheduled' tidak diizinkan oleh sistem."
  }
  ```
* **Response Penolakan di Luar Jendela Waktu (422 Unprocessable Entity):**
  ```json
  {
    "success": false,
    "message": "Sesi rapat belum dapat dimulai. Tombol mulai baru dapat diakses 15 menit sebelum jadwal rapat (08:45 WIB)."
  }
  ```

#### 8. Penugasan Pimpinan & Notulis Rapat
* **Endpoint:** `PATCH /api/v1/agendas/{id}/roles`
* **Request Body:**
  ```json
  {
    "pimpinan_id": 1,
    "notulis_id": 2
  }
  ```

#### 9. Simpan Notulensi, Kesimpulan & Foto Dokumentasi Kegiatan
* **Endpoint:** `PUT /api/v1/agendas/{id}/notulen` atau `POST /api/v1/agendas/{id}/notulen` (dengan `_method=PUT` atau header `X-HTTP-Method-Override: PUT`)
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json` (hanya teks) atau `Content-Type: multipart/form-data` (disertai foto)
* **Otorisasi:** Pegawai yang ditunjuk sebagai Notulis (`notulis_id`) atau Pimpinan (`pimpinan_id`), Admin Unit penyelenggara, atau Administrator (`manageMinutes`). Dapat diisi dan dimutakhirkan pada agenda rapat berstatus `scheduled`, `ongoing`, maupun `completed`.
* **Form / JSON Fields:**
  - `notulensi` (string HTML / teks, optional, otomatis disanitasi dari script XSS)
  - `kesimpulan` (string HTML / teks, optional, otomatis disanitasi dari script XSS)
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

#### 10. Hapus Foto Dokumentasi Kegiatan
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
* **Tujuan:** Dipanggil saat halaman presensi dibuka di mobile (`PresensiScreen.tsx`) untuk memeriksa kesiapan sesi, status eligibility unit kerja, status presensi pengguna, serta menyajikan metadata konteks verifikasi lokasi sah dan waktu resmi server instansi.
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Status portal presensi berhasil diperiksa.",
    "data": {
      "agenda": {
        "id": 1,
        "judul_rapat": "Rapat Koordinasi Evaluasi Pelaporan PDDikti Semester Genap",
        "tipe_rapat": "hybrid",
        "lokasi_ruang": "Ruang Sidang Utama Lantai 2, Gedung LLDIKTI",
        "link_meeting": "https://zoom.us/j/1234567890?pwd=lldikti-rakor",
        "rentang_waktu": "09:00 - 12:00 WIB",
        "status": {
          "value": "ongoing",
          "label": "Sedang Berlangsung (Presensi Dibuka)"
        }
      },
      "is_ongoing": true,
      "is_eligible": true,
      "has_attended": false,
      "status_note": null,
      "verification_context": {
        "server_time": "2026-10-20T08:30:00+07:00",
        "server_time_label": "20 Oktober 2026, 08:30:00 WIB",
        "client_ip": "180.252.164.22",
        "meeting_format": {
          "value": "hybrid",
          "label": "Hybrid (Luring & Daring)"
        },
        "venue": "Ruang Sidang Utama Lantai 2, Gedung LLDIKTI",
        "format_kedinasan": "Hybrid (Luring di Ruang Sidang Utama Lantai 2 & Daring via Tautan Virtual)",
        "location_validation": {
          "is_required": false,
          "type": "flexible",
          "label": "Presensi Fleksibel (Luring di Ruang Rapat / Daring)",
          "badge": "Fleksibel Sah"
        },
        "user_identity": {
          "nip": "196805121994032001",
          "name": "Afdalisma, SH, M.Pd",
          "unit_name": "Kepala Lembaga",
          "role": "administrator",
          "role_label": "Administrator"
        }
      },
      "my_attendance": null
    }
  }
  ```

#### 3. Rekam Presensi Sah Kedinasan (Check-In)
* **Endpoint:** `POST /api/v1/agendas/{id}/attendances`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json` atau `multipart/form-data`
* **Middleware:** `throttle:attendance` (max 30 request/menit)
* **Payload Opsi 1: Base64 Data URI (`application/json`) — Rekomendasi Utama Klien Mobile (`PresensiScreen.tsx`):**
  ```json
  {
    "selfie_data": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
    "signature_data": "data:image/png;base64,iVBORw0KGgo..."
  }
  ```
  * `selfie_data`: String data URI gambar (JPEG, PNG, atau WebP), max 1.000.000 karakter (~750 KB).
  * `signature_data`: String data URI gambar tanda tangan transparan (PNG, JPEG, atau WebP), max 1.000.000 karakter.
* **Payload Opsi 2: Multipart Form Data (`multipart/form-data`) — Fallback Alternatif:**
  - `selfie_file`: Berkas gambar selfie kamera (`image/jpeg, image/png, image/webp`, max: 512 KB).
  - `signature_file`: Berkas gambar tanda tangan (`image/png, image/jpeg, image/webp`, max: 512 KB).
* **Response (201 Created):**
  ```json
  {
    "success": true,
    "message": "Presensi pada agenda 'Rapat Koordinasi Evaluasi PDDikti' berhasil disimpan.",
    "data": {
      "id": 45,
      "agenda_id": 1,
      "user_id": 2,
      "signed_at": "2026-10-20T08:32:15+07:00",
      "selfie_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/selfie",
      "signature_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/signature",
      "ip_address": "180.252.164.22",
      "user_agent": "SIPERAPAT-Android/1.0"
    }
  }
  ```
* **Response (200 OK — Idempotency / Sudah Tercatat Sebelumnya):**
  Jika presensi ASN pada agenda rapat tersebut sudah pernah tercatat (misal saat retry koneksi), backend secara cerdas merespons dengan HTTP 200 tanpa menduplikasi data di database:
  ```json
  {
    "success": true,
    "message": "Presensi Anda pada agenda rapat ini sudah pernah tercatat.",
    "data": {
      "id": 45,
      "agenda_id": 1,
      "user_id": 2,
      "signed_at": "2026-10-20T08:32:15+07:00",
      "selfie_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/selfie",
      "signature_url": "https://siperapat.lldikti.go.id/api/v1/attendances/45/signature",
      "ip_address": "180.252.164.22",
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

#### 1. Daftar Pengguna ASN (`GET /api/v1/users`)
* **Endpoint:** `GET /api/v1/users`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Administrator atau Admin Unit Kerja (`UserPolicy::viewAny`). Catatan: Admin Unit secara otomatis hanya melihat daftar ASN yang bernaung di unit kerjanya (*unit-scoped*).
* **Query Parameters:**
  | Parameter | Tipe | Contoh Nilai | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `page` | `integer` | `1` | Nomor halaman paginasi (default: 1). |
  | `per_page` | `integer` | `15` | Jumlah item per halaman (min 1, max 50, default: 15). |
  | `search` | `string` | `syahril` | Pencarian teks fleksibel pada Nama Lengkap, NIP, Username, atau Email. |
  | `role` | `string` | `staff` / `Admin Unit` | Filter peran pengguna. Mendukung enum backend (`administrator`, `admin`, `staff`) maupun label UI mobile (`Administrator`, `Admin Unit`, `Staff`, `Pegawai`). |
  | `status` | `string` | `aktif` / `inactive` | Filter status keaktifan. Mendukung `active`/`inactive` dan Bahasa Indonesia `aktif`/`nonaktif` / `1`/`0`. |
  | `unit_id` | `integer` / `none` | `4` atau `none` | *(Khusus Administrator)* Filter ID unit kerja, atau `none` untuk ASN tanpa unit. |
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Daftar pengguna berhasil diambil.",
    "data": [
      {
        "id": 14,
        "name": "Drs. M. Syahril, M.M.",
        "nip": "196803151994031003",
        "username": "msyahril",
        "email": "syahril@lldikti.go.id",
        "phone": "081266128901",
        "jabatan": "Admin Subbagian Sarana & Prasarana",
        "pangkat_golongan": "Penata Tingkat I (Gol. III/d)",
        "avatar_url": null,
        "role": {
          "value": "admin",
          "label": "Admin Unit"
        },
        "is_active": true,
        "unit_id": 4,
        "unit": {
          "id": 4,
          "nama_unit": "Subbagian Sarana & Prasarana",
          "kode_unit": "SARPRAS",
          "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
          "is_active": true
        },
        "created_at": "2026-09-01T08:00:00+07:00",
        "updated_at": "2026-10-19T08:15:20+07:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 6,
      "per_page": 15,
      "total": 79
    },
    "summary": {
      "total_users": 79,
      "active_users": 77,
      "inactive_users": 2,
      "admin_users": 12,
      "staff_users": 67,
      "active_units": 12,
      "attendance_rate": 92.4,
      "attendance_rate_label": "92.4%",
      "attendance_rate_subtext": "Rata-rata Seluruh ASN (14 Rapat)"
    }
  }
  ```

#### 2. Tambah Pengguna ASN Baru (`POST /api/v1/users`)
* **Endpoint:** `POST /api/v1/users`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Otorisasi:** Administrator atau Admin Unit Kerja (`UserPolicy::create`). Admin Unit otomatis mengunci `unit_id` ke unit miliknya dan tidak dapat membuat role `administrator`.
* **Fitur Normalisasi Toleran (*Robustness Principle*):**
  - **Password Otomatis:** Jika `password` tidak dikirim (karena form mobile tidak menyediakan field kata sandi), backend otomatis menetapkan kata sandi kedinasan awal: `'Password123!'`.
  - **Sanitasi Username:** Otomatis membuang simbol awalan `@` (contoh `@johndoe` menjadi `johndoe`) dan mengubah ke huruf kecil.
  - **Resolusi String Unit:** Jika client mengirimkan nama unit berupa teks pada field `unit` (misal `"Subbagian Kepegawaian"`), backend otomatis mencarikan `unit_id` yang cocok.
  - **Normalisasi Role & Status:** Menerima label UI seperti `'Administrator'`, `'Admin Unit'`, `'Staff'`, serta status `'Aktif'` / `'Nonaktif'`.
  - **Sanitasi Nomor Telepon:** Simbol `'-'` atau string kosong otomatis dikonversi menjadi `null`.
* **Payload Request:**
  ```json
  {
    "name": "Syahril Amri, S.Kom.",
    "nip": "199408152020121002",
    "username": "@syahrilamri",
    "email": "syahril.amri@lldikti.go.id",
    "role": "Staff",
    "unit": "Subbagian Sarana & Prasarana",
    "status": "Aktif",
    "phone": "081234567890",
    "jabatan": "Pranata Komputer Ahli Pertama",
    "pangkat_golongan": "Penata Muda (Gol. III/a)"
  }
  ```
* **Response Sukses (201 Created):**
  ```json
  {
    "success": true,
    "message": "Pengguna 'Syahril Amri, S.Kom.' berhasil ditambahkan.",
    "data": {
      "id": 80,
      "name": "Syahril Amri, S.Kom.",
      "nip": "199408152020121002",
      "username": "syahrilamri",
      "email": "syahril.amri@lldikti.go.id",
      "phone": "081234567890",
      "jabatan": "Pranata Komputer Ahli Pertama",
      "pangkat_golongan": "Penata Muda (Gol. III/a)",
      "avatar_url": null,
      "role": {
        "value": "staff",
        "label": "Pegawai ASN"
      },
      "is_active": true,
      "unit_id": 4,
      "unit": {
        "id": 4,
        "nama_unit": "Subbagian Sarana & Prasarana",
        "kode_unit": "SARPRAS",
        "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
        "is_active": true
      },
      "created_at": "2026-10-19T15:20:00+07:00",
      "updated_at": "2026-10-19T15:20:00+07:00"
    }
  }
  ```

#### 3. Detail Pengguna ASN (`GET /api/v1/users/{id}`)
* **Endpoint:** `GET /api/v1/users/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** `UserPolicy::view`. Administrator dapat melihat semua pegawai. Admin Unit hanya dapat melihat pegawai dalam unit kerjanya.
* **Response (200 OK):** Mengembalikan objek `data` pegawai ASN lengkap beserta data relasi `unit`.

#### 4. Perbarui Data Pengguna ASN (`PUT /api/v1/users/{id}`)
* **Endpoint:** `PUT /api/v1/users/{id}`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Otorisasi:** `UserPolicy::update`. Administrator dapat memperbarui semua pegawai. Admin Unit hanya dapat memperbarui staf dalam unit kerjanya.
* **Fitur Normalisasi Toleran & Pembaruan Parsial:**
  - Mendukung update parsial (field yang tidak dikirim tidak akan menimpa data yang sudah ada).
  - Sanitasi awalan `@` pada username dan auto-resolving string `unit` ke `unit_id`.
  - Jika kata sandi dikosongkan, kata sandi lama tetap dipertahankan. Jika kata sandi diubah atau akun dinonaktifkan, seluruh sesi Bearer Token Sanctum aktif akun tersebut langsung dicabut demi keamanan (*enterprise token revocation*).
* **Payload Request:**
  ```json
  {
    "name": "Syahril Amri, S.Kom., M.T.",
    "username": "@syahrilamri",
    "email": "syahril.amri@lldikti.go.id",
    "role": "Admin Unit",
    "unit": "Subbagian Sarana & Prasarana",
    "status": "Aktif",
    "phone": "081234567899",
    "jabatan": "Pengelola Pengadaan Barang/Jasa Ahli Muda",
    "pangkat_golongan": "Penata (Gol. III/c)"
  }
  ```
* **Response Sukses (200 OK):** Mengembalikan objek data pengguna terbaru (`UserResource`).

#### 5. Hapus Pengguna ASN (`DELETE /api/v1/users/{id}`)
* **Endpoint:** `DELETE /api/v1/users/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** `UserPolicy::delete` (Administrator & Admin Unit scoped).
* **Aturan Durabilitas & Integritas Arsip:**
  - Akun pegawai yang **telah memiliki riwayat presensi rapat** atau **pernah membuat agenda rapat kedinasan** dilindungi dari penghapusan fisik demi integritas jejak audit pemerintahan. Permintaan akan ditolak dengan `422 Unprocessable Entity` disertai instruksi untuk menonaktifkan akun.
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "message": "Pengguna 'Syahril Amri, S.Kom.' berhasil dihapus."
  }
  ```
* **Response Ditolak demi Integritas (422 Unprocessable Entity):**
  ```json
  {
    "success": false,
    "message": "Pengguna 'Drs. M. Syahril, M.M.' memiliki riwayat presensi rapat kedinasan. Non-aktifkan akun alih-alih menghapusnya demi integritas data arsip."
  }
  ```

#### 6. Toggle Status Aktif / Nonaktif Akun (`PATCH /api/v1/users/{id}/toggle-status`)
* **Endpoint:** `PATCH /api/v1/users/{id}/toggle-status`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** `UserPolicy::update`. Pengguna tidak diizinkan menonaktifkan akunnya sendiri.
* **Fitur Keamanan:** Saat akun dinonaktifkan, seluruh personal access token Sanctum milik pengguna tersebut seketika dimusnahkan.
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "message": "Akun 'Drs. M. Syahril, M.M.' berhasil dinonaktifkan.",
    "data": { ... }
  }
  ```

---

### 4.6. Manajemen Unit Kerja (Units)

#### 1. Daftar Unit Kerja (`GET /api/v1/units`)
* **Endpoint:** `GET /api/v1/units`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Terbuka bagi seluruh pengguna terautentikasi (Administrator, Admin Unit, Staff) untuk keperluan direktori dan pilihan filter/dropdown.
* **Query Parameters:**
  | Parameter | Tipe | Contoh | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `page` | `integer` | `1` | Nomor halaman paginasi (default: 1). |
  | `per_page` | `integer` | `10` | Jumlah item per halaman (min 1, max 50, default: 15). |
  | `search` | `string` | `Sarana` | Pencarian teks pada Nama Unit, Kode Unit, atau Deskripsi. |
  | `status` | `string` | `active` / `aktif` | Filter status keaktifan (`active`/`inactive`, `aktif`/`nonaktif`). |
  | `all` | `boolean` | `true` | Jika `true`, mengembalikan seluruh data tanpa paginasi (cocok untuk dropdown list). |
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Daftar unit kerja berhasil diambil.",
    "data": [
      {
        "id": 4,
        "nama_unit": "Subbagian Sarana & Prasarana",
        "kode_unit": "SARPRAS",
        "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
        "is_active": true,
        "users_count": 8,
        "agendas_count": 14,
        "created_at": "2026-09-01T08:00:00+07:00",
        "updated_at": "2026-10-19T08:00:00+07:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 10,
      "total": 12
    },
    "summary": {
      "total_units": 12,
      "active_units": 12,
      "inactive_units": 0,
      "total_users_assigned": 79
    }
  }
  ```

#### 2. Tambah Unit Kerja (`POST /api/v1/units`)
* **Endpoint:** `POST /api/v1/units`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Otorisasi:** Khusus Administrator (`UnitPolicy::create`).
* **Payload Request:**
  ```json
  {
    "kode_unit": "POKJA-IKU",
    "nama_unit": "Kelompok Kerja Indikator Kinerja Utama",
    "deskripsi": "Pengawalan dan monev capaian IKU perguruan tinggi",
    "is_active": true
  }
  ```
* **Response Sukses (201 Created):** Mengembalikan objek `data` unit baru (`UnitResource`).

#### 3. Detail Unit Kerja (`GET /api/v1/units/{id}`)
* **Endpoint:** `GET /api/v1/units/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** `UnitPolicy::view`. Administrator dapat melihat unit manapun. Admin Unit diizinkan melihat detail unit kerjanya sendiri. (Mengakses unit lain tanpa hak akan menghasilkan `403 Forbidden`).
* **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Detail unit kerja berhasil diambil.",
    "data": {
      "id": 4,
      "nama_unit": "Subbagian Sarana & Prasarana",
      "kode_unit": "SARPRAS",
      "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
      "is_active": true,
      "users_count": 8,
      "agendas_count": 14,
      "created_at": "2026-09-01T08:00:00+07:00",
      "updated_at": "2026-10-19T08:00:00+07:00"
    }
  }
  ```

#### 4. Perbarui Unit Kerja (`PUT /api/v1/units/{id}`)
* **Endpoint:** `PUT /api/v1/units/{id}`
* **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Otorisasi:** Khusus Administrator (`UnitPolicy::update`).
* **Response Sukses (200 OK):** Mengembalikan objek `data` unit terbaru.

#### 5. Hapus Unit Kerja (`DELETE /api/v1/units/{id}`)
* **Endpoint:** `DELETE /api/v1/units/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Khusus Administrator (`UnitPolicy::delete`).
* **Proteksi Integritas Relasional:** Unit kerja yang masih memiliki relasi aktif dengan pegawai atau agenda rapat akan ditolak penghapusannya dengan `422 Unprocessable Entity`.
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "message": "Unit kerja 'POKJA-IKU' berhasil dihapus."
  }
  ```

#### 6. Toggle Status Aktif / Nonaktif Unit Kerja (`PATCH /api/v1/units/{id}/toggle-status`)
* **Endpoint:** `PATCH /api/v1/units/{id}/toggle-status`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Khusus Administrator (`UnitPolicy::update`).
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "message": "Unit kerja 'Subbagian Sarana & Prasarana' berhasil dinonaktifkan.",
    "data": { ... }
  }
  ```

---

### 4.7. Profil Akun & Log Aktivitas (Profile)

* **`GET /api/v1/profile`**: Mengambil data profil lengkap akun pengguna aktif.
  * **Headers:** `Authorization: Bearer <token>`
  * **Deskripsi:** Mengembalikan profil data ASN yang sedang login, mencakup unit kerja, jabatan kedinasan resmi, pangkat & golongan ruang, metadata keamanan akun, dan blok telemetri performa kehadiran 3-kolom.
  * **Response (200 OK):**
    ```json
    {
      "success": true,
      "message": "Profil pengguna berhasil diambil.",
      "data": {
        "id": 14,
        "name": "Drs. M. Syahril, M.M.",
        "nip": "196803151994031003",
        "username": "msyahril",
        "email": "syahril@lldikti.go.id",
        "phone": "081266128901",
        "jabatan": "Admin Subbagian Sarana & Prasarana",
        "pangkat_golongan": "Penata Tingkat I (Gol. III/d)",
        "avatar_url": null,
        "role": {
          "value": "admin_unit",
          "label": "Admin Unit"
        },
        "is_active": true,
        "unit_id": 4,
        "unit": {
          "id": 4,
          "nama_unit": "Subbagian Sarana & Prasarana",
          "kode_unit": "SARPRAS",
          "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
          "is_active": true
        },
        "security": {
          "last_password_changed_at": "2026-09-19T10:15:00+07:00",
          "last_password_changed_label": "Terakhir diperbarui 30 hari yang lalu",
          "last_login_at": "2026-10-19T08:15:20+07:00",
          "last_login_label": "Baru saja"
        },
        "attendance_stats": {
          "total_agendas": 24,
          "total_agendas_label": "24",
          "total_attended": 23,
          "attendance_rate": 98.5,
          "attendance_rate_label": "98.5%",
          "on_time_count": 23,
          "on_time_rate": 100.0,
          "on_time_rate_label": "100%",
          "period_label": "Tahun Anggaran 2026"
        },
        "created_at": "2026-09-01T08:00:00+07:00",
        "updated_at": "2026-10-19T08:15:20+07:00"
      }
    }
    ```

* **`PUT /api/v1/profile`**: Mengubah profil pengguna (Mendukung Pembaruan Parsial).
  * **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
  * **Dukungan Pembaruan Parsial (Partial Update):**
    Pengembang mobile dapat mengirimkan hanya field yang ingin diubah. Backend secara cerdas melakukan *fallback auto-fill* ke nama dan email saat ini jika tidak disertakan dalam payload:
    - Contoh Pembaruan Kontak WhatsApp Saja:
      ```json
      {
        "phone": "081266128901"
      }
      ```
    - Contoh Pembaruan Lengkap:
      ```json
      {
        "name": "Drs. M. Syahril, M.M.",
        "email": "syahril@lldikti.go.id",
        "phone": "081266128901"
      }
      ```
  * **Response Sukses (200 OK):**
    ```json
    {
      "success": true,
      "message": "Profil akun Anda berhasil diperbarui.",
      "data": {
        "id": 14,
        "name": "Drs. M. Syahril, M.M.",
        "nip": "196803151994031003",
        "username": "msyahril",
        "email": "syahril@lldikti.go.id",
        "phone": "081266128901",
        "jabatan": "Admin Subbagian Sarana & Prasarana",
        "pangkat_golongan": "Penata Tingkat I (Gol. III/d)",
        "avatar_url": null,
        "role": {
          "value": "admin_unit",
          "label": "Admin Unit"
        },
        "is_active": true,
        "unit_id": 4,
        "unit": {
          "id": 4,
          "nama_unit": "Subbagian Sarana & Prasarana",
          "kode_unit": "SARPRAS",
          "deskripsi": "Pengelolaan sarana dan prasarana kantor LLDIKTI",
          "is_active": true
        },
        "created_at": "2026-09-01T08:00:00+07:00",
        "updated_at": "2026-10-19T08:30:00+07:00"
      }
    }
    ```
* **`PUT /api/v1/profile/password`** *(Alias: `PUT /api/v1/auth/password`)*: Mengubah kata sandi akun pengguna terautentikasi dengan token rotation otomatis (`throttle:password`).
  * **Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`
  * **Middleware:** `auth:sanctum`, `throttle:password` (maksimal 5 request per menit)
  * **Otorisasi:** Seluruh pengguna aktif terautentikasi (Administrator, Admin Unit, Staff).
  * **Request Body (JSON):**
    ```json
    {
      "current_password": "PasswordLama123",
      "password": "PasswordBaru456",
      "password_confirmation": "PasswordBaru456",
      "device_name": "SIPERAPAT Mobile"
    }
    ```
    *(Mendukung juga alias penamaan field mobile: `new_password` untuk `password`, dan `confirm_password` / `new_password_confirmation` untuk `password_confirmation`).*
  * **Aturan Kompleksitas Sandi (Gov-Tech Standard):**
    - `current_password`: Wajib, sesuai dengan kata sandi saat ini.
    - `password`: Wajib, minimal 8 karakter, kombinasi huruf besar & kecil (`mixedCase`), sekurang-kurangnya satu angka (0-9), berbeda dari kata sandi saat ini (`different:current_password`), dan cocok dengan konfirmasi (`confirmed`).
    - `password_confirmation`: Wajib.
  * **Response Sukses (200 OK):**
    ```json
    {
      "success": true,
      "message": "Kata sandi akun Anda berhasil diperbarui.",
      "data": {
        "token": "49|b2x9qK8sL0m...",
        "token_type": "Bearer",
        "expires_in_days": 30,
        "user": {
          "id": 14,
          "name": "Drs. M. Syahril, M.M.",
          "nip": "196803151994031003",
          "email": "syahril@lldikti.go.id",
          "phone": "081234567890",
          "jabatan": "Pengelola Sarana Prasarana",
          "role": {
            "value": "staff",
            "label": "Pegawai ASN"
          },
          "unit": {
            "id": 4,
            "nama_unit": "Subbagian Sarana & Prasarana"
          }
        }
      }
    }
    ```
  * **Respons Error Validasi (422 Unprocessable Entity):**
    ```json
    {
      "message": "Kata sandi baru harus memuat kombinasi huruf besar dan huruf kecil.",
      "errors": {
        "password": [
          "Kata sandi baru harus memuat kombinasi huruf besar dan huruf kecil."
        ]
      }
    }
    ```
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

---

### 5.10. Panduan Integrasi Khusus Layar Ubah Kata Sandi (ChangePasswordScreen.tsx)

Dokumen panduan ini ditujukan bagi pengembang aplikasi mobile React Native (`lldikti-x-mobile/siperapat`) untuk mengintegrasikan layar **Ubah Kata Sandi** ([`ChangePasswordScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/ChangePasswordScreen.tsx)) dengan backend Laravel 13 SIPERAPAT secara mulus (*seamless*), aman (*enterprise security*), dan andal (*high durability*).

#### A. Ketersediaan Endpoint & Naming Parameter
1. **Endpoint Resmi:**
   - Primary: `PUT /api/v1/profile/password`
   - Alias: `PUT /api/v1/auth/password` (dapat dipanggil melalui `authApi` atau `userApi`).
2. **Fleksibilitas Parameter Request (Auto-Normalized):**
   Backend mendukung penamaan parameter standar Laravel maupun alias camelCase/snake_case klien mobile:
   - Kata sandi lama: `current_password` (string, required)
   - Kata sandi baru: `password` atau `new_password` (string, required)
   - Konfirmasi sandi: `password_confirmation` atau `confirm_password` atau `new_password_confirmation` (string, required)
   - Nama perangkat: `device_name` (string, opsional, default: `"SIPERAPAT Mobile"`)

#### B. Implementasi Datasource & Repository Mobile
Perbarui implementasi `authApi.ts` dan `AuthRepositoryImpl.ts` di proyek mobile:

```typescript
// 1. Pada src/data/datasources/authApi.ts (atau userApi.ts)
export const authApi = {
  // ...
  changePassword: (currentPassword: string, newPassword: string, deviceName = 'SIPERAPAT Mobile') =>
    apiClient.put<ApiEnvelope<{ token: string; token_type: 'Bearer'; expires_in_days: number; user: ApiUserDto }>>(
      '/profile/password',
      {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: newPassword,
        device_name: deviceName,
      }
    ),
};

// 2. Pada src/data/repositories/AuthRepositoryImpl.ts
async changePassword(currentPassword: string, newPassword: string): Promise<boolean> {
  const response = await authApi.changePassword(currentPassword, newPassword);
  if (!response.success) {
    throw new Error(response.message || 'Gagal memperbarui kata sandi akun.');
  }

  // PENTING: Simpan token rotasi baru ke SecureStore
  // Sesuai AGENTS.md Rule 3.6, seluruh token aktif lama otomatis dicabut di server.
  // Menyimpan token baru di sini menjamin sesi pengguna tetap aktif di perangkat ini tanpa logout paksa.
  if (response.data?.token) {
    await authTokenStore.set(response.data.token, true);
  }

  return true;
}
```

#### C. Alur Token Rotation (Keamanan Berlapis AGENTS.md §3.6)
* **Mengapa Token Lama Dicabut:**
  Sesuai standar keamanan instansi, saat terjadi perubahan kata sandi, seluruh token aktif pengguna pada perangkat lain atau sesi yang berpotensi disusupi seketika dimusnahkan (`$user->tokens()->delete()`).
* **Mengapa Disertakan Token Baru:**
  Respons sukses 200 OK langsung menyertakan payload `token` terotasi baru. Klien mobile wajib mengupdate token via `authTokenStore.set(response.data.token)`. Dengan demikian, ketika modal feedback sukses ditutup dan pengguna kembali ke Profil (`onSuccess()`), seluruh panggilan API berikutnya (seperti refresh profil atau cek presensi) tetap berjalan lancar tanpa mengalami *HTTP 401 Unauthorized*.

#### D. Penyelarasan Kriteria Keamanan Sandi (Anti-UI-Slop)
Empat kriteria validasi di `ChangePasswordScreen.tsx` telah disinkronkan 1:1 di backend:
1. `hasMinLength` (≥ 8 karakter) ➔ Aturan backend `Password::min(8)`.
2. `hasUpperAndLower` (Huruf besar A-Z dan kecil a-z) ➔ Aturan backend `->mixedCase()`.
3. `hasNumber` (Sekurang-kurangnya satu angka 0-9) ➔ Aturan backend `->numbers()`.
4. `isDifferentFromCurrent` (Berbeda dari sandi saat ini) ➔ Aturan backend `different:current_password`.
5. `isMatch` (Konfirmasi kata sandi cocok) ➔ Aturan backend `confirmed`.

Jika terdapat kriteria yang dilanggar, backend mengembalikan HTTP 422 dengan pesan formal dalam Bahasa Indonesia (PUEBI/EYD) yang dapat langsung dipetakan ke state `errorMessage` di antarmuka mobile.

#### E. Proteksi Brute-Force (Rate Limiting)
Endpoint dilindungi oleh `throttle:password` dengan batasan **5 percobaan per menit per user ID/IP**. Jika melampaui batas, backend mengembalikan HTTP 429 Too Many Requests.

---

### 5.11. Panduan Integrasi Khusus Layar Profil ASN (ProfileScreen.tsx)

Dokumen panduan ini ditujukan bagi pengembang aplikasi mobile React Native (`lldikti-x-mobile/siperapat`) untuk mengintegrasikan layar **Profil ASN** ([`ProfileScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/ProfileScreen.tsx)) dengan endpoint backend Laravel 13 SIPERAPAT secara mulus (*seamless*), menghapus seluruh nilai statis (*zero hardcoded data*), dan mengimplementasikan pembaruan profil yang reaktif.

#### A. Pemetaan Komponen Antarmuka Mobile ke Payload API

| Bagian Tampilan `ProfileScreen.tsx` | Elemen Antarmuka di Mobile | Sumber Data Backend (`UserResource`) | Keterangan & Fallback |
| :--- | :--- | :--- | :--- |
| **Hero Card (Identitas)** | Nama Pegawai | `user.name` | Nama lengkap ASN beserta gelar kedinasan resmi |
| | Jabatan Kedinasan | `user.jabatan` | Terformat otomatis: *"Administrator Sistem Instansi"*, *"Admin Subbagian ..."*, atau *"Pegawai ASN • Subbagian ..."* |
| | Sub-baris Pangkat & Unit Kerja | `user.pangkat_golongan` • `user.unit.nama_unit` | Terformat otomatis: *"Penata Tingkat I (Gol. III/d) • Subbagian ..."* |
| **Grid 3-Kolom Statistik Kehadiran** | Total Agenda (Kolom 1) | `user.attendance_stats.total_agendas_label` | Jumlah total rapat relevan yang diselenggarakan |
| | Kehadiran (Kolom 2) | `user.attendance_stats.attendance_rate_label` | Persentase kehadiran rapat berstatus selesai (contoh: `"98.5%"`) |
| | Tepat Waktu (Kolom 3) | `user.attendance_stats.on_time_rate_label` | Persentase presensi ASN sebelum/saat rapat dimulai (contoh: `"100%"`) |
| **Informasi Akun Kedinasan** | NIP | `user.nip` | 18 digit NIP resmi ASN (tampilan readonly) |
| | Username | `user.username` | Identifier login akun sistem |
| | Email Kedinasan | `user.email` | Alamat email resmi instansi |
| | WhatsApp Kedinasan | `user.phone` | Nomor WhatsApp kontak dinamis (dapat diubah) |
| | Hak Akses | `user.role.label` | Label dinamis: *"Administrator"*, *"Admin Unit"*, atau *"Pegawai ASN"* (menghapus string hardcoded *"Superadmin Wilayah"*) |
| **Keamanan Akun** | Subteks Kata Sandi | `user.security.last_password_changed_label` | Human-readable: *"Hari ini"*, *"Kemarin"*, atau *"Terakhir diperbarui X hari yang lalu"* (menghapus string hardcoded 30 hari) |

#### B. Pembaruan Kontak WhatsApp Kedinasan (Partial Update)
Pada `ProfileScreen.tsx`, field WhatsApp bersifat *editable*. Mobile developer dapat menambahkan tombol simpan atau *auto-save on blur* dengan memanggil `PUT /api/v1/profile`:

```typescript
// Contoh pemanggilan partial update dari ProfileScreen.tsx:
const handleUpdateWhatsApp = async (newPhone: string) => {
  try {
    const res = await userApi.updateProfile({ phone: newPhone });
    if (res.success && res.data) {
      // Perbarui user state di useAuthStore
      useAuthStore.getState().setUser(toAsn(res.data));
      useFeedbackStore.getState().showToast({
        type: 'success',
        message: 'Nomor WhatsApp kedinasan berhasil diperbarui.',
      });
    }
  } catch (err: any) {
    useFeedbackStore.getState().showToast({
      type: 'error',
      message: err.message || 'Gagal memperbarui nomor WhatsApp.',
    });
  }
};
```

#### C. Penyesuaian `apiMappers.ts` di Aplikasi Mobile
Perbarui mapper `toAsn()` pada `src/data/mappers/apiMappers.ts`:
```typescript
export function toAsn(user: ApiUserDto): ASN {
  return {
    id: String(user.id),
    nip: user.nip ?? '',
    name: user.name,
    pangkatGolongan: user.pangkat_golongan ?? '',
    jabatan: user.jabatan ?? user.role.label,
    unitKerja: user.unit?.nama_unit ?? '',
    email: user.email ?? '',
    phone: user.phone ?? '',
    avatarUrl: user.avatar_url ?? undefined,
    username: user.username,
    role: user.role.value,
    roleLabel: user.role.label,
    biometricEnabled: false,
    attendanceStats: user.attendance_stats,
    security: user.security,
  };
}
```

#### D. Penyesuaian JSX `ProfileScreen.tsx` (Anti-UI-Slop & Zero-Hardcoded)
1. **Grid Statistik Kehadiran (Baris 97–114):**
   ```tsx
   <View style={styles.perfGrid}>
     <View style={styles.perfCol}>
       <Text style={styles.perfLabel}>Total Agenda</Text>
       <Text style={styles.perfValue}>
         {user?.attendanceStats?.total_agendas_label ?? '0'}
       </Text>
       <Text style={styles.perfSub}>Rapat & Sidang</Text>
     </View>

     <View style={styles.perfCol}>
       <Text style={styles.perfLabel}>Kehadiran</Text>
       <Text style={styles.perfValue}>
         {user?.attendanceStats?.attendance_rate_label ?? '100%'}
       </Text>
       <Text style={styles.perfSub}>Tervalidasi</Text>
     </View>

     <View style={styles.perfCol}>
       <Text style={styles.perfLabel}>Tepat Waktu</Text>
       <Text style={styles.perfValue}>
         {user?.attendanceStats?.on_time_rate_label ?? '100%'}
       </Text>
       <Text style={styles.perfSub}>Presensi Awal</Text>
     </View>
   </View>
   ```

2. **Hak Akses Dinamis (Baris 168–176):**
   ```tsx
   <View style={styles.fieldItem}>
     <Text style={styles.fieldLabel}>Hak Akses</Text>
     <View style={styles.readonlyInputRow}>
       <MaterialIcons name="security" size={18} color={colors.outline} />
       <Text style={styles.readonlyInputValueBold}>
         {user?.roleLabel ?? user?.jabatan ?? 'Pegawai ASN'}
       </Text>
     </View>
   </View>
   ```

3. **Subteks Keamanan Kata Sandi Dinamis (Baris 185–189):**
   ```tsx
   <View style={{ flex: 1 }}>
     <Text style={styles.authActionTitle}>Kata Sandi</Text>
     <Text style={styles.authActionSub}>
       {user?.security?.last_password_changed_label ?? 'Belum pernah diubah'}
     </Text>
   </View>
   ```

---

### 5.12. Panduan Integrasi Khusus Layar Kelola Pegawai & Unit Kerja (`EmployeeDirectoryScreen`, `Add/Edit Employee & Unit`)

Dokumen panduan ini ditujukan bagi pengembang aplikasi mobile React Native (`lldikti-x-mobile/siperapat`) untuk mengintegrasikan layar **Kelola Pegawai & Unit Kerja** ([`EmployeeDirectoryScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/EmployeeDirectoryScreen.tsx)), formulir pendaftaran ([`AddEmployeeScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/AddEmployeeScreen.tsx)), penyuntingan pegawai ([`EditEmployeeScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/EditEmployeeScreen.tsx)), serta manajemen unit kerja ([`AddUnitScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/AddUnitScreen.tsx), [`EditUnitScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/EditUnitScreen.tsx)).

#### A. Pemetaan 4 Bento Stat Cards Direktori Pegawai (`EmployeeDirectoryScreen.tsx`)

Pada implementasi mobile sebelumnya, Bento Card 1–3 menghitung angka hanya dari data yang termuat pada halaman paginasi aktif `(halaman ini)`, sedangkan Bento Card 4 menampilkan nilai kosong `—` dengan subteks *"Belum tersedia di API"*.

Backend SIPERAPAT kini menyertakan blok telemetri `summary` pada respons `GET /api/v1/users` sehingga seluruh kartu statistik menyajikan angka real-time seluruh instansi (atau unit kerja terkait):

| Bento Stat Card di Mobile | Parameter UI Mobile | Sumber Data Backend (`response.summary`) | Keterangan & Dampak UI |
| :--- | :--- | :--- | :--- |
| **Kartu 1: Total Pegawai** | Nilai Utama (`bentoValue`) | `summary.total_users` | Total seluruh ASN terdaftar (bukan hanya halaman ini). |
| | Sub-keterangan (`bentoSub`) | `${summary.staff_users} Staf · ${summary.admin_users} Admin` | Rincian staf dan admin instansi. |
| **Kartu 2: Unit Kerja Aktif** | Nilai Utama (`bentoValue`) | `summary.active_units` | Jumlah unit kerja berstatus aktif. |
| | Sub-keterangan (`bentoSub`) | `"Unit aktif instansi"` | Keterangan status keaktifan unit. |
| **Kartu 3: Status Akun** | Nilai Utama (`bentoValue`) | `summary.active_users` | Jumlah akun ASN berstatus aktif. |
| | Sub-keterangan (`bentoSub`) | `${summary.inactive_users} Nonaktif` | Jumlah akun nonaktif instansi. |
| **Kartu 4: Rerata Presensi** | Nilai Utama (`bentoValue`) | `summary.attendance_rate_label` | **Contoh: `"92.4%"`** (menghapus tanda strip `—`). |
| | Sub-keterangan (`bentoSub`) | `summary.attendance_rate_subtext` | **Contoh: `"Rata-rata Seluruh ASN (14 Rapat)"`** (menghapus label *"Belum tersedia di API"*). |

#### B. Solusi Pembuatan Akun Pegawai ASN (`AddEmployeeScreen.tsx`)

Sebelumnya, repositori mobile melempar eksepsi error pada `EmployeeRepositoryImpl.ts#L35-L37`:
> *"Pembuatan akun memerlukan password awal, sedangkan formulir mobile belum menyediakan field tersebut."*

Backend SIPERAPAT telah dimutakhirkan dengan prinsip *Robustness Principle* (Postel's Law):
1. **Fallback Default Password Kedinasan:**
   Jika payload `POST /api/v1/users` tidak memuat field `password`, backend secara otomatis menerapkan kata sandi mula kedinasan standar instansi:
   ```
   Password123!
   ```
   Pegawai bersangkutan dapat mengubah kata sandi ini sewaktu-waktu melalui fitur *Ubah Kata Sandi* di aplikasi mobile.
2. **Sanitasi Awalan `@` pada Username:**
   Field username yang dikirim dengan format `@johndoe` akan secara otomatis dibersihkan menjadi `johndoe` sehingga lolos validasi `alpha_dash`.
3. **Resolusi Nama Unit String ke `unit_id`:**
   Jika mobile mengirimkan field `unit` berupa nama unit kerja (contoh: `"Subbagian Kepegawaian"`), backend otomatis mencari dan mengaitkan `unit_id` yang sesuai tanpa perlu iterasi manual di sisi klien.
4. **Normalisasi Role & Status:**
   Backend menerima label UI Bahasa Indonesia (`"Administrator"`, `"Admin Unit"`, `"Staff"` serta status `"Aktif"`, `"Nonaktif"`).

#### C. Contoh Implementasi `createEmployee` pada `EmployeeRepositoryImpl.ts`

Pengembang mobile dapat mengaktifkan fungsi pendaftaran pegawai di `EmployeeRepositoryImpl.ts` dengan kode berikut:

```typescript
// src/data/repositories/EmployeeRepositoryImpl.ts
async createEmployee(employee: Omit<Employee, 'id' | 'initials'>): Promise<Employee> {
  const role = employee.role === 'Administrator' ? 'administrator' : employee.role === 'Admin Unit' ? 'admin' : 'staff';
  const isActive = employee.status === 'Aktif';

  const response = await userApi.create({
    name: employee.name,
    nip: employee.nip,
    username: employee.username.replace(/^@/, ''),
    email: employee.email,
    role: role,
    unit: employee.unit, // Backend otomatis menyelesaikan nama unit ke unit_id
    unit_id: employee.unitId,
    status: employee.status,
    is_active: isActive,
    phone: employee.phone || null,
    jabatan: employee.jabatan,
    pangkat_golongan: employee.pangkatGolongan,
    // Password tidak wajib dikirim; backend default ke 'Password123!'
  });

  if (!response.success || !response.data) {
    throw new Error(response.message || 'Gagal mendaftarkan pegawai ASN baru.');
  }

  return toEmployee(response.data);
}
```

#### D. Penyesuaian `EmployeeDirectoryScreen.tsx` untuk 4 Bento Cards

```tsx
{/* 4 BENTO STATS CARDS */}
<View style={styles.bentoGrid}>
  <View style={styles.bentoCol}>
    <View style={styles.bentoTop}>
      <Text style={styles.bentoLabel}>Total Pegawai</Text>
      <MaterialIcons name="group" size={16} color={colors.outline} />
    </View>
    <Text style={styles.bentoValue}>{userSummary?.total_users ?? totalCount}</Text>
    <Text style={styles.bentoSub}>
      {userSummary ? `${userSummary.staff_users} Staf · ${userSummary.admin_users} Admin` : `${staffCount} Staf · ${adminCount} Admin`}
    </Text>
  </View>

  <View style={styles.bentoCol}>
    <View style={styles.bentoTop}>
      <Text style={styles.bentoLabel}>Unit Kerja Aktif</Text>
      <MaterialIcons name="account-tree" size={16} color={colors.outline} />
    </View>
    <Text style={styles.bentoValue}>{userSummary?.active_units ?? (apiUnits.length ? apiUnits.filter((u) => u.is_active).length : '—')}</Text>
    <Text style={styles.bentoSub}>Unit aktif instansi</Text>
  </View>

  <View style={styles.bentoCol}>
    <View style={styles.bentoTop}>
      <Text style={styles.bentoLabel}>Status Akun</Text>
      <MaterialIcons name="badge" size={16} color={colors.outline} />
    </View>
    <Text style={styles.bentoValue}>{userSummary?.active_users ?? activeCount}</Text>
    <Text style={styles.bentoSub}>
      {userSummary ? `${userSummary.inactive_users} Nonaktif` : `${inactiveCount} Nonaktif`}
    </Text>
  </View>

  <View style={styles.bentoCol}>
    <View style={styles.bentoTop}>
      <Text style={styles.bentoLabel}>Rerata Presensi</Text>
      <MaterialIcons name="analytics" size={16} color={colors.outline} />
    </View>
    <Text style={styles.bentoValue}>{userSummary?.attendance_rate_label ?? '100%'}</Text>
    <Text style={styles.bentoSub}>{userSummary?.attendance_rate_subtext ?? 'Tingkat kehadiran dinas'}</Text>
  </View>
</View>
```

#### E. Otorisasi Detail Unit Kerja untuk Admin Unit (`UnitPolicy::view`)

Admin Unit Kerja kini dapat memanggil `GET /api/v1/units/{id}` untuk mengambil detail data unit kerjanya sendiri (misal saat membuka layar [`EditUnitScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/EditUnitScreen.tsx) atau melihat daftar staf unit). Backend memverifikasi bahwa `$user->unit_id === $unit->id`. Jika mencoba mengakses unit lain di luar wewenangnya, server merespons dengan aman via HTTP `403 Forbidden`.

---

### 5.13. Panduan Integrasi Khusus Layar Presensi Rapat Kedinasan (`PresensiScreen.tsx`)

Layar Presensi Rapat Kedinasan ([`PresensiScreen.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/screens/PresensiScreen.tsx)) adalah antarmuka inti bagi ASN untuk merekam bukti kehadiran rapat kedinasan secara sah. Layar ini menggabungkan tangkapan kamera swafoto dengan oval guide ([`FaceOvalOverlay.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/components/attendance/FaceOvalOverlay.tsx)), pembubuhan tanda tangan digital pada kanvas ([`DigitalSignaturePad.tsx`](file:///home/daffiq/Documents/Semester-5/lldikti-x-mobile/siperapat/src/presentation/components/attendance/DigitalSignaturePad.tsx)), kartu konteks verifikasi identitas & lokasi, serta pernyataan integritas sah kehadiran dinas.

#### A. Alur Kerja 3 Langkah Presensi Mobile

```
┌────────────────────────────────────────────────────────┐
│ 1. Mount & Portal Check                               │
│    GET /api/v1/agendas/{id}/attendance-portal          │
│    -> Periksa is_ongoing, is_eligible, has_attended   │
│    -> Muat verification_context (lokasi, waktu, IP)   │
└──────────────────────────┬─────────────────────────────┘
                           ▼
┌────────────────────────────────────────────────────────┐
│ 2. Biometric Capture & Signature                      │
│    - FaceOvalOverlay: Ambil swafoto JPEG (< 150 KB)   │
│    - DigitalSignaturePad: Tanda tangan PNG (< 30 KB)  │
│    - Centang Pernyataan Sah Integritas                │
└──────────────────────────┬─────────────────────────────┘
                           ▼
┌────────────────────────────────────────────────────────┐
│ 3. Check-In Submission & Navigasi                      │
│    POST /api/v1/agendas/{id}/attendances              │
│    Payload: { selfie_data, signature_data }           │
│    -> HTTP 201 Created / HTTP 200 OK                  │
│    -> onSuccess(record.id) -> Buka ReceiptScreen.tsx  │
└────────────────────────────────────────────────────────┘
```

#### B. Pemetaan Field `verification_context` pada Kartu Metadata Presensi

Pada `PresensiScreen.tsx`, pengembang mobile dapat mengonsumsi blok `verification_context` dari respons portal check untuk menggantikan label statis *"Diisi oleh server"* dan label hardcoded *"Validasi lokasi tidak disediakan API"*:

```tsx
{/* Step 3: Identity & Verification Metadata Card */}
<View style={styles.metadataCard}>
  <View style={styles.metaRow}>
    <Text style={styles.metaLabel}>NIP Pegawai ASN</Text>
    <Text style={styles.metaValueMono}>
      {portal?.verification_context?.user_identity?.nip ?? user?.nip ?? '—'}
    </Text>
  </View>

  <View style={styles.metaDivider} />

  <View style={styles.metaRow}>
    <Text style={styles.metaLabel}>Waktu & IP Sesi</Text>
    <Text style={styles.metaValue}>
      {portal?.verification_context?.server_time_label ?? 'Waktu Server Sah'} · IP: {portal?.verification_context?.client_ip ?? 'Terdeteksi'}
    </Text>
  </View>

  <View style={styles.metaDivider} />

  <View style={styles.metaRow}>
    <Text style={styles.metaLabel}>Lokasi Presensi</Text>
    <View style={styles.geofenceValidRow}>
      <MaterialIcons name="verified-user" size={14} color={colors.primary} />
      <Text style={styles.metaValidText}>
        {portal?.verification_context?.location_validation?.label ?? portal?.verification_context?.venue ?? 'Presensi Kedinasan LLDIKTI X'}
      </Text>
    </View>
  </View>
```

#### C. Standar Pengiriman Payload Base64 JSON

Klien seluler mengirimkan data presensi melalui endpoint `POST /api/v1/agendas/{id}/attendances` dalam format `application/json`:

```typescript
const record = await submitAttendance(meetingId, {
  selfie_data: selfieUri,      // "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
  signature_data: signatureSvg // "data:image/png;base64,iVBORw0KGgo..."
});

if (record) {
  onSuccess(record.id); // Navigasi langsung ke ReceiptScreen.tsx
}
```

*Catatan Rekayasa Backend:*
1. **Batas Ukuran:** Backend memvalidasi string data URI hingga 1.000.000 karakter (`max:1000000`), memberikan ruang yang aman untuk file gambar terkompresi hingga ~750 KB tanpa risiko memory exhaustion.
2. **Normalisasi String:** Backend secara otomatis membersihkan spasi liar atau baris baru (`\r\n`) yang mungkin disisipkan oleh encoder klien seluler saat serialisasi JSON.
3. **Penyimpanan:** File didekode dan disimpan sebagai file biner pada direktori `storage/app/public/attendances/{agenda_id}/` dengan hash acak unik. String base64 tidak pernah disimpan ke dalam kolom teks database sesuai standar keamanan enterprise.

#### D. Penanganan Idempotensi & Kode Status HTTP

1. **HTTP 201 Created (Presensi Baru):**
   - Dikembalikan saat presensi berhasil disimpan pertama kali.
   - Mengembalikan data presensi lengkap beserta `id` untuk diteruskan ke `ReceiptScreen.tsx`.
2. **HTTP 200 OK (Presensi Duplikat / Retry):**
   - Jika ASN melakukan submit berulang karena gangguan sinyal seluler atau retry otomatis, backend secara cerdas mengembalikan presensi yang telah tersimpan sebelumnya (`is_duplicate: true`) dengan pesan: *"Presensi Anda pada agenda rapat ini sudah pernah tercatat."*.
   - Klien seluler dapat langsung menavigasikan ASN ke `ReceiptScreen.tsx` dengan `record.id` yang sama tanpa menampilkan pesan error yang membingungkan.
3. **HTTP 403 Forbidden (Aturan Bisnis):**
   - Rapat belum dibuka: `"Sesi presensi untuk agenda rapat ini tidak sedang dibuka."`
   - Unit kerja tidak diundang: `"Unit kerja Anda tidak termasuk dalam daftar undangan agenda rapat ini."`
   - Sudah pernah presensi: `"Presensi Anda pada agenda rapat ini sudah pernah tercatat."`


