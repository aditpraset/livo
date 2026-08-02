# Changelog API Tutor

Catatan perubahan & perbaikan pada API Tutor (`/api/tutor/*`). Detail kontrak endpoint lengkap ada di [API-TUTOR.md](API-TUTOR.md).

---

## 2026-08 — Perbaikan tanggal, penambahan data kelas, & fee

### 1. Fitur: Pencarian nama siswa pada jadwal & evaluasi

- **Endpoint:** `GET /schedules/week`, `GET /evaluations`
- **Perubahan:** Tambah query param `search` (opsional) — filter berdasarkan nama siswa (`full_name`), partial match, tidak case-sensitive.
- **File:** `app/Http/Controllers/Api/Tutor/ScheduleController.php` (`week()`), `app/Http/Controllers/Api/Tutor/EvaluationController.php` (`index()`).

### 2. Fitur: Pencarian silabus pada detail evaluasi

- **Endpoint:** `GET /evaluations/{schedule}`
- **Perubahan:** Daftar `syllabi` kini disaring otomatis sesuai **mata pelajaran** (`schedule.subject_id`) dan **kelas/jenjang siswa** (`schedule.student.grade`) — sebelumnya hanya disaring per mata pelajaran, tidak memperhitungkan kelas siswa. Ditambah query param `search` untuk mencari kata kunci pada pokok/sub pokok bahasan. Bila siswa belum punya `grade`, filter kelas dilewati (fallback) — seluruh silabus mata pelajaran tsb ditampilkan.
- **File:** `app/Http/Controllers/Api/Tutor/EvaluationController.php` (`show()`).

### 3. Bug fix: Tanggal sesi salah (mundur 1 hari) pada detail evaluasi

- **Endpoint:** `GET /evaluations/{schedule}`
- **Masalah:** `class_date` pada response `schedule` bisa tampil mundur satu hari dibanding endpoint list (`GET /evaluations`). Contoh: sesi tanggal 22 Juni malah muncul `2026-06-21T17:00:00.000000Z`.
- **Penyebab:** `show()` mengembalikan objek `Schedule` mentah (`response()->json(['schedule' => $schedule, ...])`). Laravel men-serialize kolom `class_date` (cast `date`, tengah malam WIB/UTC+7) ke JSON sebagai ISO-8601 **UTC**, sehingga waktu dikonversi mundur 7 jam dan melewati batas tengah malam. Endpoint list sudah benar karena eksplisit memanggil `->toDateString()`.
- **Fix:** `show()` sekarang mengonversi `$schedule` ke array lalu meng-override `class_date` dengan `->toDateString()`, konsisten dengan `index()`.
- **File:** `app/Http/Controllers/Api/Tutor/EvaluationController.php` (`show()`).

### 4. Fitur: Info kelas (grade) siswa pada list evaluasi

- **Endpoint:** `GET /evaluations`
- **Perubahan:** Objek `student` pada tiap item kini menyertakan `grade` (mis. `"SD Kelas 1"`), sebelumnya hanya `id` dan `full_name`. Field ini sudah otomatis ada di endpoint detail (`GET /evaluations/{schedule}`) karena mengembalikan seluruh kolom student.
- **File:** `app/Http/Controllers/Api/Tutor/EvaluationController.php` (`index()`).
- **Catatan:** Field `room` (kode ruang kelas jadwal, mis. `"101"`) ternyata **sudah ada** di kedua endpoint sejak awal — gap sebelumnya hanya pada contoh response di dokumentasi, bukan pada kode.

### 5. Bug fix: Total siswa privat + semi-privat pada fee tidak sama dengan total kehadiran

- **Cakupan:** Web (Admin generate fee, Tutor rekap fee) & API (`GET /reports/rekap-fee`) — logika dipakai bersama lewat trait `ComputesTutorFee`, sehingga fix ini berlaku untuk keduanya sekaligus.
- **Masalah:** `private_count + regular_count` bisa lebih kecil dari total kehadiran ("hadir") pada bulan yang sama.
- **Penyebab (langkah 1):** `regular_count` (siswa paket Semi-Privat) sebelumnya dihitung dengan mencocokkan `student.package_id === 6` secara persis. Siswa dengan `package_id` kosong/`null` atau tidak valid (ditemukan 5 siswa dengan kondisi ini) tidak cocok dengan `5` (Privat) maupun `6` (Semi-Privat), sehingga diam-diam **hilang** dari kedua hitungan.
- **Fix (langkah 1):** `regular_count` sempat diubah jadi `total_hadir - private_count` (fallback), bukan pencocokan persis ke `package_id === 6` — lalu **disempurnakan lagi pada item 6** setelah diketahui rumus aslinya memang per-sesi, bukan per-siswa.
- **File:** `app/Http/Controllers/Concerns/ComputesTutorFee.php` (`tutorFeeBreakdown()`).

