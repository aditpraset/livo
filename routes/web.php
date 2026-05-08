<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\LoginController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pendaftaran', [HomeController::class, 'registration'])->name('registration');
Route::post('/pendaftaran', [HomeController::class, 'storeRegistration'])->name('registration.store');

// Admin Auth Routes
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
    });
});
