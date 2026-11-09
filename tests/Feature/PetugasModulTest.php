<?php

namespace Tests\Feature;

use App\Models\Officer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SesiRole;
use Tests\TestCase;

/**
 * Modul Petugas = CRUD synchronous (form biasa + redirect).
 */
class PetugasModulTest extends TestCase
{
    use RefreshDatabase, SesiRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanRoleDanAdmin();
    }

    private function data(array $o = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Budi Santoso', 'username' => 'budi', 'email' => 'budi@example.com',
            'password' => 'rahasia1', 'no_hp' => '0812345678', 'area_kerja' => 'Arcamanik',
        ], $o);
    }

    public function test_halaman_daftar_tambah_dan_edit_dapat_dirender(): void
    {
        $this->sebagai('admin')->post('/admin/petugas', $this->data());
        $officer = Officer::firstOrFail();

        $this->sebagai('admin')->get('/admin/petugas')->assertOk()->assertSee('Budi Santoso')->assertSee('Arcamanik');
        $this->sebagai('admin')->get('/admin/petugas/create')->assertOk()->assertSee('Tambah Petugas Baru');
        $this->sebagai('admin')->get("/admin/petugas/{$officer->id}/edit")->assertOk()->assertSee('Edit Petugas');
    }

    public function test_store_membuat_user_berperan_petugas_dan_profil_petugas(): void
    {
        $this->sebagai('admin')->post('/admin/petugas', $this->data())
            ->assertRedirect('/admin/petugas')->assertSessionHas('success');

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(2, $user->role_id);
        $this->assertTrue(password_verify('rahasia1', $user->password));
        $this->assertSame('Arcamanik', Officer::where('user_id', $user->id)->firstOrFail()->area_kerja);
    }

    public function test_validasi_menampilkan_pesan_error_di_bawah_input(): void
    {
        $this->sebagai('admin')->post('/admin/petugas', [])
            ->assertSessionHasErrors(['nama_lengkap', 'username', 'email', 'password']);

        $this->sebagai('admin')->post('/admin/petugas', $this->data(['email' => 'a@a.test', 'password' => '123']))
            ->assertSessionHasErrors(['email', 'password']);

        // Pesan error tampil di bawah input pada halaman form (setelah redirect back)
        $this->sebagai('admin')->from('/admin/petugas/create')->followingRedirects()->post('/admin/petugas', [])
            ->assertOk()
            ->assertSee('Nama lengkap wajib diisi.')
            ->assertSee('Username wajib diisi.')
            ->assertSee('Password wajib diisi.');
    }

    public function test_update_mengubah_data_dan_password_opsional(): void
    {
        $this->sebagai('admin')->post('/admin/petugas', $this->data());
        $officer = Officer::firstOrFail();
        $hashLama = $officer->user->password;

        $this->sebagai('admin')->put("/admin/petugas/{$officer->id}", [
            'nama_lengkap' => 'Budi Baru', 'email' => 'budi@example.com', 'no_hp' => '0899', 'area_kerja' => 'Cisaranten', 'password' => '',
        ])->assertRedirect('/admin/petugas')->assertSessionHas('success');

        $officer->refresh()->load('user');
        $this->assertSame('Budi Baru', $officer->user->nama_lengkap);
        $this->assertSame('Cisaranten', $officer->area_kerja);
        $this->assertSame($hashLama, $officer->user->password); // password kosong = tidak berubah

        // email milik user lain ditolak, email sendiri boleh tetap
        $this->sebagai('admin')->put("/admin/petugas/{$officer->id}", [
            'nama_lengkap' => 'Budi Baru', 'email' => 'a@a.test',
        ])->assertSessionHasErrors('email');
    }

    public function test_hapus_menonaktifkan_akun_dan_menghilangkan_dari_daftar(): void
    {
        $this->sebagai('admin')->post('/admin/petugas', $this->data());
        $officer = Officer::firstOrFail();
        $userId = $officer->user_id;

        $this->sebagai('admin')->delete("/admin/petugas/{$officer->id}")
            ->assertRedirect('/admin/petugas')->assertSessionHas('success');

        $this->assertSame('nonaktif', User::find($userId)->status);
        $this->sebagai('admin')->get('/admin/petugas')->assertDontSee('Budi Santoso');
    }
}
