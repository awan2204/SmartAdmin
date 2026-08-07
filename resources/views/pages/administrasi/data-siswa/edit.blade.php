@extends('components.main')
@section('breadcrumbs')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="/administrasi/siswa">Siswa</a></li>
        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Edit</li>
    </ol>
    <h6 class="font-weight-bolder mb-0">Data Siswa</h6>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Data Siswa</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    {{-- Form ini dijamin langsung submit dan pindah halaman --}}
                    <form action="{{ url('/administrasi/siswa-update/' . $siswa->id) }}" class="row g-3 py-1 px-4" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="col-md-6">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control rounded-3" required value="{{ old('nis', $siswa->nis) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" class="form-control rounded-3" required value="{{ old('nisn', $siswa->nisn) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIK</label>
                            <input type="text" name="nik" class="form-control rounded-3" required value="{{ old('nik', $siswa->nik) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control rounded-3" required value="{{ old('nama', $siswa->nama) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control rounded-3" required value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control rounded-3" required value="{{ old('tanggal_lahir', $siswa->tanggal_lahir) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_kelamin" value="laki-laki" {{ $siswa->jenis_kelamin == 'laki-laki' ? 'checked' : '' }}>
                                <label class="form-check-label">Laki-laki</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_kelamin" value="perempuan" {{ $siswa->jenis_kelamin == 'perempuan' ? 'checked' : '' }}>
                                <label class="form-check-label">Perempuan</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agama</label>
                            <select class="form-select" name="agama">
                                @foreach (['islam', 'kristen', 'hindu', 'buddha', 'konghucu'] as $agama)
                                    <option value="{{ $agama }}" {{ $siswa->agama == $agama ? 'selected' : '' }}>{{ ucfirst($agama) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Ayah</label>
                            <input type="text" name="nama_ayah" class="form-control rounded-3" required value="{{ old('nama_ayah', $siswa->nama_ayah) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Ibu</label>
                            <input type="text" name="nama_ibu" class="form-control rounded-3" required value="{{ old('nama_ibu', $siswa->nama_ibu) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Masukkan Wali</label>
                            <input type="text" name="nama_wali" class="form-control rounded-3" required value="{{ old('nama_wali', $siswa->nama_wali) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="kelas">
                                @foreach ($kelas_list as $kelas)
                                    <option value="{{ $kelas->id }}" {{ $siswa->id_kelas == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- TIPE KJP --}}
                        <div class="col-md-6">
                            <label class="form-label">Tipe Siswa (KJP)</label>
                            <select class="form-select" name="is_kjp" required>
                                <option value="0" {{ (int)$siswa->is_kjp === 0 ? 'selected' : '' }}>Non KJP</option>
                                <option value="1" {{ (int)$siswa->is_kjp === 1 ? 'selected' : '' }}>Penerima KJP</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">No Telepon</label>
                            <input type="text" name="no_telp" class="form-control rounded-3" required value="{{ old('no_telp', $siswa->no_telp) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                @foreach ($status_siswa as $status)
                                    <option value="{{ $status }}" {{ $siswa->status == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input class="form-control" name="foto" type="file">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control rounded-3" required>{{ old('alamat', $siswa->alamat) }}</textarea>
                        </div>

                        <div class="text-right card-footer">
                            <button type="submit" class="btn btn-primary" style="float:right;">Simpan</button>
                            <a href="/administrasi/siswa" class="btn btn-danger" style="float: right; margin-right:10px">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection