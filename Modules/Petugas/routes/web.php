<?php

use Illuminate\Support\Facades\Route;
use Modules\Petugas\Http\Controllers\PetugasController;

/*
|--------------------------------------------------------------------------
| Modul Petugas (CRUD synchronous) - hanya admin yang sudah login
|--------------------------------------------------------------------------
| 'auth'      : pemeriksa sesi login CleanHub (halaman pengelolaan wajib login)
| 'role:admin': pembatas hak akses berdasarkan role
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('petugas', PetugasController::class)
        ->except('show')
        ->parameters(['petugas' => 'officer']);
});
