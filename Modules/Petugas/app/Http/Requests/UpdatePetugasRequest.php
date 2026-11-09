<?php

namespace Modules\Petugas\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $officer = $this->route('officer');

        return [
            'nama_lengkap' => 'required|max:100',
            'email'        => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($officer->user_id)],
            'no_hp'        => 'nullable|max:20',
            'password'     => 'nullable|min:6',
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
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar.',
            'password.min'          => 'Password minimal 6 karakter.',
            'no_hp.max'             => 'No. HP maksimal 20 karakter.',
            'area_kerja.max'        => 'Area kerja maksimal 150 karakter.',
        ];
    }
}
