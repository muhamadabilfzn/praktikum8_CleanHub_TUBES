<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\SesiRole;
use Tests\TestCase;

/**
 * Modul Artikel = CRUD AJAX (balasan JSON) + upload thumbnail.
 */
class ArtikelModulTest extends TestCase
{
    use RefreshDatabase, SesiRole;

    private array $json = ['Accept' => 'application/json'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->siapkanRoleDanAdmin();
    }

    private function data(array $o = []): array
    {
        return array_merge([
            'judul' => 'Cara Memilah Sampah', 'kategori' => 'pemilahan', 'konten' => 'Isi artikel',
            'is_published' => 1, 'thumbnail' => UploadedFile::fake()->image('a.jpg', 800, 500)->size(300),
        ], $o);
    }

    public function test_halaman_page_memuat_url_ajax_dan_tidak_memuat_data_langsung(): void
    {
        Article::create(['user_id' => $this->admin->id, 'judul' => 'Judul Rahasia Tabel', 'slug' => 'x',
            'konten' => 'k', 'kategori' => 'pemilahan', 'is_published' => true]);

        $this->sebagai('admin')->get('/admin/artikel')->assertOk()
            ->assertSee('/admin/artikel/data', false)
            ->assertSee('/admin/artikel/:id/edit', false)
            ->assertSee('processData: false', false)
            ->assertDontSee('Judul Rahasia Tabel'); // data baru muncul lewat AJAX, bukan di HTML
    }

    public function test_store_menyimpan_data_dan_file_lalu_membalas_json(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(), $this->json)
            ->assertOk()->assertJsonStructure(['success']);

        $a = Article::firstOrFail();
        $this->assertSame($this->admin->id, $a->user_id);
        $this->assertStringStartsWith('artikel/', $a->thumbnail);
        Storage::disk('public')->assertExists($a->thumbnail);
    }

    public function test_get_data_memuat_thumbnail_url_yang_dapat_dibuka(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(), $this->json)->assertOk();
        $a = Article::firstOrFail();

        $this->sebagai('admin')->getJson('/admin/artikel/data')->assertOk()
            ->assertJsonPath('0.judul', 'Cara Memilah Sampah')
            ->assertJsonPath('0.kategori_label', 'Pemilahan Sampah')
            ->assertJsonPath('0.thumbnail_url', asset('storage/'.$a->thumbnail));

        // Halaman publik tetap menampilkan thumbnail yang sama
        $this->get('/artikel/'.$a->slug)->assertOk()->assertSee('storage/'.$a->thumbnail, false);
    }

    public function test_validasi_422_menampilkan_pesan_per_input(): void
    {
        $r = $this->sebagai('admin');

        $r->post('/admin/artikel', [], $this->json)->assertStatus(422)
            ->assertJsonValidationErrors(['judul', 'konten', 'kategori'])
            ->assertJsonPath('errors.judul.0', 'Judul artikel wajib diisi.');

        $r->post('/admin/artikel', $this->data(['thumbnail' => UploadedFile::fake()->create('x.pdf', 50, 'application/pdf')]), $this->json)
            ->assertStatus(422)->assertJsonValidationErrors('thumbnail');
        $r->post('/admin/artikel', $this->data(['thumbnail' => UploadedFile::fake()->image('x.png')->size(2500)]), $this->json)
            ->assertStatus(422)->assertJsonPath('errors.thumbnail.0', 'Ukuran gambar maksimal 2 MB.');
        $r->post('/admin/artikel', $this->data(['thumbnail' => UploadedFile::fake()->image('x.gif')]), $this->json)
            ->assertStatus(422)->assertJsonValidationErrors('thumbnail');
        $r->post('/admin/artikel', $this->data(['kategori' => 'ngawur']), $this->json)
            ->assertStatus(422)->assertJsonValidationErrors('kategori');

        $this->assertSame(0, Article::count());
    }

    public function test_edit_mengembalikan_data_lengkap_untuk_form(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(), $this->json);
        $a = Article::firstOrFail();

        $this->sebagai('admin')->getJson("/admin/artikel/{$a->id}/edit")->assertOk()
            ->assertJsonPath('judul', 'Cara Memilah Sampah')
            ->assertJsonPath('konten', 'Isi artikel')
            ->assertJsonPath('thumbnail_url', asset('storage/'.$a->thumbnail));
    }

    public function test_update_mengganti_file_lama_tanpa_file_tetap_dan_hapus_gambar(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(), $this->json);
        $a = Article::firstOrFail();
        $lama = $a->thumbnail;

        // tanpa file baru -> file lama dipertahankan
        $this->sebagai('admin')->post("/admin/artikel/{$a->id}", $this->data(['_method' => 'PUT', 'thumbnail' => null, 'judul' => 'Judul Baru']), $this->json)
            ->assertOk()->assertJsonStructure(['success']);
        $this->assertSame($lama, $a->fresh()->thumbnail);
        $this->assertSame('Judul Baru', $a->fresh()->judul);
        Storage::disk('public')->assertExists($lama);

        // file baru -> file lama terhapus dari storage
        $this->sebagai('admin')->post("/admin/artikel/{$a->id}", $this->data(['_method' => 'PUT']), $this->json)->assertOk();
        $baru = $a->fresh()->thumbnail;
        $this->assertNotSame($lama, $baru);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($baru);

        // centang hapus gambar -> file terhapus, kolom null
        $this->sebagai('admin')->post("/admin/artikel/{$a->id}", $this->data(['_method' => 'PUT', 'thumbnail' => null, 'hapus_thumbnail' => 1]), $this->json)->assertOk();
        $this->assertNull($a->fresh()->thumbnail);
        Storage::disk('public')->assertMissing($baru);
    }

    public function test_toggle_status_lewat_ajax(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(['is_published' => 1]), $this->json);
        $a = Article::firstOrFail();
        $this->assertTrue($a->is_published);

        $this->sebagai('admin')->postJson("/admin/artikel/{$a->id}/toggle")->assertOk()
            ->assertJsonPath('success', 'Status artikel diubah menjadi Draft.');
        $this->assertFalse($a->fresh()->is_published);
    }

    public function test_delete_menghapus_data_dan_file_di_storage(): void
    {
        $this->sebagai('admin')->post('/admin/artikel', $this->data(), $this->json);
        $a = Article::firstOrFail();
        $path = $a->thumbnail;

        $this->sebagai('admin')->deleteJson("/admin/artikel/{$a->id}")->assertOk()->assertJsonStructure(['success']);

        $this->assertModelMissing($a);
        Storage::disk('public')->assertMissing($path);
    }
}
