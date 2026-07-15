<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ClassScheduleController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TutorController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pendaftaran', [HomeController::class, 'registration'])->name('registration');
Route::post('/pendaftaran', [HomeController::class, 'storeRegistration'])->name('registration.store');
Route::get('/pendaftaran/cek-promo', [HomeController::class, 'checkPromo'])->name('registration.check-promo');

// Auth Routes (login 2 langkah: email → password / buat password)
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Redirect sesuai role setelah login (dipakai juga oleh middleware guest)
Route::get('/dashboard', function () {
    $user = auth()->user();
    return match (true) {
        $user->hasRole('admin') => redirect()->route('admin.dashboard'),
        $user->hasRole('tutor') => redirect()->route('tutor.dashboard'),
        $user->hasRole('siswa') => redirect()->route('siswa.dashboard'),
        default => redirect()->route('admin.login'),
    };
})->middleware('auth')->name('dashboard');

// ── Area Tutor ──
Route::prefix('tutor')->name('tutor.')->middleware(['auth', 'role:tutor'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Tutor\DashboardController::class, 'index'])->name('dashboard');

    // Jadwal mingguan + detail siswa
    Route::get('/jadwal', [\App\Http\Controllers\Tutor\ScheduleController::class, 'week'])->name('schedules.week');
    Route::get('/siswa/{student}', [\App\Http\Controllers\Tutor\ScheduleController::class, 'studentDetail'])->name('students.show');
    Route::get('/data/siswa/{student}/riwayat', [\App\Http\Controllers\Tutor\ScheduleController::class, 'dataStudentHistory'])->name('students.data');

    // Evaluasi yang harus diisi
    Route::get('/evaluasi', [\App\Http\Controllers\Tutor\EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/data/evaluasi', [\App\Http\Controllers\Tutor\EvaluationController::class, 'data'])->name('evaluations.data');
    Route::get('/evaluasi/{schedule}', [\App\Http\Controllers\Tutor\EvaluationController::class, 'create'])->name('evaluations.create');
    Route::post('/evaluasi/{schedule}', [\App\Http\Controllers\Tutor\EvaluationController::class, 'store'])->name('evaluations.store');

    // Profil tutor
    Route::get('/profil', [\App\Http\Controllers\Tutor\ProfileController::class, 'show'])->name('profile');
    Route::put('/profil', [\App\Http\Controllers\Tutor\ProfileController::class, 'update'])->name('profile.update');

    // Rekapitulasi
    Route::get('/rekap-pengajaran', [\App\Http\Controllers\Tutor\ReportController::class, 'rekapPengajaran'])->name('rekap-pengajaran');
    Route::get('/data/rekap-pengajaran', [\App\Http\Controllers\Tutor\ReportController::class, 'dataRekapPengajaran'])->name('rekap-pengajaran.data');
    Route::get('/rekap-fee', [\App\Http\Controllers\Tutor\ReportController::class, 'rekapFee'])->name('rekap-fee');

    // Laporan (PDF)
    Route::get('/laporan', [\App\Http\Controllers\Tutor\ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/slip-gaji', [\App\Http\Controllers\Tutor\ReportController::class, 'slipGaji'])->name('reports.slip-gaji');
    Route::get('/laporan/summary', [\App\Http\Controllers\Tutor\ReportController::class, 'summaryPengajaran'])->name('reports.summary');
});

// ── Area Siswa ──
Route::prefix('siswa')->name('siswa.')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/', function () {
        return view('siswa.dashboard');
    })->name('dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login/email', [LoginController::class, 'checkEmail'])->name('login.check-email');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('/login/create-password', [LoginController::class, 'createPassword'])->name('login.create-password');
    Route::post('/login/reset-email', [LoginController::class, 'resetEmail'])->name('login.reset-email');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected Admin Routes (hanya role admin)
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/registrations', [AdminController::class, 'registrations'])->name('registrations');
        
        // DataTables Ajax Routes
        Route::get('/data/dashboard', [AdminController::class, 'dataDashboard'])->name('data.dashboard');
        Route::get('/data/registrations', [AdminController::class, 'dataRegistrations'])->name('data.registrations');
        Route::get('/registrations/{registration}', [AdminController::class, 'showRegistration'])->name('registrations.show');
        Route::patch('/registrations/{registration}/status', [AdminController::class, 'updateStatus'])->name('registrations.update-status');
        Route::post('/registrations/{registration}/payment', [AdminController::class, 'storePayment'])->name('registrations.store-payment');
        Route::get('/registrations/{registration}/receipt', [AdminController::class, 'printReceipt'])->name('registrations.receipt');

        // Student CRUD
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/data/students', [StudentController::class, 'dataStudents'])->name('data.students');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::get('/students/template', [StudentController::class, 'template'])->name('students.template');
        Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Payment CRUD
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/data/payments', [PaymentController::class, 'dataPayments'])->name('data.payments');
        Route::get('/payments-reminders', [PaymentController::class, 'reminders'])->name('payments.reminders');
        Route::get('/data/payments-reminders', [PaymentController::class, 'dataReminders'])->name('data.payments-reminders');
        Route::get('/payments-reminders/{student}/print', [PaymentController::class, 'printReminder'])->name('payments.reminders.print');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::get('/payments/template', [PaymentController::class, 'template'])->name('payments.template');
        Route::post('/payments/import', [PaymentController::class, 'import'])->name('payments.import');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

        // Schedule Sessions (Master — lama, dipertahankan)
        Route::get('/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'index'])->name('schedule-sessions.index');
        Route::get('/data/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'data'])->name('schedule-sessions.data');
        Route::post('/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'store'])->name('schedule-sessions.store');
        Route::get('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'show'])->name('schedule-sessions.show');
        Route::put('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'update'])->name('schedule-sessions.update');
        Route::delete('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'destroy'])->name('schedule-sessions.destroy');

        // ── Master Data ──────────────────────────────────────────────
        // Paket Belajar
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('/data/packages', [PackageController::class, 'data'])->name('packages.data');
        Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        // Promo & Diskon
        Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
        Route::get('/data/promos', [PromoController::class, 'data'])->name('promos.data');
        Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
        Route::put('/promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
        Route::delete('/promos/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');

        // Mata Pelajaran
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/data/subjects', [SubjectController::class, 'data'])->name('subjects.data');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        // Silabus (per Mata Pelajaran)
        Route::get('/subjects/{subject}/syllabi', [\App\Http\Controllers\Admin\SyllabusController::class, 'index'])->name('subjects.syllabi.index');
        Route::get('/subjects/{subject}/syllabi/data', [\App\Http\Controllers\Admin\SyllabusController::class, 'data'])->name('subjects.syllabi.data');
        Route::get('/subjects/{subject}/syllabi/template', [\App\Http\Controllers\Admin\SyllabusController::class, 'template'])->name('subjects.syllabi.template');
        Route::post('/subjects/{subject}/syllabi/import', [\App\Http\Controllers\Admin\SyllabusController::class, 'import'])->name('subjects.syllabi.import');
        Route::post('/subjects/{subject}/syllabi', [\App\Http\Controllers\Admin\SyllabusController::class, 'store'])->name('subjects.syllabi.store');
        Route::put('/subjects/{subject}/syllabi/{syllabus}', [\App\Http\Controllers\Admin\SyllabusController::class, 'update'])->name('subjects.syllabi.update');
        Route::delete('/subjects/{subject}/syllabi/{syllabus}', [\App\Http\Controllers\Admin\SyllabusController::class, 'destroy'])->name('subjects.syllabi.destroy');

        // Program
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::get('/data/programs', [ProgramController::class, 'data'])->name('programs.data');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');

        // Jenjang (Grade)
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/data/grades', [GradeController::class, 'data'])->name('grades.data');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::put('/grades/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');

        // Harga (Pricing) — per paket, program, jenjang & durasi
        Route::get('/pricings', [PricingController::class, 'index'])->name('pricings.index');
        Route::get('/data/pricings', [PricingController::class, 'data'])->name('pricings.data');
        Route::post('/pricings', [PricingController::class, 'store'])->name('pricings.store');
        Route::put('/pricings/{pricing}', [PricingController::class, 'update'])->name('pricings.update');
        Route::delete('/pricings/{pricing}', [PricingController::class, 'destroy'])->name('pricings.destroy');

        // Master Jadwal Kelas
        Route::get('/class-schedules', [ClassScheduleController::class, 'index'])->name('class-schedules.index');
        Route::get('/data/class-schedules', [ClassScheduleController::class, 'data'])->name('class-schedules.data');
        Route::post('/class-schedules', [ClassScheduleController::class, 'store'])->name('class-schedules.store');
        Route::put('/class-schedules/{classSchedule}', [ClassScheduleController::class, 'update'])->name('class-schedules.update');
        Route::delete('/class-schedules/{classSchedule}', [ClassScheduleController::class, 'destroy'])->name('class-schedules.destroy');

        // Tutor
        Route::get('/tutors', [TutorController::class, 'index'])->name('tutors.index');
        Route::get('/data/tutors', [TutorController::class, 'data'])->name('tutors.data');
        Route::post('/tutors', [TutorController::class, 'store'])->name('tutors.store');
        Route::put('/tutors/{tutor}', [TutorController::class, 'update'])->name('tutors.update');
        Route::delete('/tutors/{tutor}', [TutorController::class, 'destroy'])->name('tutors.destroy');

        // ── Penjadwalan ───────────────────────────────────────────────
        Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/data/schedules', [ScheduleController::class, 'data'])->name('schedules.data');
        Route::get('/data/schedules-grouped', [ScheduleController::class, 'grouped'])->name('schedules.grouped');
        Route::get('/schedules/group-students', [ScheduleController::class, 'groupStudents'])->name('schedules.group-students');
        Route::get('/schedules/evaluation-template', [ScheduleController::class, 'evaluationTemplate'])->name('schedules.evaluation-template');
        Route::post('/schedules/import-evaluation', [ScheduleController::class, 'importEvaluation'])->name('schedules.import-evaluation');
        Route::get('/schedules/events', [ScheduleController::class, 'events'])->name('schedules.events');
        Route::get('/schedules/student-schedule-info/{student}', [ScheduleController::class, 'studentScheduleInfo'])->name('schedules.student-info');
        Route::post('/schedules/generate', [ScheduleController::class, 'generate'])->name('schedules.generate');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::put('/schedules/{schedule}/status', [ScheduleController::class, 'updateStatus'])->name('schedules.updateStatus');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // ── Evaluasi ──────────────────────────────────────────────────
        Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('/data/evaluations', [EvaluationController::class, 'data'])->name('data.evaluations');
        Route::get('/evaluations/student/{student}', [EvaluationController::class, 'studentReport'])->name('evaluations.student');
        Route::get('/data/evaluations/student/{student}', [EvaluationController::class, 'dataStudentReport'])->name('data.evaluations.student');
        Route::get('/evaluations/student/{student}/summary-pdf', [EvaluationController::class, 'downloadSummary'])->name('evaluations.student.summary');
        Route::get('/evaluations/student/{student}/excel', [EvaluationController::class, 'exportStudentExcel'])->name('evaluations.student.excel');
        Route::post('/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
        Route::put('/evaluations/{evaluation}', [EvaluationController::class, 'update'])->name('evaluations.update');
        Route::put('/evaluations/{evaluation}/publish', [EvaluationController::class, 'publish'])->name('evaluations.publish');

        // Student Schedule Management (lama — dipertahankan untuk backward compat)
        Route::post('/students/{student}/schedules', [\App\Http\Controllers\Admin\ScheduleStudentController::class, 'store'])->name('students.schedules.store');
    });
});
