<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/artikel', [ArticleController::class, 'index'])->name('artikel.index');
Route::get('/artikel/{slug}', [ArticleController::class, 'show'])->name('artikel.show');

Route::get('/bantuan', [SupportController::class, 'index'])->name('bantuan.index');
Route::post('/bantuan', [SupportController::class, 'store'])->name('bantuan.store');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::view('/lupa-password', 'auth.lupa-password')->name('password.request');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('login')->name('logout');

/*
|--------------------------------------------------------------------------
| Laporan sampah
|--------------------------------------------------------------------------
*/
Route::prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/api/markers', [ReportController::class, 'markers'])->name('markers');
    Route::get('/detail/{report}', [ReportController::class, 'show'])->whereNumber('report')->name('show');

    // Hanya masyarakat yang boleh membuat / menghapus laporan
    Route::middleware(['login', 'role:masyarakat'])->group(function () {
        Route::get('/buat', [ReportController::class, 'create'])->name('create');
        Route::post('/', [ReportController::class, 'store'])->name('store');
        Route::delete('/{report}', [ReportController::class, 'destroy'])->whereNumber('report')->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Notifikasi (semua role yang sudah login)
|--------------------------------------------------------------------------
*/
Route::middleware('login')->group(function () {
    Route::get('/notif/baca/{notif}', [NotificationController::class, 'read'])->name('notif.read');
    Route::post('/notif/baca-semua', [NotificationController::class, 'readAll'])->name('notif.readAll');
});

/*
|--------------------------------------------------------------------------
| Masyarakat
|--------------------------------------------------------------------------
*/
Route::prefix('user')->name('user.')->middleware(['login', 'role:masyarakat'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/riwayat', [UserController::class, 'riwayat'])->name('riwayat');
    Route::get('/profil', [UserController::class, 'profil'])->name('profil');
    Route::put('/profil', [UserController::class, 'updateProfil'])->name('profil.update');
});

/*
|--------------------------------------------------------------------------
| Petugas kebersihan
|--------------------------------------------------------------------------
*/
Route::prefix('officer')->name('officer.')->middleware(['login', 'role:petugas'])->group(function () {
    Route::get('/dashboard', [OfficerController::class, 'dashboard'])->name('dashboard');
    Route::get('/tugasku', [OfficerController::class, 'tugasku'])->name('tugasku');
    Route::post('/ambil/{assignment}', [OfficerController::class, 'ambil'])->name('ambil');
    Route::post('/selesai/{assignment}', [OfficerController::class, 'selesai'])->name('selesai');
});

/*
|--------------------------------------------------------------------------
| Administrator
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Manajemen user
    Route::post('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::resource('users', Admin\UserController::class)->except('show')->parameters(['users' => 'user']);

    // Manajemen laporan
    Route::get('laporan', [Admin\ReportController::class, 'index'])->name('laporan.index');
    Route::get('laporan/{report}', [Admin\ReportController::class, 'show'])->name('laporan.show');
    Route::post('laporan/{report}/verifikasi', [Admin\ReportController::class, 'verify'])->name('laporan.verify');
    Route::post('laporan/{report}/assign', [Admin\ReportController::class, 'assign'])->name('laporan.assign');
    Route::post('laporan/{report}/status', [Admin\ReportController::class, 'updateStatus'])->name('laporan.status');
    Route::delete('laporan/{report}', [Admin\ReportController::class, 'destroy'])->name('laporan.destroy');

    // Manajemen petugas  -> Modules/Petugas (CRUD synchronous)
    // Manajemen artikel  -> Modules/Artikel (CRUD AJAX + upload thumbnail)
    // Kedua modul memuat route-nya sendiri (Modules/*/routes/web.php) dengan middleware ['auth', 'role:admin'].

    // Analytics
    Route::get('analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/api', [Admin\AnalyticsController::class, 'api'])->name('analytics.api');
});
