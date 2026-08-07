@extends('components.main')

@section('breadcrumbs')
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="#">Pembelajaran</a></li>
        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">E-Learning</li>
    </ol>
    <h6 class="font-weight-bolder mb-0">Manajemen E-Learning</h6>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            
            @if(session('success'))
                <div class="alert alert-success text-white font-weight-bold mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center px-3">
                        <h6 class="text-white text-capitalize m-0">Materi & Tugas Siswa</h6>
                        <button class="btn btn-light text-primary font-weight-bold btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#tambahMateriModal">
                            <i class="material-icons text-sm opacity-10">add</i> Tambah E-Learning
                        </button>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive pb-2 px-3">
                        <table id="example" class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">No</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Mapel</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Judul</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Kelas</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Tipe</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">File</th>
                                    <th class="text-center text-uppercase text-secondary text-xs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($materiList as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center fw-bold">{{ $item->mata_pelajaran }}</td>
                                        <td class="text-center">{{ $item->judul_materi }}</td>
                                        <td class="text-center">{{ $item->kelas->nama_kelas ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-gradient-{{ $item->tipe == 'materi' ? 'info' : 'warning' }}">
                                                {{ ucfirst($item->tipe) }}
                                            </span>
                                        </td>
                                       <td class="text-center">
                                        @if($item->file_attachment)
                                         <a href="{{ route('elearning.download', $item->id) }}" class="btn btn-sm btn-info">
                                             <i class="fa fa-download me-1"></i> Unduh
                                         </a>
                                                     @else
                                         <span class="text-xs text-muted">-</span>
                                            @endif
                                            </td>
                                        <td class="text-center">
                                            <form action="/administrasi/elearning/{{ $item->id }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus materi ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger font-weight-bold text-sm rounded-circle mb-0" title="Hapus">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Materi/Tugas --}}
    <div class="modal fade" id="tambahMateriModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Tambah Materi / Tugas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/administrasi/elearning" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Mata Pelajaran</label>
                                <input type="text" name="mata_pelajaran" class="form-control border px-2" placeholder="Cth: Matematika" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Target Kelas</label>
                                <select class="form-select border px-2" name="kelas_id" required>
                                    <option value="" selected disabled>-- Pilih Kelas --</option>
                                    @foreach($kelases as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Judul E-Learning</label>
                                <input type="text" name="judul_materi" class="form-control border px-2" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Tipe Modul</label>
                                <select class="form-select border px-2" name="tipe" required>
                                    <option value="materi">Materi Pembelajaran</option>
                                    <option value="tugas">Tugas Siswa</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label font-weight-bold">Deskripsi / Instruksi</label>
                                <textarea name="deskripsi" class="form-control border px-2" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">File Attachment (PDF, PPT, DOCX)</label>
                                <input type="file" name="file_attachment" class="form-control border">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Tenggat Waktu (Khusus Tugas)</label>
                                <input type="datetime-local" name="tenggat_waktu" class="form-control border px-2">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Materi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection