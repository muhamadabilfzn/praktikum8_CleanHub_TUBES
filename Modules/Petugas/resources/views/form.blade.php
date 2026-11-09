@extends('layouts.app')

@section('title', isset($editOfficer) ? 'Edit Petugas' : 'Tambah Petugas')

@section('content')
<div class="container py-4" style="max-width:820px">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">{{ isset($editOfficer) ? 'Edit Petugas' : 'Tambah Petugas Baru' }}</h5>
        <a href="{{ route('admin.petugas.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST"
                  action="{{ isset($editOfficer) ? route('admin.petugas.update', $editOfficer->id) : route('admin.petugas.store') }}">
                @csrf
                @isset($editOfficer)
                    @method('PUT')
                @endisset

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap"
                               value="{{ old('nama_lengkap', $editOfficer->user->nama_lengkap ?? '') }}"
                               class="form-control @error('nama_lengkap') is-invalid @enderror">
                        @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $editOfficer->user->email ?? '') }}"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @empty($editOfficer)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="username">Username</label>
                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                   class="form-control @error('username') is-invalid @enderror">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endempty

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="no_hp">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $editOfficer->user->no_hp ?? '') }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="password">
                            Password
                            @isset($editOfficer)<span class="text-muted fw-normal">(kosongkan jika tidak diubah)</span>@endisset
                        </label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="area_kerja">Area Kerja</label>
                        <input type="text" id="area_kerja" name="area_kerja" value="{{ old('area_kerja', $editOfficer->area_kerja ?? '') }}"
                               class="form-control" placeholder="Contoh: Arcamanik - Cisaranten Endah">
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-ch px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i>{{ isset($editOfficer) ? 'Update' : 'Simpan' }}
                    </button>
                    <a href="{{ route('admin.petugas.index') }}" class="btn btn-outline-secondary" style="border-radius:50px">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
