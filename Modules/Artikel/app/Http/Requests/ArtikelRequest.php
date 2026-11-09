<?php

namespace Modules\Artikel\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArtikelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pembatasan role sudah dilakukan middleware 'role:admin' pada route.
        return true;
    }

    /**
     * Thumbnail bersifat opsional: saat edit, tanpa file baru berarti gambar lama dipertahankan.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'judul'           => 'required|max:200',
            'konten'          => 'required',
            'kategori'        => ['required', Rule::in(array_keys(Article::KATEGORI))],
            'thumbnail'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published'    => 'nullable|boolean',
            'hapus_thumbnail' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required'     => 'Judul artikel wajib diisi.',
            'judul.max'          => 'Judul artikel maksimal 200 karakter.',
            'konten.required'    => 'Konten artikel wajib diisi.',
            'kategori.required'  => 'Kategori wajib dipilih.',
            'kategori.in'        => 'Kategori yang dipilih tidak valid.',
            'thumbnail.image'    => 'File harus berupa gambar.',
            'thumbnail.mimes'    => 'Gambar harus berformat JPG, JPEG, atau PNG.',
            'thumbnail.max'      => 'Ukuran gambar maksimal 2 MB.',
            'thumbnail.uploaded' => 'Gambar gagal diunggah. Pastikan ukurannya tidak lebih dari 2 MB.',
        ];
    }
}
