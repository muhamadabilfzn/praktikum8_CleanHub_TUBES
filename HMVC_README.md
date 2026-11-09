# CleanHub - Penerapan HMVC, Authorization, dan AJAX (Tugas Praktikum Modul 8, No. 2)

Project Tugas Akhir CleanHub (Laravel 12) ditambah arsitektur **HMVC** dengan `nwidart/laravel-modules`,
authorization berbasis **role**, dan **CRUD AJAX** dengan upload file.

## Ketentuan tugas dan implementasinya

| Ketentuan | Implementasi |
|---|---|
| **(a)** Minimal 2 modul HMVC sesuai tema | `Modules/Artikel` (edukasi lingkungan) dan `Modules/Petugas` (petugas kebersihan). Keduanya dibuat dengan `php artisan module:make`. |
| **(b)** 1 modul CRUD synchronous + 1 modul CRUD AJAX, masing-masing minimal 5 item | **Petugas = synchronous** (nama_lengkap, username, email, password, no_hp, area_kerja: 6 input, form biasa + redirect). **Artikel = AJAX** (judul, kategori, konten, thumbnail, status publikasi: 5 input, modal + jQuery + JSON, tanpa refresh). |
| **(c)** Minimal 2 role dengan hak akses berbeda, dilindungi middleware | 3 role: `admin`, `petugas`, `masyarakat`. Middleware `role` (`app/Http/Middleware/CheckRole.php`) didaftarkan sebagai alias di `bootstrap/app.php`. Modul Artikel dan Petugas hanya untuk `admin`; role lain mendapat **403**. |
| **(d)** Semua halaman pengelolaan hanya setelah login | Route modul memakai `['auth', 'role:admin']`. Alias `auth` = `EnsureLoggedIn` (sesi login CleanHub). Tamu diarahkan ke `/login`; request AJAX tanpa sesi mendapat **401 JSON**. |
| **Upload file pada modul AJAX** | Input `thumbnail` (JPG/JPEG/PNG, maks 2 MB) pada modul Artikel: dikirim dengan `FormData`, disimpan di `storage/app/public/artikel` (database hanya menyimpan path), dapat dibuka kembali lewat tautan gambar, diganti saat edit (file lama dihapus), bisa dihapus lewat checkbox, dan ikut terhapus dari storage saat artikel dihapus. |

## Struktur modul

```
Modules/
  Artikel/   (AJAX)
    routes/web.php                              route admin.artikel.* (+ endpoint JSON)
    app/Http/Controllers/ArtikelController.php  index, getData, store, edit, update, toggle, destroy
    app/Http/Requests/ArtikelRequest.php        validasi + pesan error (422)
    resources/views/index.blade.php             tabel, modal, jQuery AJAX (namespace view: artikel::)
  Petugas/   (synchronous)
    routes/web.php                              Route::resource admin.petugas.*
    app/Http/Controllers/PetugasController.php  index, create, store, edit, update, destroy
    app/Http/Requests/StorePetugasRequest.php, UpdatePetugasRequest.php
    resources/views/index.blade.php, form.blade.php   (namespace view: petugas::)
```
Model (`Article`, `Officer`, `User`) tetap di `app/Models` karena dipakai bersama oleh banyak bagian aplikasi.
Fitur lain (login, laporan, dashboard, dsb.) tidak diubah.

## Yang berubah dari kode sebelumnya
- `Admin\ArticleController` dan `Admin\OfficerController` + view `admin/articles` dan `admin/officers` dipindahkan ke modul.
  Nama route (`admin.artikel.*`, `admin.petugas.*`) dan URL (`/admin/artikel`, `/admin/petugas`) **tidak berubah**, jadi menu tetap berfungsi.
- `routes/web.php`: route artikel dan petugas dipindah ke `routes/web.php` masing-masing modul.
- `EnsureLoggedIn`: request AJAX/JSON tanpa sesi kini mendapat 401 JSON (sebelumnya redirect HTML).
- `composer.json`: ditambah `nwidart/laravel-modules` dan `merge-plugin` (`Modules/*/composer.json`).
  `composer.lock` dihapus karena sudah tidak sesuai; `composer install` akan membuatnya lagi.
- `tests/Feature/ArtikelUploadTest.php` diganti tes untuk versi AJAX (`ArtikelModulTest`, `PetugasModulTest`, `HmvcAuthorizationTest`).

## Cara menjalankan
1. Nyalakan MySQL (XAMPP/Laragon) dan buat database kosong `cleanhub_ta` (nama database sudah diatur di `.env`).
2. Di folder project:
   ```bash
   composer install
   php artisan migrate:fresh --seed
   php artisan storage:link
   php artisan serve
   ```
3. Buka http://127.0.0.1:8000/login

Akun demo (password **cleanhub123**): `admin@cleanhub.id`, `petugas@cleanhub.id`, `user@cleanhub.id`.

Perintah untuk bukti laporan:
```bash
php artisan module:list
php artisan route:list --except-vendor
php artisan test
```

## Checklist screenshot untuk laporan
1. `php artisan module:list` (Artikel dan Petugas berstatus Enabled) dan `php artisan route:list --except-vendor` (route `admin.artikel.*` dan `admin.petugas.*` berasal dari `Modules\...`).
2. Login sebagai **masyarakat** atau **petugas**, lalu buka `/admin/artikel` atau `/admin/petugas` -> halaman **403**.
3. Buka `/admin/artikel` tanpa login -> diarahkan ke `/login`.
4. Modul **Petugas** (synchronous): daftar, form tambah, pesan error di bawah input, edit, hapus, pesan berhasil.
5. Modul **Artikel** (AJAX): modal tambah, error 422 (kosongkan form, upload `.pdf` atau file > 2 MB), tabel berubah tanpa refresh, pesan berhasil, ubah status Published/Draft, edit dengan gambar lama, hapus.
6. DevTools > Network > **Fetch/XHR** saat menambah/mengubah/menghapus artikel (request `data`, `artikel`, dst., balasan JSON).
7. Gambar dibuka lewat tautan (tab baru), folder `storage/app/public/artikel`, dan kolom `thumbnail` (path) pada tabel `articles` di phpMyAdmin.
8. Kondisi folder storage setelah artikel dihapus (file ikut hilang).

## Jika ada error
- `Class "Modules\..." not found` -> `composer dump-autoload`.
- Error 419 pada AJAX -> muat ulang halaman (token CSRF kedaluwarsa).
- Gambar tidak tampil (404) -> `php artisan storage:link`.
- Akses ditolak ke database -> cek `DB_*` di `.env` dan pastikan database `cleanhub_ta` sudah dibuat.
