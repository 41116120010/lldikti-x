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
* **Response (200 OK):**
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
        "total_attendances": 142
      },
      "active_agendas": [ ... ],
      "recent_attendances": [ ... ]
    }
  }
  ```

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
      "is_eligible": true,
      "has_attended": false,
      "my_attendance": null,
      "notulensi_html": "<p>Rapat dibuka pukul 09.15 WIB oleh Pimpinan Rapat...</p>",
      "kesimpulan_html": "<p>Tindak lanjut perbaikan data semester genap dijadwalkan...</p>",
      "documentations": [ ... ]
    }
  }
  ```

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
* **Query Parameters:** `page`, `per_page`
* **Response:** Daftar riwayat kehadiran user saat ini.

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
* **`GET /api/v1/profile/logs`**: Riwayat log audit trail aktivitas pengguna.

---

### 4.8. Laporan & Ekspor Berkas (Reports)

* **`GET /api/v1/reports/summary`**: Statistik rekapitulasi rapat (total agenda, tingkat kehadiran).
* **`GET /api/v1/reports/summary/csv`**: Unduh berkas rekapitulasi agenda spreadsheet CSV/Excel (`throttle:export`). Otomatis disanitasi terhadap bahaya CSV Formula Injection (CWE-1236) dan menyertakan UTF-8 BOM.
* **`GET /api/v1/reports/agendas/{id}`**: Rekapitulasi agenda tunggal beserta tautan unduh dokumen.
* **`POST /api/v1/reports/agendas/{id}/config/reset`**: Mereset konfigurasi kop surat, penandatangan, dan tata letak dokumen agenda kembali ke standar sistem instansi.
* **`GET /api/v1/reports/agendas/{id}/export/pdf`**: Unduh dokumen resmi Berita Acara & Daftar Hadir format PDF (`throttle:export`).
* **`GET /api/v1/reports/agendas/{id}/export/word`**: Unduh dokumen resmi Berita Acara format Microsoft Word (.doc) (`throttle:export`).

---

### 4.9. Log Audit Seluruh Instansi (Activity Logs - Khusus Superadmin)

* **Endpoint:** `GET /api/v1/activity-logs`
* **Headers:** `Authorization: Bearer <token>`
* **Otorisasi:** Khusus peran `Administrator`. (Peran lain akan menerima respons `403 Forbidden`).
* **Query Parameters:**
  - `type`: Tipe aktivitas (contoh: `LOGIN`, `STORE_AGENDA`, `CHECK_IN`, `EXPORT_PDF_API`, dll.)
  - `user_id`: ID pengguna tertentu
  - `start_date`: Tanggal awal aktivitas (`Y-m-d`)
  - `end_date`: Tanggal akhir aktivitas (`Y-m-d`)
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
    }
  }
  ```

---

### 4.10. Matriks Hak Akses RBAC 42 Endpoint REST API v1

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
│ 23 │ GET    /api/v1/attendances/{id}/selfie         │ Ya            │ Ya (Scoped)│ Ya(Milik│
│ 24 │ GET    /api/v1/attendances/{id}/signature      │ Ya            │ Ya (Scoped)│ Ya(Milik│
│ 25 │ GET    /api/v1/users                           │ Ya (Semua)    │ Ya (Scoped)│ ❌ (403)│
│ 26 │ POST   /api/v1/users                           │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 27 │ GET    /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 28 │ PUT    /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 29 │ DELETE /api/v1/users/{id}                      │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 30 │ PATCH  /api/v1/users/{id}/toggle-status        │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 31 │ GET    /api/v1/units                           │ Ya (Semua)    │ Ya (Baca)  │ Ya(Baca)│
│ 32 │ POST   /api/v1/units                           │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 33 │ GET    /api/v1/units/{id}                      │ Ya            │ Ya         │ Ya      │
│ 34 │ PUT    /api/v1/units/{id}                      │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 35 │ DELETE /api/v1/units/{id}                      │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 36 │ PATCH  /api/v1/units/{id}/toggle-status        │ Ya            │ ❌ (403)   │ ❌ (403)│
│ 37 │ GET    /api/v1/reports/summary                 │ Ya (Semua)    │ Ya (Scoped)│ Ya      │
│ 38 │ GET    /api/v1/reports/summary/csv             │ Ya (Semua)    │ Ya (Scoped)│ Ya      │
│ 39 │ GET    /api/v1/reports/agendas/{id}            │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 40 │ POST   /api/v1/reports/agendas/{id}/config/rst │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 41 │ GET    /api/v1/reports/agendas/{id}/export/pdf │ Ya            │ Ya (Scoped)│ ❌ (403)│
│ 42 │ GET    /api/v1/reports/agendas/{id}/export/word│ Ya            │ Ya (Scoped)│ ❌ (403)│
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
