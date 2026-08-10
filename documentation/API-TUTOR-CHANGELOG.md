# Changelog API Tutor

Catatan perubahan & perbaikan pada API Tutor (`/api/tutor/*`). Detail kontrak endpoint lengkap ada di [API-TUTOR.md](API-TUTOR.md).

---

## 2026-08-07 — Web: Evaluasi Siswa dikelompokkan per minggu → per hari → per sesi

- **Cakupan:** Web Tutor saja (`GET /tutor/evaluasi`) — tidak mengubah API.
- **Perubahan:** Halaman Evaluasi Siswa (tab "Belum Dievaluasi" & "Sudah Dievaluasi") sebelumnya pakai tabel DataTables (server-side, paginasi 15/halaman, satu baris per siswa, tanpa batas tanggal). Sekarang mengikuti pola yang sama dengan Jadwal Mingguan: dibatasi **per minggu** (navigasi Minggu Lalu/Ini/Depan), dikelompokkan per hari lalu per sesi (jam) — satu baris per sesi dengan tombol "Lihat Siswa (N)" yang membuka modal.
- **Isi modal:** untuk tiap siswa pada sesi tsb — status (kehadiran bila mode "Sudah Dievaluasi", atau status sesi bila "Belum Dievaluasi"), badge Feedback Siswa + tombol ubah cepat (modal kedua, tanpa reload halaman penuh), dan tombol Isi/Edit Evaluasi (ke form lengkap).
- **Dampak:** Endpoint AJAX lama `GET /data/evaluasi` (`tutor.evaluations.data`, DataTables) **dihapus** — tidak dipakai lagi. `EvaluationController::index()` kini mengembalikan halaman penuh (bukan JSON), method `data()` dihapus.
- **Catatan:** Karena mode "Belum Dievaluasi" kini juga dibatasi per minggu (mengikuti `class_date` sesi), sesi lewat yang belum dievaluasi dari minggu-minggu sebelumnya tidak otomatis tampil di "Minggu Ini" — tutor perlu navigasi ke minggu terkait untuk melihatnya (sama seperti cara kerja Jadwal Mingguan).
- **File:** `app/Http/Controllers/Tutor/EvaluationController.php` (`index()` dirombak, `data()` dihapus), `resources/views/tutor/evaluations/index.blade.php` (dirombak total), `routes/web.php` (route `evaluations.data` dihapus).
- **Verifikasi:** diuji render langsung untuk mode `pending` & `done` dengan user tutor nyata — data per sesi (termasuk `has_evaluation`, `attendance`, `student_feedback`, `create_url`) terisi benar di JSON yang dikonsumsi modal.

---

## 2026-08-07 — Web: Jadwal mingguan tutor dikelompokkan per sesi

- **Cakupan:** Web Tutor saja (`GET /tutor/jadwal`) — tidak mengubah API.
- **Perubahan:** Sebelumnya satu baris tabel = satu siswa, sehingga sesi dengan banyak siswa (kelas semi-privat/reguler) tampil sebagai banyak baris berulang. Sekarang baris dikelompokkan **per sesi** (slot jam, per hari) — satu baris per sesi, dengan tombol "Lihat Siswa (N)" yang membuka modal berisi daftar siswa pada sesi itu (nama, kelas, mata pelajaran, status kehadiran/evaluasi, tautan ke history siswa).
- **Detail:** Pengelompokan sesi memakai kunci jam mulai+selesai (mengikuti pola yang sama seperti perhitungan "jumlah sesi" di rekap pengajaran/fee — bukan per siswa). Kolom Kelas/Ruang & Mata Pelajaran pada baris sesi menggabungkan nilai unik bila ada lebih dari satu (jarang terjadi, biasanya seragam per sesi). Kolom Status jadi ringkasan jumlah per status (mis. "3 Selesai, 1 Terjadwal").
- **File:** `app/Http/Controllers/Tutor/ScheduleController.php` (`week()`), `resources/views/tutor/schedules/week.blade.php`.
- **Verifikasi:** diuji dengan data nyata — sesi dengan 3 siswa berhasil tampil sebagai 1 baris, modal menampilkan ketiga siswa dengan benar.

---

## 2026-08-07 — Fitur: Feedback siswa (melekat pada sesi, bukan pada evaluasi)

- **Cakupan:** Web Tutor (menu Evaluasi Siswa — tab "Belum Dievaluasi" & "Sudah Dievaluasi") dan API.
- **Perubahan:** Tambah field `student_feedback` dengan 5 pilihan tetap: `buruk` (Buruk), `kurang_baik` (Kurang Baik), `cukup_baik` (Cukup Baik), `baik` (Baik), `sangat_baik` (Sangat Baik). Opsional — boleh dikosongkan.
- **Keputusan desain penting — field melekat pada `Schedule` (sesi), bukan `Evaluation`:** awalnya diimplementasikan sebagai kolom pada `evaluations`, tapi ini salah karena artinya feedback baru bisa diisi **setelah** sesi dievaluasi (evaluasi mensyaratkan `student_attendance` yang wajib diisi). Diperbaiki dengan memindahkan kolom ke tabel `schedules`, sehingga feedback bisa diisi **kapan saja** — termasuk untuk sesi yang belum dievaluasi sama sekali.
- **Desain UI (web):** feedback **bukan** bagian dari form isi/edit evaluasi (`create.blade.php`) — field ini diisi langsung dari **tabel** daftar evaluasi lewat ikon pensil kecil di kolom "Feedback Siswa" yang membuka modal ringkas (hanya pilih & simpan feedback), tanpa perlu membuka form evaluasi lengkap. Tombol ini tampil di **kedua tab** ("Belum Dievaluasi" maupun "Sudah Dievaluasi").
- **Detail:**
  - Kolom `student_feedback` (enum, nullable) ada di tabel `schedules` (bukan `evaluations`).
  - `Schedule::FEEDBACK_OPTIONS` (const) + `Schedule::student_feedback_label` (accessor) — dipindah dari `Evaluation` ke `Schedule`.
  - Endpoint web baru `PUT /tutor/evaluasi/{schedule}/feedback` (route `tutor.evaluations.feedback`) — update `student_feedback` pada `Schedule` langsung, tidak butuh evaluasi ada lebih dulu. Hanya 403 bila sesi bukan milik tutor tsb.
  - Endpoint API baru senada: `PUT /api/tutor/evaluations/{schedule}/feedback`.
  - Tabel daftar evaluasi tutor (web, kedua tab) menampilkan kolom "Feedback Siswa" sebagai badge berwarna (merah untuk Buruk/Kurang Baik, kuning untuk Cukup Baik, hijau untuk Baik/Sangat Baik) + tombol ubah; tampil "—" bila belum diisi.
  - `student_feedback` otomatis muncul di `GET /evaluations` (list), `GET /evaluations/{schedule}` (detail, di objek `schedule`), dan `GET /reports/rekap-pengajaran`.
  - `POST /evaluations/{schedule}` (simpan evaluasi) **tidak lagi** menerima `student_feedback` di body — sudah dipisah ke endpoint feedback sendiri.
- **File:**
  - `database/migrations/2026_08_07_000001_add_student_feedback_to_schedules_table.php` (migrasi awal ke `evaluations` sudah di-rollback & dihapus, diganti migrasi ini)
  - `app/Models/Schedule.php` (`FEEDBACK_OPTIONS`, `student_feedback_label`), `app/Models/Evaluation.php` (dikembalikan seperti semula)
  - `app/Http/Controllers/Tutor/EvaluationController.php` (`data()`, `updateFeedback()` — kini operasi ke `Schedule`, tanpa syarat evaluasi ada)
  - `app/Http/Controllers/Api/Tutor/EvaluationController.php` (`index()`, `updateFeedback()` baru; `store()` tidak lagi memvalidasi `student_feedback`)
  - `app/Http/Controllers/Api/Tutor/ReportController.php` (`rekapPengajaran()`)
  - `routes/web.php` (`tutor.evaluations.feedback`), `routes/api.php` (`api.tutor.evaluations.feedback`)
  - `resources/views/tutor/evaluations/index.blade.php` (kolom + modal + JS, referensi `Schedule::FEEDBACK_OPTIONS`), `resources/views/tutor/evaluations/create.blade.php` (field feedback tidak ada di sini)
  - `documentation/API-TUTOR.md` (bagian 5.3 & 5.4 baru: Simpan Feedback Siswa)
- **Verifikasi:** diuji langsung memanggil controller dengan user tutor nyata — berhasil set feedback untuk sesi **tanpa evaluasi** (evaluation `null`), nilai valid tersimpan ke DB, nilai di luar 5 pilihan ditolak oleh validasi.

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
