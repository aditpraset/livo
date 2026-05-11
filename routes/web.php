<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pendaftaran', [HomeController::class, 'registration'])->name('registration');
Route::post('/pendaftaran', [HomeController::class, 'storeRegistration'])->name('registration.store');

// Auth Routes
Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Protected Admin Routes
    Route::middleware(['auth'])->group(function () {
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
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Payment CRUD
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/data/payments', [PaymentController::class, 'dataPayments'])->name('data.payments');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'printReceipt'])->name('payments.receipt');

        // Schedule Sessions (Master)
        Route::get('/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'index'])->name('schedule-sessions.index');
        Route::get('/data/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'data'])->name('schedule-sessions.data');
        Route::post('/schedule-sessions', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'store'])->name('schedule-sessions.store');
        Route::get('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'show'])->name('schedule-sessions.show');
        Route::put('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'update'])->name('schedule-sessions.update');
        Route::delete('/schedule-sessions/{scheduleSession}', [\App\Http\Controllers\Admin\ScheduleSessionController::class, 'destroy'])->name('schedule-sessions.destroy');

        // Student Schedule Management
        Route::post('/students/{student}/schedules', [\App\Http\Controllers\Admin\ScheduleStudentController::class, 'store'])->name('students.schedules.store');
        Route::get('/schedules/{scheduleStudent}', [\App\Http\Controllers\Admin\ScheduleStudentController::class, 'show'])->name('schedules.show');
        Route::put('/schedules/{scheduleStudent}', [\App\Http\Controllers\Admin\ScheduleStudentController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{scheduleStudent}', [\App\Http\Controllers\Admin\ScheduleStudentController::class, 'destroy'])->name('schedules.destroy');
    });
});
