<?php

namespace Modules\Petugas\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pembatasan role sudah dilakukan middleware 'role:admin' pada route.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => 'required|max:100',
            'username'     => 'required|max:50|unique:users,username',
            'email'        => 'required|email|max:100|unique:users,email',
            'password'     => 'required|min:6',
            'no_hp'        => 'nullable|max:20',
            'area_kerja'   => 'nullable|max:150',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max'      => 'Nama lengkap maksimal 100 karakter.',
            'username.required'     => 'Username wajib diisi.',
            'username.unique'       => 'Username sudah dipakai.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 6 karakter.',
            'no_hp.max'             => 'No. HP maksimal 20 karakter.',
            'area_kerja.max'        => 'Area kerja maksimal 150 karakter.',
        ];
    }
}
