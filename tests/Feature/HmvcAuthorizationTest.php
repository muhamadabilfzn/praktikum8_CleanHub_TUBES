<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Facades\Module;
use Tests\Feature\Concerns\SesiRole;
use Tests\TestCase;

/**
 * Bukti arsitektur HMVC + authorization berbasis role pada project CleanHub.
 */
class HmvcAuthorizationTest extends TestCase
{
    use RefreshDatabase, SesiRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanRoleDanAdmin();
    }

    public function test_dua_modul_hmvc_aktif(): void
    {
        foreach (['Artikel', 'Petugas'] as $nama) {
            $this->assertTrue(Module::find($nama)->isEnabled(), "Modul {$nama} harus aktif");
        }
    }

    public function test_route_modul_dilindungi_middleware_auth_dan_role_admin(): void
    {
        foreach (['admin.artikel.index', 'admin.artikel.data', 'admin.artikel.store', 'admin.petugas.index', 'admin.petugas.store'] as $nama) {
            $middleware = Route::getRoutes()->getByName($nama)->gatherMiddleware();
            $this->assertContains('auth', $middleware, "{$nama} harus memakai middleware auth");
            $this->assertContains('role:admin', $middleware, "{$nama} harus memakai role:admin");
        }
    }

    public function test_tamu_diarahkan_ke_login_pada_halaman_pengelolaan(): void
    {
        foreach (['/admin/artikel', '/admin/petugas', '/admin/petugas/create'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_tamu_yang_memakai_ajax_mendapat_401_json_bukan_redirect(): void
    {
        $this->getJson('/admin/artikel/data')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->postJson('/admin/artikel', [])->assertUnauthorized();
    }

    public function test_masyarakat_dan_petugas_mendapat_403_pada_modul_admin(): void
    {
        foreach (['masyarakat', 'petugas'] as $role) {
            foreach (['/admin/artikel', '/admin/petugas', '/admin/petugas/create'] as $url) {
                $this->sebagai($role)->get($url)->assertForbidden();
            }
            $this->sebagai($role)->getJson('/admin/artikel/data')->assertForbidden();
            $this->sebagai($role)->postJson('/admin/artikel', [])->assertForbidden();
            $this->sebagai($role)->post('/admin/petugas', [])->assertForbidden();
        }
    }

    public function test_halaman_403_ditampilkan_dengan_pesan_role(): void
    {
        $this->sebagai('masyarakat')->get('/admin/artikel')
            ->assertForbidden()->assertSee('403')->assertSee('hanya untuk: admin');
    }

    public function test_admin_dapat_membuka_kedua_modul(): void
    {
        $this->sebagai('admin')->get('/admin/artikel')->assertOk();
        $this->sebagai('admin')->get('/admin/petugas')->assertOk();
    }
}
