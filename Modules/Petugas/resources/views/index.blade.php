@extends('layouts.app')

@section('title', 'Manajemen Petugas')

@section('content')
<div class="container-fluid py-4 px-3 px-md-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Manajemen Petugas</h5>
            <small class="text-muted">Kelola petugas kebersihan dan area kerja mereka</small>
        </div>
        <a href="{{ route('admin.petugas.create') }}" class="btn btn-ch btn-sm px-3">
            <i class="fa-solid fa-user-plus me-1"></i>Tambah Petugas
        </a>
    </div>

    <div class="row g-3">
        @forelse ($petugas as $p)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar avatar-lg">{{ strtoupper(substr($p->user->nama_lengkap, 0, 1)) }}</span>
                            <div>
                                <div class="fw-bold">{{ $p->user->nama_lengkap }}</div>
                                <span class="badge {{ $p->status === 'aktif' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($p->status) }}</span>
                            </div>
                        </div>
                        <div class="small text-muted mb-1"><i class="fa-solid fa-envelope me-2"></i>{{ $p->user->email }}</div>
                        <div class="small text-muted mb-1"><i class="fa-solid fa-phone me-2"></i>{{ $p->user->no_hp ?? '-' }}</div>
                        <div class="small text-muted mb-3"><i class="fa-solid fa-map-pin me-2"></i>{{ $p->area_kerja ?? 'Belum diset' }}</div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-bold text-success">Rp {{ number_format($p->saldo, 0, ',', '.') }}</div>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.petugas.edit', $p->id) }}" class="btn btn-sm" style="background:#fff3e0;color:#e65100" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.petugas.destroy', $p->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus petugas ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty-state icon="fa-hard-hat" message="Belum ada petugas terdaftar." />
            </div>
        @endforelse
    </div>
</div>
@endsection