### 6. Perbaikan rumus: `fee_per_student_private` & `fee_per_session` ternyata per SESI, bukan per siswa

- **Cakupan:** Sama seperti item 5 — Admin generate fee, Tutor rekap fee, API `GET /reports/rekap-fee`, PDF slip gaji.
- **Masalah:** Setelah item 5, ditemukan bahwa rumus fee komponen a & b (dulu: `fee_per_student_private × jumlah siswa privat` dan `fee_per_session × jumlah SEMUA sesi`) tidak sesuai kebijakan yang berlaku — kedua rate ini seharusnya dihitung **per sesi/kelompok jadwal**, bukan per siswa.
- **Rumus final (a + b + c + d), satu "sesi" = satu slot tanggal + jam:**
  - **a) Sesi Privat** — `fee_per_student_private` dihitung **flat satu kali per sesi** yang berisi minimal satu siswa paket Privat (package_id 5), **bukan** dikali jumlah siswa privat di sesi itu.
  - **b) Sesi Semi-Privat** — `fee_per_session` dihitung **flat satu kali per sesi** yang **tidak** berisi siswa Privat sama sekali (semi-privat/fallback) — alternatif dari (a), tidak keduanya sekaligus.
  - **c) Per siswa** — `fee_per_student × total kehadiran "hadir"` di **seluruh sesi** bulan itu, **tanpa memandang paket** (privat maupun semi-privat tetap dihitung di sini).
  - **d) Transport** — tidak berubah, `fee_transport_per_day × jumlah hari mengajar`.
  - Sesi **campuran** (ada siswa Privat & non-Privat sekaligus) diklasifikasikan sebagai sesi Privat (a); siswa non-Privat di sesi itu tetap masuk hitungan (c).
  - Contoh: 1 sesi, 10 siswa package_id 5, rate a=30.000, b=15.000, c=5.000/siswa, d=10.000/hari → total = a(30.000, sesi privat) + c(5.000×10=50.000) + d(10.000) = **90.000**.
- **Perubahan penting:** nama kolom di tabel `tutor_fees` **tidak berubah** (`private_count`, `session_count`, `regular_count`, dst.) tapi maknanya bergeser:
  - `private_count`/`fee_private` → jumlah **sesi** Privat (dulu: jumlah siswa privat).
  - `session_count`/`fee_session` → jumlah **sesi** non-Privat (dulu: jumlah **seluruh** sesi, tanpa pandang paket).
  - `regular_count`/`fee_regular` → total **siswa** hadir semua paket (dulu: hanya siswa semi-privat).
  - `day_count`/`fee_transport` → tidak berubah.
- **File:**
  - `app/Http/Controllers/Concerns/ComputesTutorFee.php` (`tutorFeeBreakdown()`) — logika inti.
  - `app/Http/Controllers/Admin/TutorFeeController.php` (`data()`) — label satuan kolom review admin.
  - `resources/views/admin/tutor-fees/index.blade.php` — header tabel & modal edit manual.
  - `resources/views/tutor/reports/rekap-fee.blade.php` — header tabel & kartu tarif.
  - `resources/views/tutor/reports/pdf/slip-gaji.blade.php` — label baris rincian.
  - `documentation/API-TUTOR.md` (bagian 7.2 Rekap Fee) — tabel komponen a/b/c/d.

### Verifikasi

Setiap item di atas sudah diverifikasi terhadap data nyata di database (bukan hanya asumsi unit), lewat script sekali-pakai yang dijalankan via `php artisan tinker` / `php <script>.php` lalu dihapus setelah dikonfirmasi. Item 6 khususnya diuji dengan skenario persis dari kasus yang dilaporkan (1 sesi, 10 siswa privat) dan menghasilkan total yang cocok.
