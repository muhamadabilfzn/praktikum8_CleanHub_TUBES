<?php

use Illuminate\Support\Facades\Route;
use Modules\Artikel\Http\Controllers\ArtikelController;

/*
|--------------------------------------------------------------------------
| Modul Artikel (CRUD AJAX + upload thumbnail) - hanya admin yang sudah login
|--------------------------------------------------------------------------
| Satu halaman (index) dan beberapa endpoint JSON yang dipanggil lewat AJAX.
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('artikel', [ArtikelController::class, 'index'])->name('artikel.index');
    Route::get('artikel/data', [ArtikelController::class, 'getData'])->name('artikel.data');
    Route::post('artikel', [ArtikelController::class, 'store'])->name('artikel.store');
    Route::get('artikel/{article}/edit', [ArtikelController::class, 'edit'])->name('artikel.edit');
    Route::put('artikel/{article}', [ArtikelController::class, 'update'])->name('artikel.update');
    Route::post('artikel/{article}/toggle', [ArtikelController::class, 'toggle'])->name('artikel.toggle');
    Route::delete('artikel/{article}', [ArtikelController::class, 'destroy'])->name('artikel.destroy');
});
