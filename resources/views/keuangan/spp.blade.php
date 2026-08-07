@extends('components.main')

@section('content')
<!-- Header & Breadcrumb -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 font-weight-bold text-dark">Manajemen Pembayaran SPP</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Keuangan</a></li>
                    <li class="breadcrumb-item active">Pembayaran SPP</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        {{-- Pesan Notifikasi Sukses --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- BAGIAN 1: CARD IMPORT EXCEL --}}
        <div class="card card-outline card-info mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-file-excel mr-1 text-success"></i> Import Data SPP dari Excel</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('keuangan.spp.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Tahun Ajaran</label>
                                <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label>Pilih File Excel SPP (.xlsx / .xls)</label>
                                <input type="file" name="file_excel" class="form-control" required accept=".xlsx, .xls">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-info"><i class="fas fa-upload mr-1"></i> Import Sekarang</button>
                </form>
            </div>
        </div>

        {{-- BAGIAN 2: CARD TABEL DATA TRANSAKSI SPP --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Transaksi Pembayaran SPP Siswa</h3>
                <div class="card-tools">
                    <a href="{{ route('keuangan.laporan-spp') }}" class="btn btn-info btn-sm mr-2">
                        <i class="fas fa-file-alt mr-1"></i> Laporan SPP
                    </a>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSpp">
                        <i class="fas fa-plus mr-1"></i> Transaksi Baru
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Bulan</th>
                                <th>Jumlah</th>
                                <th>Tanggal & Waktu Bayar</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sppPayments as $index => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->siswa->nisn ?? '-' }}</td>
                                    <td>{{ $item->siswa->nama ?? 'Siswa Tidak Ditemukan' }}</td>
                                    <td>{{ $item->siswa->kelas ?? '-' }}</td>
                                    <td>{{ $item->bulan }}</td>
                                    <td>Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                    
                                    {{-- MENGGUNAKAN CREATED_AT AGAR WAKTU / JAM MUNCUL AKURAT --}}
                                    <td>
                                        {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') : '-' }}
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">{{ $item->metode_pembayaran }}</span>
                                    </td>
                                    <td>
                                        @if($item->status == 'PAID')
                                            <span class="badge bg-success">PAID</span>
                                        @else
                                            <span class="badge bg-warning text-dark">UNPAID</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">Belum ada data transaksi SPP.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- MODAL TAMBAH TRANSAKSI SPP BARU -->
<div class="modal fade" id="modalTambahSpp" tabindex="-1" aria-labelledby="modalTambahSppLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('keuangan.spp.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTambahSppLabel"><i class="fas fa-plus-circle mr-1"></i> Tambah Transaksi SPP Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Pilih / Cari Siswa</label>
                        <select name="siswa_id" class="form-control" required>
                            <option value="">-- Pilih Siswa --</option>
                            @foreach(\App\Models\Siswa::orderBy('nama', 'asc')->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->nisn }} - {{ $s->nama }} (Kelas: {{ $s->kelas }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Bulan</label>
                        <select name="bulan" class="form-control" required>
                            <option value="">-- Pilih Bulan --</option>
                            @foreach(['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- INPUT NOMINAL DENGAN TOMBOL OPSI CEPAT --}}
                    <div class="form-group mb-3">
                        <label>Nominal (Rp)</label>
                        <input type="number" name="nominal" id="inputNominal" class="form-control" placeholder="Contoh: 500000" required>
                        
                        <!-- Tombol Pilihan Nominal Cepat -->
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(25000)">25rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(50000)">50rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(75000)">75rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(100000)">100rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(150000)">150rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(200000)">200rb</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(250000)">250rb (1 Bln)</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(500000)">500rb (2 Bln)</button>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Tanggal Bayar</label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-control" required>
                            <option value="CASH">CASH</option>
                            <option value="TRANSFER">TRANSFER</option>
                            <option value="DEBET">DEBET</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Status Pembayaran</label>
                        <select name="status" class="form-control" required>
                            <option value="PAID">PAID (Lunas)</option>
                            <option value="UNPAID">UNPAID (Belum Lunas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Transaksi</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Skrip JavaScript untuk Tombol Nominal Cepat --}}
<script>
    function setNominal(nilai) {
        document.getElementById('inputNominal').value = nilai;
    }
</script>
@endsection