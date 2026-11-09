<?php

namespace Modules\Petugas\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Petugas\Http\Requests\StorePetugasRequest;
use Modules\Petugas\Http\Requests\UpdatePetugasRequest;

/**
 * CRUD synchronous: setiap aksi mengirim form biasa lalu halaman dimuat ulang (redirect).
 */
class PetugasController extends Controller
{
    public function index(): View
    {
        return view('petugas::index', [
            'petugas' => Officer::with('user')->whereHas('user', fn ($q) => $q->where('status', 'aktif'))->get(),
        ]);
    }

    public function create(): View
    {
        return view('petugas::form');
    }

    public function store(StorePetugasRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'role_id'      => 2, // petugas
                'nama_lengkap' => $data['nama_lengkap'],
                'username'     => $data['username'],
                'email'        => $data['email'],
                'password'     => Hash::make($data['password']),
                'no_hp'        => $data['no_hp'] ?? null,
                'status'       => 'aktif',
            ]);

            Officer::create([
                'user_id'    => $user->id,
                'area_kerja' => $data['area_kerja'] ?? null,
                'status'     => 'aktif',
            ]);
        });

        return redirect()->route('admin.petugas.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(Officer $officer): View
    {
        return view('petugas::form', ['editOfficer' => $officer->load('user')]);
    }

    public function update(UpdatePetugasRequest $request, Officer $officer): RedirectResponse
    {
        $data = $request->validated();

        $userData = [
            'nama_lengkap' => $data['nama_lengkap'],
            'email'        => $data['email'],
            'no_hp'        => $data['no_hp'] ?? null,
        ];
        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($officer, $userData, $data) {
            $officer->user->update($userData);
            $officer->update(['area_kerja' => $data['area_kerja'] ?? null]);
        });

        return redirect()->route('admin.petugas.index')->with('success', 'Data petugas berhasil diupdate.');
    }

    public function destroy(Officer $officer): RedirectResponse
    {
        try {
            DB::transaction(function () use ($officer) {
                $officer->delete();
                $officer->user()->update(['status' => 'nonaktif']);
            });
        } catch (QueryException $e) {
            return redirect()->route('admin.petugas.index')
                ->with('error', 'Petugas tidak dapat dihapus karena masih memiliki riwayat penugasan.');
        }

        return redirect()->route('admin.petugas.index')->with('success', 'Petugas berhasil dihapus.');
    }
}
