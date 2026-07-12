<?php

use App\Http\Controllers\Api\Tutor\AuthController;
use App\Http\Controllers\Api\Tutor\DashboardController;
use App\Http\Controllers\Api\Tutor\EvaluationController;
use App\Http\Controllers\Api\Tutor\ProfileController;
use App\Http\Controllers\Api\Tutor\ReportController;
use App\Http\Controllers\Api\Tutor\ScheduleController;
use Illuminate\Support\Facades\Route;

// ── Auth Tutor (Sanctum, stateless token) ──────────────────────────────────
Route::prefix('tutor')->name('api.tutor.')->group(function () {
    Route::post('/auth/check-email', [AuthController::class, 'checkEmail'])->name('auth.check-email');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/create-password', [AuthController::class, 'createPassword'])->name('auth.create-password');

    // ── Area terproteksi: token Sanctum valid + role tutor ──────────────────
    Route::middleware(['auth:sanctum', 'role:tutor'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/schedules/week', [ScheduleController::class, 'week'])->name('schedules.week');
        Route::get('/students/{student}', [ScheduleController::class, 'studentDetail'])->name('students.show');
        Route::get('/students/{student}/history', [ScheduleController::class, 'studentHistory'])->name('students.history');

        Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
        Route::get('/evaluations/{schedule}', [EvaluationController::class, 'show'])->name('evaluations.show');
        Route::post('/evaluations/{schedule}', [EvaluationController::class, 'store'])->name('evaluations.store');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/rekap-pengajaran', [ReportController::class, 'rekapPengajaran'])->name('rekap-pengajaran');
        Route::get('/rekap-fee', [ReportController::class, 'rekapFee'])->name('rekap-fee');

        Route::get('/reports/slip-gaji', [ReportController::class, 'slipGaji'])->name('reports.slip-gaji');
        Route::get('/reports/summary', [ReportController::class, 'summaryPengajaran'])->name('reports.summary');
    });
});
