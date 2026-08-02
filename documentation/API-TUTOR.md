# API Reference — Portal Tutor (LIVO)

REST API berformat JSON untuk role **tutor**, diautentikasi dengan **Laravel Sanctum** (personal access token, stateless — bukan cookie/SPA). Dipakai untuk aplikasi client terpisah (mis. aplikasi mobile) yang mengakses fungsi yang sama dengan [Portal Tutor berbasis web](ALUR-DAN-MODUL-SISTEM-LIVO.md#10-modul-portal-tutor).

> Dokumen alur & modul sistem secara umum ada di [`ALUR-DAN-MODUL-SISTEM-LIVO.md`](ALUR-DAN-MODUL-SISTEM-LIVO.md). Dokumen ini fokus ke kontrak API: endpoint, payload, dan contoh response.

---

## Daftar Isi

1. [Dasar & Konvensi](#1-dasar--konvensi)
2. [Autentikasi](#2-autentikasi)
3. [Dashboard](#3-dashboard)
4. [Jadwal & Siswa](#4-jadwal--siswa)
5. [Evaluasi](#5-evaluasi)
6. [Profil](#6-profil)
7. [Rekapitulasi](#7-rekapitulasi)
8. [Laporan (PDF)](#8-laporan-pdf)
9. [Format Error](#9-format-error)
10. [Referensi Objek Data](#10-referensi-objek-data)
11. [Contoh Alur Lengkap (cURL)](#11-contoh-alur-lengkap-curl)

---

## 1. Dasar & Konvensi

| | |
|---|---|
| **Base URL** | `https://<domain-aplikasi>/api/tutor` |
| **Format** | JSON (`Content-Type: application/json`, kecuali endpoint upload foto: `multipart/form-data`) |
| **Autentikasi** | Bearer token (Laravel Sanctum) di header `Authorization: Bearer <token>` |
| **Role** | Seluruh endpoint terproteksi hanya menerima akun ber-role **tutor** yang tertaut ke data master Tutor |

**Header yang dianjurkan pada setiap request:**

```
Accept: application/json
Authorization: Bearer <token>   (untuk endpoint terproteksi)
```

**Format tanggal & jam:** tanggal `YYYY-MM-DD`, jam `HH:mm` (24 jam), bulan pada query `?month=` berformat `YYYY-MM`.

**Paginasi:** endpoint yang mengembalikan daftar data (riwayat siswa, evaluasi pending, rekap pengajaran) memakai paginator standar Laravel. Parameter query: `page` (default `1`), `per_page` (default `15`). Bentuk response:

```json
{
  "current_page": 1,
  "data": [ ... ],
  "first_page_url": "http://.../api/tutor/evaluations?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://.../api/tutor/evaluations?page=1",
  "links": [ ... ],
  "next_page_url": null,
  "path": "http://.../api/tutor/evaluations",
  "per_page": 15,
  "prev_page_url": null,
  "to": 3,
  "total": 3
}
```

Field `data` berisi array item — struktur tiap item dijelaskan per endpoint di bawah.

---

## 2. Autentikasi

Login 2 langkah (identik dengan alur web, versi stateless bertoken). Ketiga endpoint di bawah **publik** (tidak butuh token).

### 2.1 Cek Email

```
POST /api/tutor/auth/check-email
```

Mengecek apakah email terdaftar sebagai tutor. Bila email ditemukan di master **Tutor** namun belum punya akun, akun dibuat otomatis (status `pending`, tanpa password) — sama seperti alur web.

**Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `email` | string (email) | ✅ | Email tutor |

**Response `200 OK`**

```json
{
  "email": "budi@livo.co.id",
  "name": "Budi Santoso",
  "has_password": false
}
```

Gunakan `has_password` untuk menentukan langkah berikutnya di client:
- `false` → arahkan ke **2.3 Buat Password**
- `true` → arahkan ke **2.2 Login**

**Response `422 Unprocessable Entity`** — email tidak ditemukan sebagai tutor, atau akun berstatus `nonaktif`:

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["Email tidak terdaftar sebagai tutor. Hubungi admin bila Anda merasa ini keliru."] }
}
```

### 2.2 Login (akun sudah punya password)

```
POST /api/tutor/auth/login
```

**Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `email` | string (email) | ✅ | |
| `password` | string | ✅ | |
| `device_name` | string, maks 100 | — | Nama perangkat/klien, dipakai sebagai label token. Default `tutor-app` |

**Response `200 OK`**

```json
{
  "token": "1|abcdEXAMPLEtokenXYZ...",
  "token_type": "Bearer",
  "user": {
    "id": 12,
    "name": "Budi Santoso",
    "email": "budi@livo.co.id",
    "role": "tutor",
    "status": "aktif"
  },
  "tutor": {
    "id": 4,
    "name": "Budi Santoso",
    "photo": "tutors/xyz.jpg",
    "phone": "081234567890",
    "email": "budi@livo.co.id",
    "no_rekening": "BCA 1234567890",
    "fee_per_session": 75000,
    "fee_per_student_private": 50000,
    "fee_per_student": 25000,
    "fee_transport_per_day": 20000,
    "specialization": ["Matematika", "Fisika"]
  }
}
```

**Response `422`** — email/password salah, bukan akun tutor, atau akun `nonaktif` (pesan generik "Email atau password salah." untuk mencegah enumerasi akun).

### 2.3 Buat Password (login pertama kali)

```
POST /api/tutor/auth/create-password
```

Khusus akun hasil provisioning yang **belum** punya password. Berhasil → status akun otomatis `aktif` dan token langsung diterbitkan (tidak perlu login ulang).

**Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `email` | string (email) | ✅ | |
| `password` | string, min 8 | ✅ | |
| `password_confirmation` | string | ✅ | Harus sama dengan `password` |
| `device_name` | string, maks 100 | — | Default `tutor-app` |

**Response `200 OK`** — struktur identik dengan respons Login (2.2).

**Response `422`** — email bukan tutor, atau **akun sudah punya password** (harus pakai endpoint Login, bukan endpoint ini).

### 2.4 Info Akun Saat Ini

```
GET /api/tutor/auth/me
```
🔒 *Butuh token*

**Response `200 OK`**

```json
{
  "user": { "id": 12, "name": "Budi Santoso", "email": "budi@livo.co.id", "role": "tutor", "status": "aktif" },
  "tutor": { "id": 4, "name": "Budi Santoso", "...": "..." }
}
```

### 2.5 Logout

```
POST /api/tutor/auth/logout
```
🔒 *Butuh token*

Mencabut **token yang sedang dipakai** (hanya perangkat ini — token lain milik akun yang sama tetap aktif).

**Response `200 OK`**

```json
{ "message": "Berhasil logout." }
```

Setelah logout, token tersebut tidak bisa dipakai lagi (`401 Unauthenticated` pada request berikutnya).

---

## 3. Dashboard

```
GET /api/tutor/dashboard
```
🔒 *Butuh token*

Ringkasan akumulasi sesi & siswa, review hasil penilaian, dan evaluasi terbaru.

**Response `200 OK`**

```json
{
  "tutor": { "id": 4, "name": "Budi Santoso", "...": "..." },
  "stats": {
    "total_sessions": 128,
    "month_sessions": 14,
    "upcoming_sessions": 6,
    "total_students": 231,
    "month_students": 9,
    "pending_evaluations": 2
  },
  "review": {
    "evaluated": 120,
    "published": 98,
    "avg_post_test": 82.4,
    "avg_pemahaman": 80.1,
    "avg_analisa": 78.9,
    "avg_hafalan": 81.0,
    "avg_kepercayaan": 79.5,
    "hadir": 110,
    "izin": 8,
    "alfa": 2
  },
  "recent_evaluations": [
    {
      "id": 301,
      "student_name": "Siswa Uji",
      "subject_name": "Matematika",
      "class_date": "2026-07-10",
      "post_test": 90,
      "is_published": true
    }
  ]
}
```

**Definisi `stats` (disamakan dengan Portal Tutor web):**

| Field | Definisi |
|---|---|
| `total_sessions` / `month_sessions` | Jumlah **sesi unik** berstatus `done` — dikelompokkan per slot (tanggal + jam mulai + jam selesai). Beberapa siswa pada slot/jam yang sama tetap dihitung **1 sesi**, bukan per baris siswa. |
| `upcoming_sessions` | Jumlah sesi unik (grouping sama) berstatus `scheduled` mulai hari ini. |
| `total_students` | **Total kehadiran** dengan `student_attendance = hadir` sepanjang waktu — **tidak** di-distinct per siswa. Bila satu siswa hadir 10× dalam setahun, ia menyumbang 10 ke angka ini (bukan 1). |
| `month_students` | Jumlah **siswa unik** (distinct) yang dijadwalkan bulan ini, **tidak termasuk** jadwal berstatus `canceled`. |
| `pending_evaluations` | Sesi `done` yang belum ada evaluasinya. |

`review.*` dihitung dari **seluruh evaluasi** milik tutor ini (tidak difilter tanggal). `avg_*` bernilai `null` bila belum ada data untuk field tersebut.

> ⚠️ **Perhatian untuk klien lama:** `total_students` sebelumnya dihitung distinct per siswa; sejak versi ini nilainya **tidak lagi unik** (redundan/berulang per kehadiran), agar konsisten dengan tampilan web. Sesuaikan tampilan/label di aplikasi client bila sebelumnya mengasumsikan nilai unik.

---

## 4. Jadwal & Siswa

### 4.1 Jadwal Satu Minggu

```
GET /api/tutor/schedules/week
```
🔒 *Butuh token*

**Query**

| Param | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `week` | date (`YYYY-MM-DD`) | — | Tanggal mana pun dalam minggu yang ingin dilihat. Default: minggu berjalan. Minggu dihitung Senin–Minggu. |
| `search` | string | — | Filter berdasarkan **nama siswa** (partial match, tidak case-sensitive). Hanya sesi dengan siswa yang namanya mengandung kata kunci ini yang ditampilkan. |

**Response `200 OK`**

```json
{
  "start": "2026-07-06",
  "end": "2026-07-12",
  "prev_week": "2026-06-29",
  "next_week": "2026-07-13",
  "search": null,
  "total": 5,
  "days": ["2026-07-06", "2026-07-07", "2026-07-08", "2026-07-09", "2026-07-10", "2026-07-11", "2026-07-12"],
  "schedules_by_day": {
    "2026-07-06": [],
    "2026-07-07": [
      {
        "id": 501,
        "class_date": "2026-07-07",
        "start_time": "10:00",
        "end_time": "11:30",
        "room": "Kelas A",
        "status_schedule": "scheduled",
        "student": { "id": 8, "full_name": "Siswa Uji", "grade": "SMA 10" },
        "subject": { "id": 2, "subject_name": "Matematika" },
        "evaluation": null
      }
    ],
    "...": "..."
  }
}
```

`schedules_by_day` berisi 7 key (satu per hari Senin–Minggu sesuai `days`), masing-masing array sesi (bisa kosong). Field `evaluation` berisi objek ringkas bila sesi sudah dievaluasi, atau `null` bila belum. Field `search` menggemakan kembali kata kunci yang dikirim (`null` bila parameter `search` tidak disertakan); `total` mengikuti hasil yang sudah difilter oleh `search`.

### 4.2 Detail Siswa

```
GET /api/tutor/students/{student}
```
🔒 *Butuh token* · **Hanya siswa yang pernah/akan diajar tutor ini** — selain itu `403`.

**Response `200 OK`**

```json
{
  "student": {
    "id": 8,
    "full_name": "Siswa Uji",
    "nickname": "Uji",
    "grade": "SMA 10",
    "quota_sessions": 5,
    "...": "kolom lengkap tabel students"
  },
  "stats": {
    "total": 12,
    "done": 10,
    "evaluated": 10,
    "avg_post_test": 84.3,
    "hadir": 9,
    "izin": 1,
    "alfa": 0
  }
}
```

**Response `403`** bila siswa tersebut tidak pernah dijadwalkan bersama tutor ini:

```json
{ "message": "Siswa ini tidak terdaftar pada jadwal Anda." }
```

### 4.3 Riwayat Sesi Siswa (dipaginasi)

```
GET /api/tutor/students/{student}/history
```
🔒 *Butuh token* · proteksi kepemilikan sama seperti 4.2.

**Query:** `page`, `per_page` (lihat [konvensi paginasi](#1-dasar--konvensi)).

**Response `200 OK`** — paginator dengan `data` berisi:

```json
{
  "id": 501,
  "class_date": "2026-07-07",
  "start_time": "10:00",
  "end_time": "11:30",
  "room": "Kelas A",
  "status_schedule": "done",
  "student": { "id": 8, "full_name": "Siswa Uji", "grade": "SMA 10" },
  "subject": { "id": 2, "subject_name": "Matematika" },
  "evaluation": {
    "id": 301,
    "materi": { "pokok": "Aljabar", "sub": "Persamaan Linear" },
    "student_attendance": "hadir",
    "post_test": 90,
    "is_published": true
  }
}
```

---

## 5. Evaluasi

### 5.1 Daftar Sesi Belum Dievaluasi (dipaginasi)

```
GET /api/tutor/evaluations
```
🔒 *Butuh token*

Sesi milik tutor ini yang **belum ada evaluasi**, dan (a) berstatus `done`, atau (b) berstatus `scheduled` tapi tanggalnya sudah lewat.

**Query:** `page`, `per_page`, `search` (opsional — filter berdasarkan **nama siswa**, partial match tidak case-sensitive, sama seperti pada [4.1 Jadwal Satu Minggu](#41-jadwal-satu-minggu)).

**Item pada `data`:**

```json
{
  "id": 502,
  "class_date": "2026-07-11",
  "start_time": "13:00",
  "end_time": "14:30",
  "room": "Kelas B",
  "status_schedule": "done",
  "student": { "id": 9, "full_name": "Siswa Lain", "grade": "SMP Kelas 9" },
  "subject": { "id": 2, "subject_name": "Matematika" }
}
```

### 5.2 Detail Sesi untuk Form Evaluasi

```
GET /api/tutor/evaluations/{schedule}
```
🔒 *Butuh token* · **hanya sesi milik tutor ini** — selain itu `403`.

**Query**

| Param | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `search` | string | — | Filter tambahan pada `syllabi`: mencari kata kunci di **pokok bahasan** atau **sub pokok bahasan** (partial match, tidak case-sensitive). |

**Response `200 OK`**

```json
{
  "schedule": {
    "id": 502,
    "student_id": 9,
    "tutor_id": 4,
    "subject_id": 2,
    "class_date": "2026-07-11",
    "start_time": "13:00:00",
    "end_time": "14:30:00",
    "room": "Kelas B",
    "status_schedule": "done",
    "student": { "id": 9, "full_name": "Siswa Lain", "grade": "SMP Kelas 9", "...": "..." },
    "subject": { "id": 2, "subject_name": "Matematika" },
    "evaluation": null
  },
  "syllabi": [
    { "id": 11, "pokok_bahasan": "Aljabar", "sub_pokok_bahasan": "Persamaan Linear", "kelas": "SMP Kelas 9" },
    { "id": 12, "pokok_bahasan": "Geometri", "sub_pokok_bahasan": "Segitiga", "kelas": "SMP Kelas 9" }
  ]
}
```

`syllabi` adalah daftar pilihan materi — dipakai untuk mengisi `syllabus_id` pada endpoint simpan (5.3). Disaring otomatis sesuai **dua kriteria sekaligus**:

1. **Mata pelajaran** sesi ini (`schedule.subject_id`).
2. **Kelas/jenjang siswa** (`schedule.student.grade`) — hanya silabus dengan `kelas` yang sama persis dengan kelas siswa yang ditampilkan.

Tambahkan `?search=<kata kunci>` untuk mempersempit lebih lanjut berdasarkan teks pokok/sub pokok bahasan.

Catatan:
- Bila siswa **belum punya `grade`** tercatat, filter kelas dilewati (fallback) — seluruh silabus mata pelajaran tsb ditampilkan tanpa disaring kelas.
- `syllabi` selalu **kosong** bila sesi tidak punya `subject_id`.

### 5.3 Simpan Evaluasi

```
POST /api/tutor/evaluations/{schedule}
```
🔒 *Butuh token* · **hanya sesi milik tutor ini** — selain itu `403`.

Membuat evaluasi baru, atau **memperbarui** bila sesi ini sudah pernah dievaluasi sebelumnya (upsert berdasarkan `schedule_id`).

**Body**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `syllabus_id` | integer (exists) | — | Materi dari silabus. **Saling eksklusif** dengan `materi_manual` — bila diisi, `materi_manual` diabaikan/dikosongkan otomatis. |
| `materi_manual` | string, maks 255 | — | Materi bebas (dipakai bila `syllabus_id` kosong) |
| `student_attendance` | string: `hadir`\|`izin`\|`alfa` | ✅ | |
| `post_test` | integer 1–100 | — | |
| `pemahaman` | integer 1–100 | — | |
| `kemampuan_analisa` | integer 1–100 | — | |
| `kemampuan_hafalan` | integer 1–100 | — | |
| `kepercayaan_diri` | integer 1–100 | — | |
| `tutor_notes` | string, maks 1000 | — | |

**Efek samping otomatis:**
- Bila `status_schedule` sesi masih `scheduled`, otomatis diubah menjadi `done`.
- Kehadiran `hadir`/`alfa` memotong **1 kuota sesi** siswa (sekali saja, ditandai internal agar tidak terpotong dobel bila evaluasi diedit ulang). Kehadiran `izin` tidak memotong; mengubah dari status pemotong ke `izin` mengembalikan kuota.

**Response `200 OK`**

```json
{
  "message": "Evaluasi Siswa Lain berhasil disimpan.",
  "evaluation": {
    "id": 305,
    "schedule_id": 502,
    "syllabus_id": null,
    "materi_manual": "Aljabar Dasar",
    "student_attendance": "hadir",
    "post_test": 90,
    "pemahaman": 85,
    "kemampuan_analisa": null,
    "kemampuan_hafalan": null,
    "kepercayaan_diri": null,
    "tutor_notes": null,
    "is_published": false,
    "quota_consumed": true
  }
}
```

**Response `422`** — validasi gagal (lihat [format error](#9-format-error)). **Response `403`** — sesi bukan milik tutor ini.

---

## 6. Profil

### 6.1 Lihat Profil

```
GET /api/tutor/profile
```
🔒 *Butuh token*

```json
{
  "tutor": {
    "id": 4,
    "name": "Budi Santoso",
    "photo": "tutors/xyz.jpg",
    "phone": "081234567890",
    "email": "budi@livo.co.id",
    "no_rekening": "BCA 1234567890",
    "fee_per_session": 75000,
    "fee_per_student_private": 50000,
    "fee_per_student": 25000,
    "fee_transport_per_day": 20000,
    "specialization": ["Matematika", "Fisika"]
  }
}
```

### 6.2 Perbarui Profil

```
POST /api/tutor/profile
```
🔒 *Butuh token* · **`Content-Type: multipart/form-data`** (dipakai `POST`, bukan `PUT`, agar upload file berjalan)

Tutor **hanya boleh mengubah** kontak, no. rekening, dan foto. Nama, email, dan spesialisasi tetap dikelola admin.

**Body (form-data)**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `phone` | string, maks 20 | ✅ | |
| `no_rekening` | string, maks 50 | — | |
| `photo` | file gambar, maks 5120 KB | — | Foto lama otomatis dihapus saat diganti |

**Response `200 OK`**

```json
{
  "message": "Profil berhasil diperbarui.",
  "tutor": { "id": 4, "phone": "089999999999", "...": "..." }
}
```

---

## 7. Rekapitulasi

### 7.1 Rekap Pengajaran per Bulan (dipaginasi)

```
GET /api/tutor/rekap-pengajaran
```
🔒 *Butuh token*

**Query:** `month` (`YYYY-MM`, default bulan berjalan), `page`, `per_page`.

**Response `200 OK`**

```json
{
  "month": "2026-07",
  "stats": {
    "done": 18,
    "students": 16,
    "evaluated": 18,
    "avg_post_test": 83.2,
    "hadir": 16,
    "izin": 2,
    "alfa": 0
  },
  "schedules": {
    "current_page": 1,
    "data": [
      {
        "id": 501,
        "class_date": "2026-07-07",
        "start_time": "10:00",
        "end_time": "11:30",
        "student": { "id": 8, "full_name": "Siswa Uji" },
        "subject": { "id": 2, "subject_name": "Matematika" },
        "materi": { "pokok": "Aljabar", "sub": "Persamaan Linear" },
        "student_attendance": "hadir",
        "post_test": 90,
        "tutor_notes": null
      }
    ],
    "total": 18,
    "...": "field paginator lain"
  }
}
```

`stats` dihitung dari **seluruh** sesi selesai bulan tsb. (tidak ikut terpotong `per_page`); `schedules` adalah daftar rincinya yang dipaginasi.

- `done` = jumlah **sesi unik** (grouping per slot tanggal+jam), sama seperti Dashboard.
- `students` = **total kehadiran hadir** (sama persis dengan field `hadir` di bawahnya) — **bukan** jumlah siswa unik. Field ini sengaja redundan dengan `hadir` demi konsistensi dengan tampilan web.

### 7.2 Rekap Fee per Tahun

```
GET /api/tutor/rekap-fee
```
🔒 *Butuh token*

> ⚠️ **Perubahan alur (breaking change):** fee tutor **tidak lagi dihitung langsung (live)** dari jadwal setiap kali endpoint dipanggil. Admin kini menjalankan alur **generate → review → terbitkan** per bulan (lihat [dokumen alur & modul](ALUR-DAN-MODUL-SISTEM-LIVO.md)):
> 1. Admin memilih bulan lalu men-**generate** fee seluruh tutor untuk bulan itu (status `draft`).
> 2. Admin **mereview** rincian per tutor, boleh mengedit manual komponennya selagi masih `draft`.
> 3. Admin **menerbitkan** (`published`) — **baru pada titik ini** tutor bisa melihat fee bulan tsb.
>
> Selama status masih `draft` (atau belum pernah di-generate sama sekali), endpoint ini **tidak menampilkan nominal apa pun** ke tutor — bulan tsb tampil dengan `published: false` dan seluruh angka `0`. Ini untuk mencegah tutor melihat fee yang belum final/belum disetujui admin.

**Query:** `year` (default tahun berjalan).

**Response `200 OK`**

```json
{
  "year": 2026,
  "rates": {
    "session": 75000,
    "private": 50000,
    "student": 25000,
    "transport": 20000
  },
  "rows": [
    {
      "private_count": 0, "regular_count": 0, "session_count": 0, "day_count": 0,
      "fee_private": 0, "fee_regular": 0, "fee_session": 0, "fee_transport": 0, "total": 0,
      "month": 1, "month_label": "Januari", "published": false
    },
    {
      "private_count": 3, "regular_count": 20, "session_count": 12, "day_count": 10,
      "fee_private": 150000, "fee_regular": 500000, "fee_session": 900000, "fee_transport": 200000, "total": 1750000,
      "month": 2, "month_label": "Februari", "published": true
    }
  ],
  "totals": {
    "private_count": 3, "regular_count": 20, "session_count": 12, "day_count": 10,
    "fee_private": 150000, "fee_regular": 500000, "fee_session": 900000, "fee_transport": 200000, "total": 1750000
  }
}
```

`rows` selalu berisi **12 elemen** (Januari–Desember), dipotong pada contoh di atas agar ringkas. `totals` adalah penjumlahan seluruh `rows` sepanjang tahun (hanya bulan `published` yang bernilai > 0).

**Rincian fee = a + b + c + d**, dihitung admin saat generate dan disimpan apa adanya (bisa diedit manual sebelum terbit). Satu **sesi** = satu slot mengajar (tanggal + jam), boleh diisi banyak siswa sekaligus:

| Komponen | Field jumlah | Field nominal | Rumus |
|---|---|---|---|
| a) Sesi Privat — sesi yang berisi **minimal satu** siswa paket Privat, flat per sesi (bukan dikali jumlah siswa) | `private_count` | `fee_private` | `private_count × rates.private` |
| b) Sesi Semi-Privat — sesi yang **tidak** berisi siswa Privat sama sekali, flat per sesi (alternatif dari a) | `session_count` | `fee_session` | `session_count × rates.session` |
| c) Total siswa hadir — seluruh kehadiran `hadir` di semua sesi bulan itu, **tanpa memandang paket**, dikali per kepala | `regular_count` | `fee_regular` | `regular_count × rates.student` |
| d) Transport (per hari yang ada minimal 1 sesi) | `day_count` | `fee_transport` | `day_count × rates.transport` |

Catatan penting:
- Nama field `private_count`/`session_count`/`regular_count` dipertahankan agar skema API tidak berubah, tapi maknanya **bukan lagi jumlah siswa untuk ketiganya** — `private_count` & `session_count` sekarang **jumlah sesi**, hanya `regular_count` yang tetap jumlah siswa (dan sekarang mencakup semua paket, bukan cuma Semi-Privat).
- Sesi **campuran** (ada siswa Privat & non-Privat sekaligus) diklasifikasikan sebagai sesi Privat (a) — siswa non-Privat di sesi itu tetap ikut dihitung pada komponen (c).
- Siswa dengan `package_id` kosong/tidak valid diperlakukan sebagai non-Privat (fallback ke sesi tipe b / komponen c), supaya tidak ada kehadiran yang "hilang" dari perhitungan.

`total = fee_private + fee_regular + fee_session + fee_transport`. `rates.*` diambil dari master Tutor (`fee_per_student_private`, `fee_per_student`, `fee_per_session`, `fee_transport_per_day`) — nilai ini hanya untuk referensi tampilan; angka aktual pada `rows[].fee_*` sudah final saat di-generate (dan bisa berbeda dari `rates` bila admin melakukan edit manual sebelum menerbitkan).

Field `published` (boolean) menandai apakah bulan tsb sudah bisa ditagihkan/dicetak slip-nya — gunakan untuk menonaktifkan tombol "Unduh Slip" di UI client saat `false`.

---

## 8. Laporan (PDF)

Kedua endpoint berikut **tidak mengembalikan JSON**, melainkan **berkas PDF biner** (`Content-Type: application/pdf`) — gunakan template Blade yang sama dengan versi web agar hasil cetak konsisten.

### 8.1 Slip Gaji

```
GET /api/tutor/reports/slip-gaji
```
🔒 *Butuh token* · **Query:** `month` (`YYYY-MM`, default bulan berjalan)

**Syarat:** periode bulan tsb harus sudah **`published`** oleh admin (lihat [7.2 Rekap Fee](#72-rekap-fee-per-tahun)). Bila belum:

**Response `404 Not Found`**

```json
{ "message": "Fee bulan Juli 2026 belum diterbitkan oleh admin." }
```

Bila sudah terbit — **Response `200 OK`**: unduhan PDF ukuran A5 landscape, nama berkas `slip-gaji-YYYY-MM.pdf`. Berisi rincian 4 komponen fee (sesi, siswa privat, siswa semi-privat, transport) sesuai data yang diterbitkan admin, dan area tanda tangan.

> ⚠️ **Perubahan alur:** sebelumnya slip gaji dihitung live (`sesi × fee_per_session`) dan selalu bisa diunduh kapan saja. Sekarang **hanya berhasil bila admin sudah menerbitkan** fee bulan tsb — tangani status `404` di client dengan menampilkan pesan bahwa fee bulan itu belum diterbitkan, bukan sebagai error generik.

### 8.2 Summary Pengajaran

```
GET /api/tutor/reports/summary
```
🔒 *Butuh token* · **Query:** `month` (`YYYY-MM`, default bulan berjalan)

Response: unduhan PDF ukuran A4 landscape, nama berkas `summary-pengajaran-YYYY-MM.pdf`. Berisi statistik bulanan + tabel rinci per sesi (materi, kehadiran, nilai, catatan tutor).

---

## 9. Format Error

Seluruh error memakai format standar Laravel:

| Kode | Kapan terjadi | Bentuk body |
|---|---|---|
| `401 Unauthorized` | Token tidak dikirim, tidak valid, atau sudah dicabut (logout) | `{ "message": "Unauthenticated." }` |
| `403 Forbidden` | Role bukan `tutor`; akun tutor tidak tertaut ke master Tutor; mengakses siswa/sesi/evaluasi milik tutor lain | `{ "message": "<penjelasan, bisa kosong>" }` |
| `404 Not Found` | ID di path (`{student}`, `{schedule}`) tidak ditemukan; atau slip gaji diminta untuk bulan yang **belum diterbitkan** admin (lihat [8.1](#81-slip-gaji)) | `{ "message": "..." }` |
| `422 Unprocessable Entity` | Validasi input gagal, atau aturan bisnis login (lihat bagian 2) | `{ "message": "The given data was invalid.", "errors": { "<field>": ["<pesan>"] } }` |

Contoh `403` saat akun tutor belum tertaut ke master Tutor (berlaku di **semua** endpoint terproteksi):

```json
{ "message": "Akun Anda belum tertaut ke data master tutor. Hubungi admin." }
```

---

## 10. Referensi Objek Data

### Enum / status

| Field | Nilai |
|---|---|
| `user.role` | `admin`, `tutor`, `siswa` |
| `user.status` | `pending`, `aktif`, `nonaktif` |
| `schedule.status_schedule` | `scheduled`, `done`, `canceled` |
| `evaluation.student_attendance` | `hadir`, `izin`, `alfa` |
| `fee_period.status` (internal admin, tercermin sebagai `rows[].published`) | `draft`, `published` |

### Objek `tutor` (master Tutor)

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | integer | |
| `name` | string | |
| `photo` | string\|null | Path relatif storage (gabungkan dengan base URL `storage/` untuk menampilkan gambar) |
| `phone` | string | |
| `email` | string | Kunci penaut akun login |
| `no_rekening` | string\|null | |
| `fee_per_session` | number\|null | Tarif per sesi mengajar (komponen c) |
| `fee_per_student_private` | number\|null | Tarif per kehadiran siswa paket **Privat** (komponen a) |
| `fee_per_student` | number\|null | Tarif per kehadiran siswa paket **Semi-Privat** (komponen b) |
| `fee_transport_per_day` | number\|null | Tarif transport per hari mengajar (komponen d) |
| `specialization` | string[] | Daftar mata pelajaran spesialisasi |

> Keempat field `fee_*` adalah **tarif master** yang diatur admin di data Tutor — dipakai admin saat men-generate fee bulanan (lihat [7.2 Rekap Fee](#72-rekap-fee-per-tahun)). Bukan nominal fee aktual suatu bulan.

### Objek `evaluation` (ringkas, seperti dipakai di 4.3/5.1)

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | integer | |
| `materi` | `{ pokok: string, sub: string\|null }`\|null | Dari silabus atau `materi_manual`, sudah digabung |
| `student_attendance` | string | `hadir`\|`izin`\|`alfa` |
| `post_test` | integer\|null | 1–100 |
| `is_published` | boolean | Status terbit laporan (dikelola dari sisi admin) |

---

## 11. Contoh Alur Lengkap (cURL)

```bash
BASE=https://app.livo.co.id/api/tutor

# 1) Cek email
curl -s -X POST "$BASE/auth/check-email" \
  -H "Accept: application/json" \
  -d "email=budi@livo.co.id"
# → { "has_password": false, ... }  → lanjut ke langkah 2a
# → { "has_password": true, ... }   → lanjut ke langkah 2b

# 2a) Login pertama kali: buat password
curl -s -X POST "$BASE/auth/create-password" \
  -H "Accept: application/json" \
  -d "email=budi@livo.co.id" \
  -d "password=RahasiaSekali123" \
  -d "password_confirmation=RahasiaSekali123" \
  -d "device_name=iphone-budi"

# 2b) Login (sudah punya password)
curl -s -X POST "$BASE/auth/login" \
  -H "Accept: application/json" \
  -d "email=budi@livo.co.id" \
  -d "password=RahasiaSekali123" \
  -d "device_name=iphone-budi"

# → simpan nilai "token" dari salah satu response di atas
TOKEN="1|abcdEXAMPLEtokenXYZ..."

# 3) Panggil endpoint terproteksi
curl -s "$BASE/dashboard" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 4) Isi evaluasi sesi #502
curl -s -X POST "$BASE/evaluations/502" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "student_attendance=hadir" \
  -d "materi_manual=Aljabar Dasar" \
  -d "post_test=90"

# 4a) Cari siswa "Budi" pada jadwal minggu ini
curl -s "$BASE/schedules/week?search=Budi" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 4b) Cari siswa "Budi" pada daftar evaluasi yang belum diisi
curl -s "$BASE/evaluations?search=Budi" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 4c) Cari silabus "Aljabar" untuk sesi #502 (otomatis disaring sesuai mapel & kelas siswa sesi ini)
curl -s "$BASE/evaluations/502?search=Aljabar" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# 5) Unduh slip gaji bulan berjalan (404 bila admin belum menerbitkan fee bulan tsb)
curl -s "$BASE/reports/slip-gaji?month=2026-07" \
  -H "Authorization: Bearer $TOKEN" \
  -o slip-gaji.pdf

# 6) Logout
curl -s -X POST "$BASE/auth/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```
