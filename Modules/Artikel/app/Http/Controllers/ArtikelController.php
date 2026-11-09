<?php

namespace Modules\Artikel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Artikel\Http\Requests\ArtikelRequest;

/**
 * CRUD AJAX: halaman dimuat sekali, seluruh operasi tambah/tampil/ubah/hapus
 * dilakukan lewat request AJAX yang membalas JSON (tanpa refresh halaman).
 */
class ArtikelController extends Controller
{
    /** Halaman kosong; datanya diambil lewat AJAX ke getData(). */
    public function index(): View
    {
        return view('artikel::index', ['kategori' => Article::KATEGORI]);
    }

    /** Seluruh artikel sebagai JSON untuk mengisi tabel. */
    public function getData(): JsonResponse
    {
        $artikel = Article::with('penulis')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Article $a) => [
                'id'             => $a->id,
                'judul'          => $a->judul,
                'kategori'       => $a->kategori,
                'kategori_label' => $a->kategori_label,
                'penulis'        => $a->penulis->nama_lengkap ?? '-',
                'is_published'   => $a->is_published,
                'tanggal'        => $a->created_at->format('d/m/Y'),
                'thumbnail_url'  => $a->thumbnail_url,
            ]);

        return response()->json($artikel);
    }

    public function store(ArtikelRequest $request): JsonResponse
    {
        $data = $request->validated();

        $thumbnail = $request->hasFile('thumbnail')
            ? $request->file('thumbnail')->store('artikel', 'public')
            : null;

        Article::create([
            'user_id'      => session('user_id'),
            'thumbnail'    => $thumbnail,
            'judul'        => $data['judul'],
            'slug'         => Article::buatSlug($data['judul']),
            'konten'       => $data['konten'],
            'kategori'     => $data['kategori'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return response()->json(['success' => 'Artikel berhasil disimpan.']);
    }

    /** Data satu artikel untuk mengisi form edit (modal). */
    public function edit(Article $article): JsonResponse
    {
        return response()->json([
            'id'            => $article->id,
            'judul'         => $article->judul,
            'kategori'      => $article->kategori,
            'konten'        => $article->konten,
            'is_published'  => $article->is_published,
            'thumbnail'     => $article->thumbnail,
            'thumbnail_url' => $article->thumbnail_url,
        ]);
    }

    public function update(ArtikelRequest $request, Article $article): JsonResponse
    {
        $data = $request->validated();

        // Ganti gambar -> hapus file lama. Centang "hapus gambar" -> hapus tanpa pengganti.
        $thumbnail = $article->thumbnail;

        if ($request->hasFile('thumbnail')) {
            $this->hapusFile($thumbnail);
            $thumbnail = $request->file('thumbnail')->store('artikel', 'public');
        } elseif ($request->boolean('hapus_thumbnail')) {
            $this->hapusFile($thumbnail);
            $thumbnail = null;
        }

        $article->update([
            'thumbnail'    => $thumbnail,
            'judul'        => $data['judul'],
            'slug'         => Article::buatSlug($data['judul'], $article->id),
            'konten'       => $data['konten'],
            'kategori'     => $data['kategori'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return response()->json(['success' => 'Artikel berhasil diperbarui.']);
    }

    /** Ubah status Published/Draft tanpa membuka form. */
    public function toggle(Article $article): JsonResponse
    {
        $article->update(['is_published' => ! $article->is_published]);

        return response()->json(['success' => 'Status artikel diubah menjadi '.($article->is_published ? 'Published' : 'Draft').'.']);
    }

    public function destroy(Article $article): JsonResponse
    {
        $this->hapusFile($article->thumbnail);
        $article->delete();

        return response()->json(['success' => 'Artikel berhasil dihapus.']);
    }

    private function hapusFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
