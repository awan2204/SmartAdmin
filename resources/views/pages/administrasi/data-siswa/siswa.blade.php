@extends('components.main')

@section('breadcrumbs')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="/data-siswa">Siswa</a></li>
        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Data Siswa</li>
    </ol>
    <h6 class="font-weight-bolder mb-0">Data Siswa</h6>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Data Siswa</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive pb-2 px-3">
                        <div class="mb-3">
                            <a href="/administrasi/siswa-tambah" class="btn btn-primary font-weight-bold text-xs">
                                <i class="material-icons opacity-10">add</i> Tambah
                            </a>
                            <a href="/administrasi/siswa-keluar" class="btn btn-danger font-weight-bold text-xs">
                                Siswa Keluar
                            </a>
                            <a href="/usersiswa/export" class="btn btn-success font-weight-bold text-xs">
                                Export Data siswa
                            </a>
                        </div>

                        {{-- Filter Search --}}
                        <form action="/administrasi/siswa" method="get">
                            <div class="my-3 d-flex gap-2 align-items-center justify-content-start">
                                <select class="form-select form-select-sm" name="kelas" style="text-transform: capitalize; width: 200px">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($kelas as $k)
                                        <option value="{{ $k->id }}" {{ request('kelas') == $k->id ? 'selected' : '' }}>
                                            {{ $k->nama_kelas }}
                                        </option>
                                    @endforeach
                                </select>

                                <select class="form-select form-select-sm" name="status" style="text-transform: capitalize; width: 200px">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="bukan pindahan" {{ request('status') == 'bukan pindahan' ? 'selected' : '' }}>Bukan Pindahan</option>
                                    <option value="pindahan" {{ request('status') == 'pindahan' ? 'selected' : '' }}>Pindahan</option>
                                </select>

                                <button type="submit" class="btn btn-outline-primary btn-sm mb-0">Cari</button>
                            </div>
                        </form>

                        {{-- Table Data Siswa --}}
