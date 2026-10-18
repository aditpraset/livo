# PRD — API Tutor LIVO (untuk Aplikasi Mobile)

| | |
|---|---|
| **Status** | Siap diimplementasikan di mobile |
| **Base URL** | `/api/tutor` |
| **Auth** | Bearer token (Laravel Sanctum, stateless) |
| **Target pembaca** | Tim mobile (engineer + designer) |
| **Cakupan** | Seluruh API area Tutor: Auth, Dashboard, Jadwal Mingguan, Jadwal dari Pola (konfirmasi & pengambilan jadwal baru), Evaluasi, Profil, Laporan & Fee |

---

## Daftar isi

1. [Ringkasan & cara pakai dokumen ini](#1-ringkasan--cara-pakai-dokumen-ini)
2. [Autentikasi & sesi](#2-autentikasi--sesi)
3. [Ringkasan seluruh endpoint](#3-ringkasan-seluruh-endpoint)
4. [Modul: Dashboard](#4-modul-dashboard)
5. [Modul: Jadwal Mingguan](#5-modul-jadwal-mingguan)
6. [Modul: Jadwal dari Pola — Konfirmasi Mingguan & Pengambilan Jadwal Baru](#6-modul-jadwal-dari-pola--konfirmasi-mingguan--pengambilan-jadwal-baru)
7. [Modul: Evaluasi](#7-modul-evaluasi)
8. [Modul: Profil](#8-modul-profil)
9. [Modul: Laporan & Fee](#9-modul-laporan--fee)
10. [Error umum (berlaku di semua endpoint)](#10-error-umum-berlaku-di-semua-endpoint)
11. [Rekomendasi UI/UX umum](#11-rekomendasi-uiux-umum)
12. [Di luar scope dokumen ini](#12-di-luar-scope-dokumen-ini)

---

## 1. Ringkasan & cara pakai dokumen ini

Dokumen ini mendeskripsikan **seluruh API yang tersedia untuk aplikasi mobile tutor** LIVO — bukan cuma satu fitur. Semua endpoint ini sudah berjalan di backend dan sudah ada versi web-nya (Blade) yang dipakai tutor lewat browser; perilaku bisnisnya identik, dokumen ini hanya mendeskripsikan kontrak JSON-nya untuk dipakai di mobile.

Tiap modul (§4–§9) berisi: tujuan modul, daftar endpoint, request/response lengkap dengan contoh JSON, dan catatan bisnis yang perlu diketahui mobile (termasuk perilaku tak-kentara yang mudah salah diasumsikan, misalnya field yang tampil `null` sampai syarat tertentu terpenuhi).

Modul yang paling kompleks — **Jadwal dari Pola** (§6) — juga memuat user flow bergambar dan rekomendasi UI, karena ini fitur paling baru dan belum punya padanan di aplikasi lama.

---

## 2. Autentikasi & sesi

Login 2 langkah, setara alur web (`App\Http\Controllers\Auth\LoginController`), versi stateless bertoken Sanctum:

```
POST /auth/check-email  { email }
        │
        ▼
 has_password == false ──► POST /auth/create-password { email, password, password_confirmation }
        │
 has_password == true  ──► POST /auth/login { email, password }
        │
        ▼
   { token, token_type: "Bearer", user, tutor }
        │
        ▼
Simpan token, pakai di header semua request berikutnya:
   Authorization: Bearer <token>
```

### `POST /auth/check-email`

Cek apakah email terdaftar sebagai tutor, sekaligus **auto-provisioning**: kalau email ada di data master Tutor tapi belum pernah login, akun `User` dibuatkan otomatis saat endpoint ini dipanggil.

**Body**: `email` (wajib, format email).

**Response `200`**
```json
{ "email": "tutor@example.com", "name": "Puja Nur Annisa", "has_password": false }
```
`has_password: false` → arahkan ke form **Buat Password** (langkah pertama kali pakai akun). `has_password: true` → arahkan ke form **Login** biasa.

**Error `422`** (`ValidationException`, field `email`):
- `"Email tidak terdaftar sebagai tutor. Hubungi admin bila Anda merasa ini keliru."`
- `"Akun Anda nonaktif. Hubungi admin untuk mengaktifkan kembali."`

### `POST /auth/login`

**Body**: `email` (wajib), `password` (wajib), `device_name` (opsional, default `"tutor-app"` — **isi dengan nama perangkat**, mis. `"iPhone Budi"`, supaya daftar sesi aktif bermakna kalau nanti ada fitur kelola perangkat).

**Response `200`** — lihat shape `respondWithToken` di bawah.

**Error `422`**: `"Email atau password salah."` atau `"Akun Anda nonaktif. ..."`.

### `POST /auth/create-password`

Dipakai **hanya sekali** — percobaan login pertama tutor yang belum pernah set password. Memvalidasi: `email` (wajib), `password` (wajib, **min 8 karakter**), `password_confirmation` (wajib, harus sama dengan `password`), `device_name` (opsional).

**Response `200`** — sama seperti login, sekalian langsung dapat token (tidak perlu login ulang setelah buat password).

**Error `422`**:
- `"Password minimal 8 karakter."` / `"Konfirmasi password tidak cocok."`
- `"Email tidak terdaftar sebagai tutor."`
- `"Akun ini sudah memiliki password. Gunakan endpoint login."` — **penting**: endpoint ini menolak dipakai dua kali; kalau tutor lupa password, itu alur "lupa password" terpisah (belum ada di API ini, lihat §12).

**Shape respons `login`/`create-password`** (sama persis):
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "user": { "id": 19, "name": "Puja Nur Annisa", "email": "tutor@example.com", "role": "tutor", "status": "aktif" },
  "tutor": { "id": 1, "name": "Puja Nur Annisa", "photo": "tutors/abc.jpg", "phone": "0812...", "...": "lihat §8 untuk field lengkap objek tutor" }
}
```

### `GET /auth/me` 🔒

Info akun untuk bootstrap aplikasi (dipanggil saat app dibuka dan token tersimpan masih ada, untuk cek validitas token + ambil data user terbaru).

**Response `200`**: `{ "user": {...}, "tutor": {...} }` (shape sama seperti field `user`/`tutor` di atas).

### `POST /auth/logout` 🔒

Mencabut **token yang sedang dipakai saja** (device lain tetap login kalau ada). Response: `{"message": "Berhasil logout."}`. Client wajib menghapus token tersimpan setelah ini — token yang sudah dicabut akan membuat semua request berikutnya mengembalikan `401`.

> 🔒 = butuh header `Authorization: Bearer <token>` + role `tutor`. Endpoint tanpa tanda ini (ketiga endpoint auth paling atas) memang publik/pre-login.

---

## 3. Ringkasan seluruh endpoint

| Method | Path | 🔒 | Fungsi |
|---|---|---|---|
| POST | `/auth/check-email` | — | Cek email + auto-provisioning akun |
| POST | `/auth/login` | — | Login (akun sudah punya password) |
| POST | `/auth/create-password` | — | Set password pertama kali + login |
| GET | `/auth/me` | 🔒 | Info akun saat ini |
| POST | `/auth/logout` | 🔒 | Cabut token perangkat ini |
| GET | `/dashboard` | 🔒 | Ringkasan statistik tutor |
| GET | `/schedules/week?week=` | 🔒 | Jadwal satu minggu (sesi berjalan/sudah lewat), dikelompokkan per hari |
| GET | `/students/{id}` | 🔒 | Statistik ringkas satu siswa |
| GET | `/students/{id}/history` | 🔒 | Riwayat sesi siswa itu bersama tutor ini (dipaginasi) |
| GET | `/schedule-slots?week=` | 🔒 | Ringkasan pola mingguan: perlu dikonfirmasi, tersedia, keputusan admin |
| POST | `/schedule-slots/{id}/apply` | 🔒 | Lamar slot yang terbuka |
| PUT | `/schedule-slots/{id}/withdraw` | 🔒 | Batalkan lamaran |
| PUT | `/schedule-slots/{id}/confirm` | 🔒 | Konfirmasi jadwal rutin mingguan |
| PUT | `/schedule-slots/{id}/izin` | 🔒 | Izin untuk minggu ini saja |
| GET | `/evaluations?search=&per_page=` | 🔒 | Daftar sesi yang perlu dievaluasi (dipaginasi) |
| GET | `/evaluations/{schedule}?search=` | 🔒 | Detail sesi + daftar silabus utk form evaluasi |
| POST | `/evaluations/{schedule}` | 🔒 | Simpan evaluasi |
| PUT | `/evaluations/{schedule}/feedback` | 🔒 | Simpan feedback siswa cepat (tanpa form lengkap) |
| GET | `/profile` | 🔒 | Lihat profil tutor |
| POST | `/profile` | 🔒 | Ubah kontak/rekening/foto |
| GET | `/rekap-pengajaran?month=` | 🔒 | Statistik + daftar sesi bulan tsb |
| GET | `/rekap-fee?year=` | 🔒 | Rekap fee 12 bulan dalam satu tahun |
| GET | `/reports/slip-gaji?month=` | 🔒 | **PDF** slip gaji bulan tsb |
| GET | `/reports/summary?month=` | 🔒 | **PDF** summary pengajaran bulan tsb |

Dua endpoint terakhir mengembalikan **file PDF biner** (`Content-Type: application/pdf`), bukan JSON — perlakukan beda dari endpoint lain (lihat §9.1).

---

## 4. Modul: Dashboard

### `GET /dashboard` 🔒

Satu layar ringkasan untuk halaman utama aplikasi setelah login.

**Response `200`**
```json
{
  "tutor": { "...": "objek tutor lengkap, lihat §8" },
  "stats": {
    "total_sessions": 120,
    "month_sessions": 14,
    "upcoming_sessions": 6,
    "total_students": 35,
    "month_students": 12,
    "pending_evaluations": 3
  },
  "review": {
    "evaluated": 118,
    "published": 110,
    "avg_post_test": 82.4,
    "avg_pemahaman": 78.1,
    "avg_analisa": 75.0,
    "avg_hafalan": 80.2,
    "avg_kepercayaan": 77.5,
    "hadir": 112,
    "izin": 4,
    "alfa": 2
  },
  "recent_evaluations": [
    { "id": 501, "student_name": "Budi Santoso", "subject_name": "Matematika", "class_date": "2027-03-01", "post_test": 85, "is_published": true }
  ]
}
```

**Arti field `stats`** (semua dihitung dari jadwal milik tutor ini saja):
- `total_sessions` / `month_sessions` — jumlah sesi **unik** (dihitung per kombinasi tanggal+jam, bukan per baris siswa) berstatus `done`, sepanjang waktu vs bulan berjalan.
- `upcoming_sessions` — sesi berstatus `scheduled` dengan tanggal ≥ hari ini.
- `total_students` — jumlah baris kehadiran "hadir" (bisa > jumlah siswa unik kalau satu siswa diajar berkali-kali — ini metrik "total kehadiran", bukan "jumlah siswa berbeda").
- `month_students` — jumlah **siswa unik** (distinct) yang punya jadwal (bukan batal) bulan ini.
- `pending_evaluations` — sesi `done` yang **belum** ada evaluasinya sama sekali. Tampilkan sebagai badge peringatan/CTA ke modul Evaluasi.

**Arti field `review`** — dihitung dari SEMUA evaluasi tutor ini (lintas bulan, bukan cuma bulan berjalan). `published` = evaluasi yang sudah ditinjau & diterbitkan admin (siswa/ortu baru bisa lihat setelah ini). `avg_*` bisa `null` kalau belum ada data terisi untuk field tsb.

`recent_evaluations` — 8 evaluasi terbaru (`latest()`), untuk ditampilkan sebagai list pendek di dashboard, bukan tabel penuh.

---

## 5. Modul: Jadwal Mingguan

Berbeda dengan §6 — modul ini menampilkan **jadwal yang sudah final/sedang berjalan** (`Schedule`, bukan `ScheduleSlot`), termasuk histori yang sudah lewat. Dipakai untuk kalender/jadwal harian tutor dan drill-down riwayat per siswa.

### `GET /schedules/week?week=&search=` 🔒

**Query params**: `week` (opsional, `YYYY-MM-DD`, tanggal apa saja dalam minggu yang dituju — default minggu berjalan), `search` (opsional, filter nama siswa, substring/`like`).

**Response `200`**
```json
{
  "start": "2027-03-01",
  "end": "2027-03-07",
  "prev_week": "2027-02-22",
  "next_week": "2027-03-08",
  "search": null,
  "total": 9,
  "days": ["2027-03-01", "2027-03-02", "2027-03-03", "2027-03-04", "2027-03-05", "2027-03-06", "2027-03-07"],
  "schedules_by_day": {
    "2027-03-01": [
      {
        "id": 501, "class_date": "2027-03-01", "start_time": "16:00", "end_time": "17:30", "room": null,
        "status_schedule": "scheduled",
        "student": { "id": 101, "full_name": "Budi Santoso", "grade": "SD Kelas 3" },
        "subject": { "id": 1, "subject_name": "Matematika" },
        "evaluation": null
      }
    ],
    "2027-03-02": []
  }
}
```
`schedules_by_day` **selalu punya ketujuh key tanggal** (Senin–Minggu), walau array-nya kosong — memudahkan render kalender 7 kolom tanpa perlu cek key hilang. `status_schedule` bernilai `scheduled` | `done` | `canceled`. `evaluation` berisi objek (`id`, `materi`, `student_attendance`, `post_test`, `is_published`) kalau sesi sudah dievaluasi, atau `null` kalau belum.

### `GET /students/{id}` 🔒

Statistik ringkas satu siswa — **hanya siswa yang pernah/akan diajar tutor ini** (dicek lewat relasi `Schedule`, bukan daftar siswa global).

**Error `403`**: `"Siswa ini tidak terdaftar pada jadwal Anda."` — siswa valid tapi tidak pernah diajar tutor yang login. **Mobile wajib menangani ini** kalau ada fitur "cari siswa" yang scope-nya lebih luas dari jadwal tutor sendiri.

**Response `200`**
```json
{
  "student": { "...": "objek Student penuh (hasil toJson model, termasuk field admin seperti nis/alamat/dll jika ada)" },
  "stats": { "total": 24, "done": 20, "evaluated": 18, "avg_post_test": 81.5, "hadir": 17, "izin": 2, "alfa": 1 }
}
```

### `GET /students/{id}/history?per_page=` 🔒

Riwayat sesi siswa tsb bersama tutor ini, terurut dari yang terbaru, **dipaginasi** (default 15/halaman, atur lewat `per_page`). Sama dengan proteksi 403 di atas.

**Response `200`** — struktur paginator Laravel standar:
```json
{
  "current_page": 1,
  "data": [ { "id": 501, "class_date": "...", "...": "shape sama seperti item di schedules_by_day" } ],
  "last_page": 3,
  "per_page": 15,
  "total": 38,
  "next_page_url": "...", "prev_page_url": null
}
```
Mobile cukup pakai `next_page_url`/`current_page < last_page` untuk infinite-scroll/load-more.

---

## 6. Modul: Jadwal dari Pola — Konfirmasi Mingguan & Pengambilan Jadwal Baru

### 6.1 Latar belakang & tujuan

Tutor LIVO mengajar jadwal yang berulang tiap minggu (mis. "Setiap Senin jam 16:00, Matematika, Group SD Kelas 3"). Pola ini dibuat admin sekali ("Pola Jadwal"), lalu tiap minggu admin menekan "Siapkan Jadwal" untuk menghasilkan slot minggu tsb secara otomatis.

Modul ini (`ScheduleSlot`) berbeda dari §5 (`Schedule`) — di sini yang dikelola adalah **rencana/kandidat** jadwal sebelum final, bukan jadwal yang sudah pasti berjalan. Begitu dikonfirmasi/ditetapkan, modul ini **menghasilkan** baris `Schedule` baru yang baru kemudian muncul di §5.

Dua alur utama yang perlu ada di aplikasi mobile tutor:

1. **Konfirmasi mingguan** — tutor yang sudah punya jadwal rutin dari minggu sebelumnya cukup **konfirmasi** (atau **izin** bila berhalangan minggu itu saja) tiap minggu, tanpa perlu melamar ulang.
2. **Pengambilan jadwal baru** — slot yang belum punya tutor tetap (baru/kosong) ditampilkan ke semua tutor yang sesuai kualifikasi (mata pelajaran). Tutor bisa **memilih/melamar**; admin yang menentukan siapa yang dapat.

Versi web (Blade) fitur ini sudah berjalan di `/tutor/jadwal-tersedia`.

### 6.2 Istilah & status

| Istilah | Arti |
|---|---|
| **Slot** | Satu baris jadwal untuk satu minggu tertentu (hari + sesi + mapel + group siswa). Satu slot = satu kemungkinan sesi mengajar. |
| **Pola** | Template mingguan yang menghasilkan slot baru tiap minggu admin men-generate. |
| **Lamaran (application)** | Pernyataan minat seorang tutor terhadap satu slot yang masih terbuka. |

**Status slot** (field `status` pada setiap slot):

| Nilai | Label tampilan | Arti | Siapa yang bertindak |
|---|---|---|---|
| `open` | Tersedia | Belum pernah ada tutor tetap untuk baris pola ini, atau sudah dilepas admin — dilelang ke semua tutor berkualifikasi | Tutor melamar → **admin** menetapkan satu |
| `pending_confirmation` | Menunggu Konfirmasi | Tutor rutin dari minggu lalu dibawa otomatis ke minggu ini, tinggal dikonfirmasi | **Tutor pemilik slot** konfirmasi atau izin |
| `vacant` | Butuh Pengganti | Tutor rutin mengambil **izin** untuk minggu ini saja — kepemilikan pola TETAP miliknya, minggu depan jadwal balik ke dia lagi | Tutor lain melamar → **admin** menetapkan pengganti |
| `assigned` | Complete | Sudah ada tutor final untuk minggu ini (hasil konfirmasi atau ditetapkan admin) — jadwal mengajar asli (`Schedule`) sudah otomatis dibuat | — (sudah final) |
| `closed` | Ditutup | Admin menutup slot ini, tidak jadi dipakai minggu ini | — |

**Status lamaran** (field `status` pada application): `pending` (menunggu keputusan admin), `accepted` (terpilih), `rejected` (tidak terpilih), `withdrawn` (dibatalkan tutor sendiri).

#### Diagram alur status slot

```
      tutor melamar,                      tutor KONFIRMASI
      ADMIN menetapkan                    ┌─────────────────┐
  open ─────────────────────► assigned ◄──┤                 │
    ▲                            ▲        │  pending_confirmation
    │ admin melepas              │        │                 │
    │ tutor (force)              │ admin menetapkan          │
    │                            │ pengganti                 │ tutor
    └──── vacant ◄───────────────┘                            IZIN
              ▲                                                │
              └────────────────────────────────────────────────┘
  (apa pun) ──admin menutup──► closed
```

Poin penting untuk UI mobile: **"izin" ≠ "lepas jadwal"**. Izin hanya mengosongkan minggu berjalan; minggu depan pola otomatis balik ke tutor yang sama. Pastikan copy di mobile tidak membuat tutor kira mereka kehilangan jadwal rutinnya secara permanen.

### 6.3 User flow

#### Flow A — Konfirmasi jadwal mingguan

```
[Buka tab "Jadwal Saya"]
        │
        ▼
GET /schedule-slots  ──► needs_confirmation[] berisi N slot?
        │
   ┌────┴────┐
   │  N = 0  │  N > 0
   ▼         ▼
"Tidak ada   Tampilkan daftar kartu, tiap kartu ada 2 tombol:
yang perlu   [Konfirmasi]  [Izin]
dikonfirmasi"     │              │
                  ▼              ▼
     PUT .../confirm      PUT .../izin
                  │              │
                  ▼              ▼
     Jadwal mengajar       Slot jadi "Butuh Pengganti",
     otomatis dibuat       tutor tetap pemilik pola
     (lihat response       minggu depan
     `created`)
```

**Kapan ini perlu muncul sebagai notifikasi/badge?** Setiap kali tutor login atau buka halaman, cek `needs_confirmation`. Disarankan tampilkan badge count di tab navigasi (seperti versi web: badge kuning dengan angka) supaya tutor tidak lupa konfirmasi sebelum minggu berjalan.

#### Flow B — Mengambil jadwal baru (minggu terpilih)

```
[Tutor geser ke minggu tertentu, atau default minggu ini]
        │
        ▼
GET /schedule-slots?week=YYYY-MM-DD
        │
        ▼
available[] berisi slot sesuai mata pelajaran tutor
        │
   tutor tap kartu slot
        │
   ┌────┴─────────────────────┐
   │ belum pernah melamar     │ sudah melamar (field `application` ada isinya)
   ▼                          ▼
[Pilih Jadwal Ini]      [Batalkan Pilihan]  (hanya kalau application.status == "pending")
   │                          │
   ▼                          ▼
POST .../apply          PUT .../withdraw
```

Catatan penting: **boleh lebih dari satu tutor melamar slot yang sama** — ini bukan rebutan "siapa cepat dia dapat". Admin yang memutuskan siapa yang dipilih dari semua pelamar. UI sebaiknya menampilkan status "Menunggu Keputusan Admin" setelah melamar, bukan seolah-olah otomatis dapat.

#### Flow C — Melihat hasil keputusan admin

Setelah admin menetapkan/menolak lamaran, tutor perlu tahu hasilnya. Field `decisions[]` pada response `index` berisi lamaran yang sudah diputuskan (accepted/rejected) untuk minggu yang sedang dilihat. Tampilkan sebagai daftar terpisah ("Hasil Keputusan") dengan badge hijau (Terpilih) / abu-abu (Tidak Terpilih).

#### Navigasi minggu

Semua data pada `available` dan `decisions` terikat ke satu minggu (`week` query param, format `YYYY-MM-DD`, boleh tanggal apa saja dalam minggu itu — server akan menormalkan ke hari Senin). `needs_confirmation` **tidak** terikat minggu — selalu tampilkan semua yang pending terlepas dari minggu mana yang sedang dilihat tutor, supaya tidak ada yang terlewat kalau admin menyiapkan jadwal jauh-jauh hari.

Sediakan navigasi Minggu Lalu / Minggu Ini / Minggu Depan di UI (pakai `prev_week`/`next_week` dari response, jangan hitung sendiri di client agar konsisten dengan definisi "awal minggu = Senin" di server).

### 6.4 Referensi API

#### `GET /schedule-slots` 🔒

Ringkasan satu minggu: jadwal yang perlu dikonfirmasi, jadwal tersedia untuk dilamar, dan riwayat keputusan admin.

**Query params**

| Param | Wajib | Format | Default | Keterangan |
|---|---|---|---|---|
| `week` | tidak | `YYYY-MM-DD` | minggu berjalan | Tanggal apa saja dalam minggu yang ingin dilihat (dinormalkan ke hari Senin) |

**Response `200`**

```jsonc
{
  "week_start": "2027-03-01",
  "week_end": "2027-03-07",
  "prev_week": "2027-02-22",
  "next_week": "2027-03-08",
  "specialization": ["Matematika", "Fisika"],

  "needs_confirmation": [
    {
      "id": 71,
      "hari": "Senin",
      "class_date": "2027-03-01",
      "week_start": "2027-03-01",
      "status": "pending_confirmation",
      "status_label": "Menunggu Konfirmasi",
      "carried_from_slot_id": 54,
      "confirmed_at": null,
      "izin_at": null,
      "session": { "id": 11, "name": "Sesi Sore", "time_start": "16:00", "time_end": "17:30" },
      "subject": { "id": 1, "subject_name": "Matematika" },
      "student_group": {
        "id": 76,
        "name": "Group SD Kelas 3",
        "students": [
          { "id": 101, "full_name": "Budi Santoso", "nis": "2024001", "grade": "SD Kelas 3" },
          { "id": 102, "full_name": "Siti Aminah", "nis": "2024002", "grade": "SD Kelas 3" }
        ]
      },
      "assigned_tutor": { "id": 5, "name": "Puja Nur Annisa" },
      "filled_by_tutor": null
    }
  ],

  "available": [
    {
      "id": 90,
      "hari": "Rabu",
      "class_date": "2027-03-03",
      "status": "open",
      "status_label": "Tersedia",
      "session": { "id": 12, "name": "Sesi Malam", "time_start": "18:30", "time_end": "20:00" },
      "subject": { "id": 1, "subject_name": "Matematika" },
      "student_group": { "id": 80, "name": "Group SMP Kelas 7", "students": [ /* ... */ ] },
      "assigned_tutor": null,
      "filled_by_tutor": null,
      "application": {
        "id": 14,
        "schedule_slot_id": 90,
        "status": "pending",
        "status_label": "Menunggu Keputusan",
        "note": "Siap mengajar",
        "applied_at": "2027-02-20T09:15:00+07:00",
        "slot": null
      }
    }
  ],

  "decisions": [
    {
      "id": 9,
      "schedule_slot_id": 61,
      "status": "accepted",
      "status_label": "Terpilih",
      "note": null,
      "applied_at": "2027-02-18T10:00:00+07:00",
      "slot": {
        "id": 61, "hari": "Jumat", "class_date": "2027-03-05",
        "status": "assigned", "status_label": "Complete",
        "session": { "...": "..." }, "subject": { "...": "..." },
        "student_group": { "...": "..." }
      }
    }
  ]
}
```

**Catatan field:**
- `application` pada tiap item `available` bernilai `null` kalau tutor belum pernah melamar slot itu. Gunakan ini untuk menentukan tombol mana yang ditampilkan (lihat §6.3 Flow B).
- `student_group.students` **hanya berisi siswa berstatus aktif**, diurutkan nama. Dipakai untuk fitur "Lihat Siswa" (popup daftar siswa di kartu jadwal — sudah ada polanya di versi web).
- Semua field tanggal/waktu dalam zona waktu server (WIB, `Asia/Jakarta`); field bertipe timestamp (`confirmed_at`, `izin_at`, `applied_at`) memakai format ISO 8601 dengan offset (`+07:00`), sedangkan `class_date`/`week_start` murni tanggal (`YYYY-MM-DD`) tanpa jam.

---

#### `POST /schedule-slots/{id}/apply` 🔒

Tutor melamar satu slot yang masih terbuka (`open` atau `vacant`).

**Body**

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `note` | tidak | string | Catatan singkat opsional dari tutor ke admin |

**Response sukses `200`**
```json
{
  "success": true,
  "message": "Jadwal berhasil dipilih. Menunggu keputusan admin.",
  "application": { "id": 14, "schedule_slot_id": 90, "status": "pending", "status_label": "Menunggu Keputusan", "note": "siap", "applied_at": "2027-02-20T09:15:00+07:00", "slot": null }
}
```

**Error `422`** — dua kemungkinan pesan:
- `"Slot ini sudah tidak dibuka lagi."` — slot sudah bukan `open`/`vacant` lagi (keduluan admin/tutor lain).
- `"Mata pelajaran slot ini di luar kualifikasi Anda."` — mapel slot tidak ada di `specialization` tutor. **Mobile sebaiknya mencegah ini di level UI** (jangan tampilkan tombol lamar untuk slot di luar kualifikasi — meski secara data endpoint `available` sudah difilter berdasar kualifikasi, validasi ini tetap jalan sebagai pengaman kalau ada slot dari sumber lain).

Melamar ulang ke slot yang sama (sudah pernah melamar, lalu melamar lagi) **tidak membuat lamaran dobel** — `note` dan `applied_at` akan diperbarui (upsert).

---

#### `PUT /schedule-slots/{id}/withdraw` 🔒

Tutor membatalkan lamarannya sendiri.

**Response sukses `200`**
```json
{ "success": true, "message": "Pilihan jadwal dibatalkan." }
```

**Error `422`**:
- `"Anda belum memilih jadwal ini."` — tidak ada lamaran dari tutor ini untuk slot tsb.
- `"Lamaran sudah disetujui admin. Hubungi admin untuk perubahan."` — admin sudah menetapkan tutor ini sebagai pemenang; tidak bisa dibatalkan sendiri dari mobile (harus lewat admin).

---

#### `PUT /schedule-slots/{id}/confirm` 🔒

Tutor mengonfirmasi jadwal rutin (hanya berlaku untuk slot berstatus `pending_confirmation` **milik tutor ini**). Memicu pembuatan jadwal mengajar asli secara otomatis untuk setiap siswa aktif di group.

**Response sukses `200`**
```json
{
  "success": true,
  "message": "Jadwal dikonfirmasi. 2 jadwal mengajar dibuat.",
  "created": 2,
  "skipped": 0,
  "slot": { "id": 71, "status": "assigned", "status_label": "Complete", "...": "..." }
}
```

`created`/`skipped` = jumlah siswa di group yang berhasil/tidak dibuatkan jadwal (skip terjadi kalau siswa itu kebetulan sudah punya jadwal lain di jam yang sama — jarang terjadi, tapi tampilkan `skipped` di UI sebagai info kalau > 0, tidak perlu dianggap error).

**Error `422`**: `"Jadwal ini bukan milik Anda atau sudah tidak perlu dikonfirmasi."` — slot bukan status `pending_confirmation`, atau bukan milik tutor yang login (jangan sampai terjadi dari UI normal, tapi tetap divalidasi server-side).

---

#### `PUT /schedule-slots/{id}/izin` 🔒

Tutor mengambil izin untuk slot `pending_confirmation` miliknya — **hanya minggu ini**, kepemilikan pola tetap untuknya.

**Response sukses `200`**
```json
{
  "success": true,
  "message": "Izin dicatat. Jadwal minggu ini dikosongkan menunggu pengganti dari admin; jadwal rutin Anda tetap berlanjut minggu depan.",
  "slot": { "id": 71, "status": "vacant", "status_label": "Butuh Pengganti", "...": "..." }
}
```

**Error `422`**: sama seperti `confirm` — slot bukan `pending_confirmation` atau bukan miliknya.

> ⚠️ **Penting untuk copy UI**: tampilkan dialog konfirmasi sebelum memanggil endpoint ini (pola yang sama seperti versi web — lihat §11), karena tutor sering salah paham antara "izin" (sementara) dan "melepas jadwal" (permanen, hanya bisa dilakukan admin).

---

## 7. Modul: Evaluasi

Mengisi hasil belajar siswa untuk satu sesi (`Schedule`). Sesi yang boleh dievaluasi: berstatus `done` **atau** `scheduled` — **tidak ada syarat tanggal sudah lewat**, tutor boleh mengisi evaluasi kapan saja begitu jadwalnya dibuat, termasuk untuk sesi yang baru akan berlangsung. Satu-satunya sesi yang tidak muncul di daftar "perlu dievaluasi" adalah yang berstatus `canceled`.

### `GET /evaluations?search=&per_page=` 🔒

Daftar sesi yang **belum** dievaluasi, dipaginasi (default 15/halaman).

**Query params**: `search` (opsional, filter nama siswa), `per_page` (opsional).

**Response `200`** — paginator standar, tiap item:
```json
{
  "id": 501, "class_date": "2027-03-01", "start_time": "16:00", "end_time": "17:30", "room": null,
  "status_schedule": "scheduled", "student_feedback": null,
  "student": { "id": 101, "full_name": "Budi Santoso", "grade": "SD Kelas 3" },
  "subject": { "id": 1, "subject_name": "Matematika" }
}
```

### `GET /evaluations/{schedule}?search=` 🔒

Detail satu sesi + daftar silabus (untuk dropdown pilih materi) yang sudah difilter sesuai mata pelajaran & kelas siswa. `search` opsional untuk mencari silabus by pokok/sub-pokok bahasan.

**Error `403`**: sesi bukan milik tutor yang login.

**Response `200`**
```json
{
  "schedule": {
    "id": 501, "class_date": "2027-03-01", "...": "field Schedule penuh + relasi student/subject/evaluation.syllabus"
  },
  "syllabi": [
    { "id": 12, "pokok_bahasan": "Pecahan", "sub_pokok_bahasan": "Penjumlahan Pecahan", "kelas": "SD Kelas 3" }
  ]
}
```
> ⚠️ `schedule.class_date` di-override manual oleh backend supaya tetap `YYYY-MM-DD` apa adanya — **field lain dalam objek `schedule` (kalau ada datetime lain) tetap ikut serialisasi default Eloquent**, jadi untuk field tanggal selain `class_date`, mobile tetap perlu antisipasi kemungkinan pergeseran zona waktu pada serialisasi JSON standar Laravel.

### `POST /evaluations/{schedule}` 🔒

Simpan (atau perbarui — upsert berdasar `schedule_id`) evaluasi.

**Body**

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `student_attendance` | **ya** | string | `hadir` \| `izin` \| `alfa` |
| `syllabus_id` | tidak | int | Pilih dari daftar `syllabi` di endpoint detail |
| `materi_manual` | tidak | string, max 255 | Dipakai kalau materi tidak ada di silabus (exclusive dengan `syllabus_id` — isi salah satu) |
| `post_test` | tidak | int 1–100 | Nilai post test |
| `pemahaman` | tidak | int 1–100 | Skor kemampuan pemahaman |
| `kemampuan_analisa` | tidak | int 1–100 | |
| `kemampuan_hafalan` | tidak | int 1–100 | |
| `kepercayaan_diri` | tidak | int 1–100 | |
| `tutor_notes` | tidak | string, max 1000 | Catatan bebas tutor |

**Response `200`**
```json
{ "message": "Evaluasi Budi Santoso berhasil disimpan.", "evaluation": { "...": "objek Evaluation lengkap, fresh dari DB" } }
```

**Efek samping penting (otomatis, tidak perlu ditangani mobile secara eksplisit, tapi baik diketahui):**
- Kalau sesi sebelumnya `scheduled`, statusnya otomatis berubah jadi `done` begitu evaluasi tersimpan.
- Kuota sesi siswa (`quota_sessions`) otomatis berkurang 1 saat `student_attendance` = `hadir`/`alfa` (dan belum pernah dipotong untuk evaluasi ini), atau dikembalikan kalau kehadiran diubah jadi `izin`. **Kuota boleh menjadi minus** (dianggap utang sesi, otomatis terbayar saat admin menambah kuota lewat pembayaran berikutnya) — ini bukan bug, jangan diblokir di sisi mobile.

### `PUT /evaluations/{schedule}/feedback` 🔒

Simpan feedback siswa secara cepat **tanpa form evaluasi lengkap** — dipakai untuk kasus tutor ingin kasih feedback kapan saja, termasuk sebelum sesi dievaluasi penuh.

**Body**: `student_feedback` (opsional/nullable) — salah satu dari: `buruk`, `kurang_baik`, `cukup_baik`, `baik`, `sangat_baik`.

**Response `200`**
```json
{ "message": "Feedback siswa berhasil disimpan.", "schedule": { "...": "objek Schedule fresh, class_date sudah dinormalkan" } }
```

---

## 8. Modul: Profil

### `GET /profile` 🔒

**Response `200`**: `{ "tutor": { ... } }` — objek Tutor penuh.

**Field objek `tutor`** (sama di semua endpoint yang mengembalikan `tutor`):

| Field | Tipe | Catatan |
|---|---|---|
| `id`, `name` | — | |
| `photo` | string\|null | Path relatif disk `public` (mis. `tutors/abc.jpg`) — **bukan URL penuh**, mobile perlu gabungkan dengan base storage URL sendiri |
| `phone`, `email`, `no_rekening` | string\|null | |
| `specialization` | string[] | Daftar nama mata pelajaran kualifikasi tutor |
| `kategori` | string | `freelance` \| `tetap` |
| `fee_per_session`, `fee_per_student_private`, `fee_per_student`, `fee_transport_per_day`, `fee_private_khusus`, `fee_tka_regular`, `fee_private_sma`, `fee_tka_visit` | number | Tarif fee tutor — **ini data sensitif milik tutor sendiri**, ditampilkan apa adanya, hati-hati jangan ikut ter-expose ke pengguna lain di UI manapun yang kebetulan reuse komponen ini |
| `gaji_pokok`, `tunjangan_per_bulan`, `maks_sesi_tunjangan_per_bulan` | number\|null | Hanya relevan kalau `kategori == "tetap"` |

### `POST /profile` 🔒

> Catatan teknis: method HTTP-nya `POST`, **bukan** `PUT`, meski secara semantik ini "update" — ikuti apa adanya karena endpoint ini menerima `multipart/form-data` (upload foto), dan `PUT` dengan file upload tidak didukung baik oleh sebagian HTTP client. Kirim sebagai `multipart/form-data`, bukan JSON, kalau menyertakan `photo`.

Tutor **hanya boleh mengubah kontak, rekening & foto** — field lain (fee, spesialisasi, kategori) dikelola admin, tidak bisa diubah lewat endpoint ini.

**Body** (`multipart/form-data`)

| Field | Wajib | Tipe | Keterangan |
|---|---|---|---|
| `phone` | **ya** | string, max 20 | |
| `no_rekening` | tidak | string, max 50 | |
| `photo` | tidak | file gambar, max 5MB | Format gambar apa saja yang dikenali Laravel (`image` rule) |

**Response `200`**
```json
{ "message": "Profil berhasil diperbarui.", "tutor": { "...": "objek tutor fresh" } }
```
Foto lama otomatis terhapus dari storage saat diganti dengan foto baru.

---

## 9. Modul: Laporan & Fee

### `GET /rekap-pengajaran?month=&per_page=` 🔒

**Query**: `month` (opsional, format `YYYY-MM`, default bulan berjalan), `per_page` (opsional).

**Response `200`**
```json
{
  "month": "2027-03",
  "stats": {
    "done": 14,
    "students": 12,
    "evaluated": 13,
    "avg_post_test": 82.4,
    "hadir": 12,
    "izin": 1,
    "alfa": 0
  },
  "schedules": { "...": "paginator, tiap item: id, class_date, start_time, end_time, student, subject, materi, student_attendance, post_test, tutor_notes, student_feedback" }
}
```
> ⚠️ `stats` di sini **bukan objek yang sama** dengan `stats` di Dashboard (§4) — field-nya berbeda meski namanya mirip. Jangan reuse model/struct yang sama di client untuk keduanya. Arti field: `done` = jumlah sesi unik (per slot tanggal+jam, bukan per baris siswa) berstatus `done` bulan itu; `students` = jumlah kehadiran "hadir" (boleh sama dengan `hadir`, bukan distinct siswa); `evaluated` = sesi yang sudah ada evaluasinya; `avg_post_test` bisa `null` kalau belum ada nilai terisi.

### `GET /rekap-fee?year=` 🔒

Rekap fee 12 bulan dalam satu tahun. **Hanya menampilkan bulan yang periodenya sudah diterbitkan admin** — bulan yang masih draft/belum di-generate tetap muncul di array `rows` tapi dengan `published: false` dan semua angka `0`.

**Query**: `year` (opsional, default tahun berjalan).

**Response `200`**
```json
{
  "year": 2027,
  "rates": {
    "session": 60000, "private": 75000, "student": 15000, "transport": 20000,
    "kategori": "freelance", "gaji_pokok": 0, "tunjangan": 0, "maks_sesi": null
  },
  "rows": [
    {
      "month": 1, "month_label": "Januari", "published": true,
      "private_count": 4, "regular_count": 10, "session_count": 6, "day_count": 5,
      "fee_private": 300000, "fee_regular": 150000, "fee_session": 360000, "fee_transport": 100000,
      "fee_pokok": 0, "fee_tunjangan": 0, "extra_session_count": 0, "fee_extra_session": 0, "fee_insentif": 50000,
      "total": 960000
    }
  ],
  "totals": { "...": "jumlah setahun, key sama seperti tiap baris di 'rows' (tanpa month/month_label/published)" }
}
```
`rows` **selalu berisi 12 item** (Januari–Desember), urut. Field `rates` berguna untuk menampilkan "tarif Anda saat ini" di layar laporan, terpisah dari angka historis per bulan.

### 9.1 Endpoint file PDF — perlakuan khusus

`GET /reports/slip-gaji?month=` dan `GET /reports/summary?month=` **mengembalikan file PDF biner**, bukan JSON:
- Response header `Content-Type: application/pdf` dan `Content-Disposition: attachment; filename="..."`.
- Mobile harus menangani ini sebagai **file download**, bukan `response.json()` — pakai client HTTP yang mendukung response biner (simpan ke storage lokal device, lalu buka dengan viewer PDF bawaan OS, atau tampilkan di in-app PDF viewer).
- `slip-gaji` mengembalikan error `404` JSON biasa (`{"message": "Fee bulan ... belum diterbitkan oleh admin."}`) kalau periode belum diterbitkan admin — **cek Content-Type response** untuk membedakan "berhasil dapat PDF" vs "dapat pesan error JSON", karena keduanya memakai endpoint & method yang sama.
- `summary` (summary pengajaran) **tidak punya syarat periode diterbitkan** — selalu bisa diunduh untuk bulan mana saja yang tutor punya datanya.

---

## 10. Error umum (berlaku di semua endpoint)

| Status | Kapan terjadi | Body |
|---|---|---|
| `401` | Token tidak ada/tidak valid/kedaluwarsa | `{"message": "Unauthenticated."}` |
| `403` | Role bukan tutor, akun user belum tertaut ke data master Tutor, atau mengakses data (siswa/sesi) yang bukan miliknya | `{"message": "..."}` |
| `404` | Resource (`{id}`) tidak ditemukan | default Laravel 404 JSON, atau pesan khusus (lihat §9.1 untuk slip gaji) |
| `422` | Validasi input gagal, atau pelanggaran aturan bisnis | `{"message": "...", "errors": {...}}` untuk validasi field; `{"message": "..."}` saja untuk aturan bisnis (lihat tiap endpoint) |

Mobile cukup menampilkan `message` dari body error secara langsung — semua pesan sudah dalam Bahasa Indonesia dan siap ditampilkan ke pengguna (tidak perlu di-translate/disusun ulang). Untuk error validasi (`errors` ada isinya), tiap key adalah nama field dan isinya array pesan — ambil elemen pertama untuk ditampilkan di bawah input terkait.

---

## 11. Rekomendasi UI/UX umum

Bagian ini bukan spesifikasi wajib, tapi rangkuman pola yang sudah terbukti jalan di versi web — dipakai sebagai referensi supaya desain mobile konsisten secara perilaku (istilah, urutan info, konfirmasi sebelum aksi destruktif) walau tata letak visualnya bebas disesuaikan untuk mobile.

**Jadwal dari Pola (§6):**
1. **Dua seksi terpisah secara visual**: "Perlu Dikonfirmasi" (badge kuning/oranye, prioritas atas) dan "Jadwal Tersedia" (badge biru). Jangan digabung jadi satu list campur.
2. **Kartu per slot**, bukan tabel — hari+tanggal, jam sesi, mata pelajaran, nama group, dan **jumlah siswa saja** (bukan daftar nama langsung di kartu) dengan tombol terpisah untuk membuka popup/bottom-sheet daftar nama siswa.
3. **Konfirmasi destruktif**: aksi `izin` dan `withdraw` sebaiknya memunculkan dialog konfirmasi dulu, dengan teks yang menjelaskan konsekuensi — khususnya untuk `izin`, tekankan "jadwal rutin Anda tetap berlanjut minggu depan".
4. **Status visual per slot**: `open`/Tersedia → biru, `pending_confirmation`/Menunggu Konfirmasi → kuning/oranye, `vacant`/Butuh Pengganti → merah, `assigned`/Complete → hijau.
5. **Badge jumlah** di tab/menu navigasi untuk `needs_confirmation.length`.
6. **Filter kualifikasi sudah dilakukan server** — `available` HANYA berisi slot yang mapelnya cocok dengan `specialization` tutor; mobile tidak perlu filter ulang.

**Umum (seluruh aplikasi):**
7. **Status kehadiran** (`student_attendance`, dipakai di modul Evaluasi & muncul di `review`/dashboard) punya warna standar di versi web yang baik diikuti: `hadir` → hijau, `izin` → kuning, `alfa` → merah.
8. **Field foto** (`tutor.photo`) adalah path relatif, bukan URL — pastikan base URL storage dikonfigurasi sekali di layer networking app, bukan di-hardcode per layar.
9. **Pesan error selalu Bahasa Indonesia & siap-tampil** (lihat §10) — hindari membungkus ulang dengan pesan generik "Terjadi kesalahan" kecuali untuk error jaringan/5xx yang benar-benar tidak terduga.
10. **Endpoint dengan file (foto profil, PDF laporan)** memakai `multipart/form-data` untuk upload dan response biner untuk download — jangan asumsikan semua endpoint API ini JSON-only.

---

## 12. Di luar scope dokumen ini

- Push notification (slot baru yang cocok kualifikasi, keputusan admin atas lamaran, evaluasi yang mendekati jatuh tempo, dll.) — belum ada infrastrukturnya di backend saat ini, perlu dibahas terpisah bila dibutuhkan.
- Alur **lupa password** — endpoint `create-password` khusus untuk tutor yang BELUM PERNAH set password; reset password untuk akun yang sudah punya password belum ada endpoint API-nya (versi web punya alur admin reset manual, belum diekspos sebagai API self-service).
- Melihat slot pola yang sudah lewat jauh ke belakang (arsip) — modul §6 didesain untuk "minggu berjalan/ke depan". Riwayat mengajar yang sudah selesai ada di §5 dan §9 (tidak dibatasi rentang waktu).
- Endpoint §6 tidak memberi tahu alasan spesifik kenapa lamaran `rejected` (admin tidak diwajibkan isi alasan) — hanya status saja.
- Manajemen multi-device/sesi aktif (lihat & cabut token dari perangkat lain) — saat ini `logout` hanya bisa mencabut token perangkat yang sedang dipakai memanggilnya sendiri.
