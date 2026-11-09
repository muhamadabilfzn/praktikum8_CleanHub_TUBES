@extends('layouts.app')

@section('title', 'Manajemen Artikel')

@section('content')
<div class="container-fluid py-4 px-3 px-md-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-0">Manajemen Artikel & Edukasi</h5>
            <small class="text-muted">Kelola konten edukasi lingkungan untuk publik. Semua aksi berjalan tanpa refresh halaman.</small>
        </div>
        <button type="button" class="btn btn-ch btn-sm px-3" id="addBtn">
            <i class="fa-solid fa-plus me-1"></i>Tulis Artikel
        </button>
    </div>

    {{-- Pesan sukses / error hasil AJAX --}}
    <div id="alert-container"></div>

    <div class="panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="fw-semibold">Daftar Artikel <span class="badge bg-light text-dark border ms-1" id="total-count">0</span></span>
            <div class="input-group input-group-sm" style="max-width:280px">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" id="search" class="form-control" placeholder="Cari judul, kategori, penulis...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="artikel-table">
                <thead>
                    <tr>
                        <th>Gambar</th><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal tambah / edit --}}
<div class="modal fade" id="artikelModal" tabindex="-1" aria-labelledby="artikelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="artikelModalLabel">Tulis Artikel Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="artikelForm" novalidate>
                    @csrf
                    <input type="hidden" name="id" id="id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="judul">Judul Artikel</label>
                        <input type="text" id="judul" name="judul" class="form-control">
                        <div class="invalid-feedback" id="judul-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="kategori">Kategori</label>
                        <select id="kategori" name="kategori" class="form-select">
                            @foreach ($kategori as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="kategori-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="konten">Konten</label>
                        <textarea id="konten" name="konten" rows="8" class="form-control"></textarea>
                        <div class="invalid-feedback" id="konten-error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="thumbnail">
                            Gambar Thumbnail <span class="text-muted fw-normal">(opsional)</span>
                        </label>

                        {{-- Gambar saat ini (hanya saat edit): dapat dibuka kembali lewat tautan --}}
                        <div class="mb-2 d-none" id="thumb-current">
                            <a href="#" target="_blank" rel="noopener" id="thumb-link">
                                <img src="" alt="Thumbnail saat ini" id="thumb-img" class="rounded border" style="max-height:120px">
                            </a>
                            <div class="small mt-1">
                                <a href="#" target="_blank" rel="noopener" id="thumb-open"><i class="fa-solid fa-up-right-from-square me-1"></i>Buka gambar saat ini</a>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="hapus_thumbnail" id="hapus_thumbnail" value="1">
                                <label class="form-check-label small" for="hapus_thumbnail">Hapus gambar ini</label>
                            </div>
                        </div>

                        <input type="file" id="thumbnail" name="thumbnail" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="form-control">
                        <div class="invalid-feedback" id="thumbnail-error"></div>
                        <div class="form-text" id="thumbnail-help">JPG, JPEG, atau PNG. Maksimal 2 MB.</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" checked>
                        <label class="form-check-label" for="is_published">Publikasikan sekarang</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" style="border-radius:50px" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-ch px-4" id="saveBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <span class="submit-text">Simpan Artikel</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script>
$(function () {
    const urls = {
        login:   "{{ route('login') }}",
        data:    "{{ route('admin.artikel.data') }}",
        store:   "{{ route('admin.artikel.store') }}",
        edit:    "{{ route('admin.artikel.edit', ':id') }}",
        update:  "{{ route('admin.artikel.update', ':id') }}",
        toggle:  "{{ route('admin.artikel.toggle', ':id') }}",
        destroy: "{{ route('admin.artikel.destroy', ':id') }}",
    };

    let records = [];

    // Token CSRF dikirim di setiap request AJAX (tanpa ini akan terjadi error 419)
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // Sesi habis -> 401 dari middleware 'auth': arahkan kembali ke halaman login
    $(document).ajaxError(function (event, xhr) {
        if (xhr.status === 401) {
            window.location.href = urls.login;
        }
    });

    // Escape HTML agar data dari database tidak dieksekusi sebagai script (XSS)
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function showAlert(message, type = 'success') {
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark';
        const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fa-solid ${icon} me-2"></i><span>${escapeHtml(message)}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>`);
        $('#alert-container').html(alert);
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert[0]).close(), 4000);
    }

    function clearErrors() {
        $('#artikelForm .invalid-feedback').text('');
        $('#artikelForm .is-invalid').removeClass('is-invalid');
    }

    function messageRow(text) {
        return `<tr><td colspan="7" class="text-center text-muted py-4">${text}</td></tr>`;
    }

    function thumbnailCell(item) {
        if (!item.thumbnail_url) {
            return '<span class="text-muted small">-</span>';
        }
        // Gambar dapat dibuka kembali (tab baru) lewat tautan
        return `<a href="${escapeHtml(item.thumbnail_url)}" target="_blank" rel="noopener" title="Buka gambar">
                    <img src="${escapeHtml(item.thumbnail_url)}" alt="Thumbnail ${escapeHtml(item.judul)}"
                         class="rounded border" style="width:64px;height:48px;object-fit:cover">
                </a>`;
    }

    function renderTable() {
        const keyword = $('#search').val().trim().toLowerCase();
        const rows = records.filter(item =>
            [item.judul, item.kategori_label, item.penulis, item.is_published ? 'published' : 'draft']
                .some(value => (value || '').toLowerCase().includes(keyword))
        );

        $('#total-count').text(records.length);

        if (records.length === 0) {
            $('#artikel-table tbody').html(messageRow('Belum ada artikel. Klik <strong>Tulis Artikel</strong> untuk memulai.'));
            return;
        }
        if (rows.length === 0) {
            $('#artikel-table tbody').html(messageRow('Tidak ada artikel yang cocok dengan pencarian.'));
            return;
        }

        const html = rows.map(item => `
            <tr>
                <td>${thumbnailCell(item)}</td>
                <td class="fw-semibold small">${escapeHtml(item.judul)}</td>
                <td><span class="badge bg-info">${escapeHtml(item.kategori_label)}</span></td>
                <td class="small text-muted">${escapeHtml(item.penulis)}</td>
                <td>
                    <button type="button" data-id="${item.id}" class="toggle badge border-0 ${item.is_published ? 'bg-success' : 'bg-secondary'}"
                            title="Klik untuk ubah status">${item.is_published ? 'Published' : 'Draft'}</button>
                </td>
                <td class="small text-muted">${escapeHtml(item.tanggal)}</td>
                <td class="text-nowrap">
                    <button type="button" data-id="${item.id}" class="edit btn btn-sm" style="background:#fff3e0;color:#e65100" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button type="button" data-id="${item.id}" data-judul="${escapeHtml(item.judul)}" class="delete btn btn-sm" style="background:#ffebee;color:#c62828" title="Hapus">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`).join('');

        $('#artikel-table tbody').html(html);
    }

    // Ambil data dari server tanpa memuat ulang halaman
    function loadData() {
        $.ajax({
            url: urls.data,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                records = data;
                renderTable();
            },
            error: function (xhr) {
                if (xhr.status !== 401) {
                    $('#artikel-table tbody').html(messageRow('Gagal memuat data. Silakan refresh halaman.'));
                }
            }
        });
    }

    // Atur tampilan bagian gambar: tambah (kosong) atau edit (ada gambar lama)
    function setThumbnailMode(item = null) {
        $('#thumbnail').val('');
        $('#hapus_thumbnail').prop('checked', false);

        if (item && item.thumbnail_url) {
            $('#thumb-current').removeClass('d-none');
            $('#thumb-img').attr('src', item.thumbnail_url);
            $('#thumb-link, #thumb-open').attr('href', item.thumbnail_url);
            $('#thumbnail-help').text('JPG, JPEG, atau PNG. Maksimal 2 MB. Pilih file baru untuk mengganti gambar lama.');
        } else {
            $('#thumb-current').addClass('d-none');
            $('#thumbnail-help').text('JPG, JPEG, atau PNG. Maksimal 2 MB.');
        }
    }

    const artikelModal = new bootstrap.Modal(document.getElementById('artikelModal'));

    $('#artikelModal').on('shown.bs.modal', function () {
        $('#judul').trigger('focus');
    });

    $('#artikelModal').on('hidden.bs.modal', function () {
        $('#artikelForm').trigger('reset');
        $('#id').val('');
        clearErrors();
        setThumbnailMode();
    });

    loadData();
    $('#search').on('input', renderTable);

    // Tambah
    $('#addBtn').on('click', function () {
        $('#artikelModalLabel').text('Tulis Artikel Baru');
        $('#is_published').prop('checked', true);
        setThumbnailMode();
        artikelModal.show();
    });

    $('#artikelForm').on('submit', function (e) {
        e.preventDefault();
        $('#saveBtn').trigger('click');
    });

    // Simpan (create / update). FormData dipakai agar file ikut terkirim lewat AJAX.
    $('#saveBtn').on('click', function () {
        const btn = $(this);
        const id = $('#id').val();

        btn.prop('disabled', true);
        btn.find('.submit-text').text('Menyimpan...');
        btn.find('.spinner-border').removeClass('d-none');
        clearErrors();

        const formData = new FormData(document.getElementById('artikelForm'));
        if (id) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: id ? urls.update.replace(':id', id) : urls.store,
            type: 'POST',
            data: formData,
            processData: false,   // jangan ubah FormData menjadi query string
            contentType: false,   // biarkan browser membuat header multipart + boundary
            dataType: 'json',
            success: function (response) {
                artikelModal.hide();
                loadData();
                showAlert(response.success);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    // Error validasi: tampilkan di bawah masing-masing input
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#${key}-error`).text(messages[0]);
                    });
                    $('#artikelForm .is-invalid:first').trigger('focus');
                } else if (xhr.status === 419) {
                    artikelModal.hide();
                    showAlert('Sesi halaman sudah berakhir (token CSRF). Muat ulang halaman lalu coba lagi.', 'danger');
                } else if (xhr.status === 403) {
                    artikelModal.hide();
                    showAlert('Akses ditolak. Hanya admin yang boleh mengelola artikel.', 'danger');
                } else if (xhr.status !== 401) {
                    artikelModal.hide();
                    showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                }
            },
            complete: function () {
                btn.prop('disabled', false);
                btn.find('.submit-text').text('Simpan Artikel');
                btn.find('.spinner-border').addClass('d-none');
            }
        });
    });

    // Edit: ambil data satu artikel lalu isi form di modal
    $(document).on('click', '.edit', function () {
        const id = $(this).data('id');

        $.get(urls.edit.replace(':id', id), function (data) {
            $('#artikelModalLabel').text('Edit Artikel');
            $('#id').val(data.id);
            $('#judul').val(data.judul);
            $('#kategori').val(data.kategori);
            $('#konten').val(data.konten);
            $('#is_published').prop('checked', data.is_published);
            setThumbnailMode(data);
            artikelModal.show();
        }).fail(function (xhr) {
            if (xhr.status !== 401) {
                showAlert('Gagal memuat data artikel untuk diedit.', 'danger');
            }
        });
    });

    // Ubah status Published / Draft
    $(document).on('click', '.toggle', function () {
        $.post(urls.toggle.replace(':id', $(this).data('id')), function (response) {
            loadData();
            showAlert(response.success);
        }).fail(function (xhr) {
            if (xhr.status !== 401) {
                showAlert('Gagal mengubah status artikel.', 'danger');
            }
        });
    });

    // Hapus (file thumbnail ikut terhapus dari storage oleh server)
    $(document).on('click', '.delete', function () {
        const id = $(this).data('id');
        const judul = $(this).data('judul');

        if (!window.confirm(`Hapus artikel "${judul}"? Tindakan ini tidak bisa dibatalkan.`)) {
            return;
        }

        $.ajax({
            url: urls.destroy.replace(':id', id),
            type: 'DELETE',
            dataType: 'json',
            success: function (response) {
                loadData();
                showAlert(response.success);
            },
            error: function (xhr) {
                if (xhr.status !== 401) {
                    showAlert('Gagal menghapus artikel. Silakan coba lagi.', 'danger');
                }
            }
        });
    });
});
</script>
@endpush