<table id="example" class="table align-items-center mb-0">
    <thead>
        <tr>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">No</th>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">NIS</th>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Nama Lengkap</th>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Kelas</th>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Status</th>
            <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($siswas as $siswa)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $siswa->nis }}</td>
                <td class="text-center">{{ $siswa->nama }}</td>
                <td class="text-center">{{ $siswa->kelas->nama_kelas ?? '-' }}</td>
                <td class="text-center">{{ $siswa->status }}</td>
                <td class="text-center d-flex justify-content-center gap-2">
                    {{-- Detail Button --}}
                    <button type="button" 
                        class="btn btn-info font-weight-bold text-sm rounded-circle mb-0"
                        data-bs-toggle="modal" 
                        data-bs-target="#detail-modal"
                        data-bs-placement="bottom" 
                        title="Detail" 
                        data-nama-siswa="{{ $siswa->nama }}"
                        data-nis="{{ $siswa->nis }}" 
                        data-nisn="{{ $siswa->nisn }}"
                        data-jenis-kelamin="{{ $siswa->jenis_kelamin }}"
                        data-kelas="{{ $siswa->kelas->nama_kelas ?? '-' }}" 
                        data-nik="{{ $siswa->nik }}"
                        data-tempat-lahir="{{ $siswa->tempat_lahir }}"
                        data-tanggal-lahir="{{ \Carbon\Carbon::parse($siswa->tanggal_lahir)->format('d/M/Y') }}"
                        data-nama-wali="{{ $siswa->nama_wali }}" 
                        data-no-telp="{{ $siswa->no_telp }}"
                        data-agama="{{ $siswa->agama }}" 
                        data-alamat="{{ $siswa->alamat }}"
                        data-status-siswa="{{ $siswa->status }}"
                        data-sekolah-asal="{{ $siswa->detail_siswa->asal_sekolah ?? '-' }}" 
                        data-tahun-masuk="{{ isset($siswa->detail_siswa->tanggal_masuk) ? \Carbon\Carbon::parse($siswa->detail_siswa->tanggal_masuk)->format('d/M/Y') : '-' }}"
                        data-foto="{{ asset('storage/murid/img/' . $siswa->foto) }}"
                        onclick="showModalDetail(this)">
                        <i class="fa fa-eye"></i>
                    </button>

                    {{-- Edit Button --}}
                    <a href="/administrasi/siswa-update/{{ $siswa->id }}"
                        class="btn btn-warning font-weight-bold text-sm rounded-circle mb-0"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit">
                        <i class="fa fa-edit"></i>
                    </a>

                    {{-- Leave/Trash Button --}}
                    <button type="button" 
                        class="btn btn-danger font-weight-bold text-sm rounded-circle mb-0"
                        data-bs-toggle="modal" 
                        data-bs-target="#leave-modal"
                        data-bs-placement="bottom" 
                        title="Keluarkan Siswa"
                        data-id-siswa="{{ $siswa->id }}" 
                        data-nama-siswa="{{ $siswa->nama }}"
                        data-nis="{{ $siswa->nis }}" 
                        data-nisn="{{ $siswa->nisn }}"
                        data-kelas="{{ $siswa->kelas->nama_kelas ?? '-' }}"
                        onclick="showModalLeave(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

    {{-- Modal Detail Siswa --}}
    <div class="modal fade" id="detail-modal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="detailModalLabel">Detail Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="foto">
                                <img id="detail-foto" src="" alt="Foto Siswa" width="100%" class="img-fluid rounded">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Nama <span class="float-end">:</span></div>
                                        <div id="detail-nama-siswa" class="col-md-7 text-uppercase"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">NISN <span class="float-end">:</span></div>
                                        <div id="detail-nisn" class="col-md-7"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">NIS <span class="float-end">:</span></div>
                                        <div id="detail-nis" class="col-md-7"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">NIK <span class="float-end">:</span></div>
                                        <div id="detail-nik" class="col-md-7"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Kelas <span class="float-end">:</span></div>
                                        <div id="detail-kelas" class="col-md-7 text-uppercase"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Jenis Kelamin <span class="float-end">:</span></div>
                                        <div id="detail-jenis-kelamin" class="col-md-7 text-capitalize"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Tempat, Tgl Lahir <span class="float-end">:</span></div>
                                        <div id="detail-ttl" class="col-md-7 text-uppercase"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Nama Wali <span class="float-end">:</span></div>
                                        <div id="detail-wali" class="col-md-7 text-capitalize"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Status <span class="float-end">:</span></div>
                                        <div id="detail-status-siswa" class="col-md-7 text-capitalize"></div>
                                    </div>
                                </li>
                                <li class="list-group-item mutasi-detail-item" style="display: none">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Sekolah Asal <span class="float-end">:</span></div>
                                        <div id="detail-sekolah-asal" class="col-md-7 text-uppercase"></div>
                                    </div>
                                </li>
                                <li class="list-group-item mutasi-detail-item" style="display: none">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Tahun Masuk <span class="float-end">:</span></div>
                                        <div id="detail-tahun-masuk" class="col-md-7"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">No Telepon <span class="float-end">:</span></div>
                                        <div id="detail-no-telp" class="col-md-7"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Agama <span class="float-end">:</span></div>
                                        <div id="detail-agama" class="col-md-7 text-capitalize"></div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-md-5 fw-bold">Alamat <span class="float-end">:</span></div>
                                        <div id="detail-alamat" class="col-md-7 text-capitalize"></div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Siswa Keluar --}}
    <div class="modal fade" id="leave-modal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="leaveModalLabel">Siswa Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="siswaForm" class="py-1 px-3" method="post" action="">
                    @method('PUT')
                    @csrf
                    <div class="modal-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-5 fw-bold">Nama <span class="float-end">:</span></div>
                                    <div id="leave-nama-siswa" class="col-md-7 text-uppercase"></div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-5 fw-bold">NISN <span class="float-end">:</span></div>
                                    <div id="leave-nisn" class="col-md-7"></div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-5 fw-bold">NIS <span class="float-end">:</span></div>
                                    <div id="leave-nis" class="col-md-7"></div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row">
                                    <div class="col-md-5 fw-bold">Kelas <span class="float-end">:</span></div>
                                    <div id="leave-kelas" class="col-md-7 text-uppercase"></div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-md-5 fw-bold">Status Keluar <span class="float-end">:</span></div>
                                    <div class="col-md-7">
                                        <select class="form-select rounded-3 text-sm" name="status" required>
                                            <option value="" selected disabled>-- Pilih Status --</option>
                                            <option value="lulus">Lulus</option>
                                            <option value="mutasi">Mutasi</option>
                                        </select>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-md-5 fw-bold">Tanggal Keluar <span class="float-end">:</span></div>
                                    <div class="col-md-7">
                                        <input type="date" class="form-control text-sm" name="tanggal_keluar" required>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function showModalDetail(element) {
        let el = $(element);
        
        $('#detail-foto').attr('src', el.data('foto'));
        $('#detail-nama-siswa').text(el.data('nama-siswa'));
        $('#detail-nisn').text(el.data('nisn'));
        $('#detail-nis').text(el.data('nis'));
        $('#detail-nik').text(el.data('nik'));
        $('#detail-kelas').text(el.data('kelas'));
        $('#detail-jenis-kelamin').text(el.data('jenis-kelamin'));
        $('#detail-ttl').text(el.data('tempat-lahir') + ', ' + el.data('tanggal-lahir'));
        $('#detail-wali').text(el.data('nama-wali'));
        $('#detail-status-siswa').text(el.data('status-siswa'));
        $('#detail-no-telp').text(el.data('no-telp'));
        $('#detail-agama').text(el.data('agama'));
        $('#detail-alamat').text(el.data('alamat'));

        if (el.data('status-siswa') === 'mutasi' || el.data('status-siswa') === 'pindahan') {
            $('.mutasi-detail-item').show();
            $('#detail-sekolah-asal').text(el.data('sekolah-asal'));
            $('#detail-tahun-masuk').text(el.data('tahun-masuk'));
        } else {
            $('.mutasi-detail-item').hide();
        }
    }

    function showModalLeave(element) {
        let el = $(element);
        let id = el.data('id-siswa');

        // Mengatur action form secara dinamis berdasarkan ID siswa
        $('#siswaForm').attr('action', '/administrasi/siswa-keluar/' + id);

        $('#leave-nama-siswa').text(el.data('nama-siswa'));
        $('#leave-nisn').text(el.data('nisn'));
        $('#leave-nis').text(el.data('nis'));
        $('#leave-kelas').text(el.data('kelas'));
    }
</script>
@endpush