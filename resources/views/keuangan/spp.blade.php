@extends('components.main')

@section('content')
<!-- CSS MODAL LEBIH LEBAR & STYLING BADGE & SELECT2 CUSTOM -->
<style>
    .modal-backdrop { z-index: 1050 !important; }
    .modal { z-index: 1060 !important; }

    .modal-dialog {
        max-width: 650px !important;
        width: 90% !important;
        margin: 2rem auto !important;
    }

    .modal-content {
        max-height: 85vh !important;
        border: none !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
    }

    .modal-header {
        position: sticky !important;
        top: 0;
        z-index: 10;
    }

    .modal-body {
        max-height: calc(85vh - 130px) !important;
        overflow-y: auto !important;
    }

    .modal-footer {
        position: sticky !important;
        bottom: 0;
        z-index: 10;
        background-color: #f8f9fa !important;
    }

    .badge-kjp {
        background-color: #008080 !important;
        color: #fff !important;
        font-weight: bold;
    }

    .badge-non-kjp {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    /* CUSTOM WARNA TEKS KJP / NON KJP PADA SELECT2 DROPDOWN */
    .select2-container--default .select2-results__option[data-kjp="1"] {
        color: #0d6efd !important; /* Warna Biru Terang / Hijau untuk KJP */
        font-weight: bold;
    }
    .select2-container--default .select2-results__option[data-kjp="0"] {
        color: #dc3545 !important; /* Warna Merah untuk Non KJP */
    }
</style>

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
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title font-weight-bold m-0"><i class="fas fa-list mr-1"></i> Transaksi Pembayaran SPP Siswa</h3>
                
                <div class="card-tools d-flex align-items-center gap-2">
                    <!-- FILTER STATUS KJP -->
                    <form method="GET" action="{{ route('keuangan.spp') }}" class="d-inline-block mr-2">
                        <select name="kjp_filter" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">-- Filter Semua KJP --</option>
                            <option value="1" {{ isset($filterKjp) && $filterKjp == '1' ? 'selected' : '' }}>Penerima KJP</option>
                            <option value="0" {{ isset($filterKjp) && $filterKjp == '0' ? 'selected' : '' }}>NON KJP</option>
                        </select>
                    </form>

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
                                <th>Tipe</th>
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
                                    <td>{{ $item->siswa->kelas->nama_kelas ?? $item->siswa->kelas ?? '-' }}</td>
                                    
                                    <!-- BADGE PENANDA KJP / NON KJP -->
                                    <td>
                                        @if($item->is_kjp)
                                            <span class="badge badge-kjp px-2 py-1"><i class="fas fa-id-card mr-1"></i> KJP</span>
                                        @else
                                            <span class="badge badge-non-kjp px-2 py-1">NON KJP</span>
                                        @endif
                                    </td>

                                    <td>{{ $item->bulan }}</td>
                                    <td>
                                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                                        @if($item->is_kjp && $item->nominal == 30000)
                                            <br><small class="text-muted font-italic">(Rp 30.000 Cash + Rp 170.000 Subsidi)</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $item->metode_pembayaran }}</span></td>
                                    <td>
                                        @if($item->status == 'PAID')
                                            <span class="badge bg-success">PAID</span>
                                        @else
                                            <span class="badge bg-warning text-dark">UNPAID</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- TOMBOL EDIT -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditSpp{{ $item->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- TOMBOL DELETE -->
                                        <form action="{{ route('keuangan.spp.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- MODAL EDIT SPP -->
                                <div class="modal fade" id="modalEditSpp{{ $item->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form action="{{ route('keuangan.spp.update', $item->id) }}" method="POST" class="form-spp w-100">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-dark py-3">
                                                    <h5 class="modal-title font-weight-bold">
                                                        <i class="fas fa-edit mr-1"></i> Edit Transaksi SPP
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Pilih / Cari Siswa</label>
                                                        <select name="siswa_id" class="form-control select2-modal" required>
                                                            @foreach(\App\Models\Siswa::orderBy('nama', 'asc')->get() as $s)
                                                                <option value="{{ $s->id }}" data-kjp="{{ $s->is_kjp ? 1 : 0 }}" {{ $item->siswa_id == $s->id ? 'selected' : '' }}>
                                                                    {{ $s->nisn }} - {{ $s->nama }} ({{ $s->is_kjp ? 'KJP' : 'NON KJP' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Tipe Siswa (KJP / Non KJP)</label>
                                                        <select name="is_kjp" class="form-control" required>
                                                            <option value="1" {{ (int)$item->is_kjp === 1 ? 'selected' : '' }}>Penerima KJP</option>
                                                            <option value="0" {{ (int)$item->is_kjp === 0 ? 'selected' : '' }}>NON KJP</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Tahun Ajaran</label>
                                                        <input type="text" name="tahun_ajaran" class="form-control" value="{{ $item->tahun_ajaran }}" required>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Bulan</label>
                                                        <select name="bulan" class="form-control" required>
                                                            @foreach(['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'] as $b)
                                                                <option value="{{ $b }}" {{ $item->bulan == $b ? 'selected' : '' }}>{{ $b }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Nominal (Rp)</label>
                                                        <input type="text" 
                                                               name="nominal" 
                                                               class="form-control input-nominal" 
                                                               value="{{ number_format($item->nominal, 0, ',', '.') }}" 
                                                               oninput="formatRupiahInput(this)" 
                                                               required>
                                                        <small class="text-muted d-block mt-1">*Min Rp 10.000 - Max Rp 10.000.000</small>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Tanggal Bayar</label>
                                                        <input type="date" name="tanggal_bayar" class="form-control" value="{{ \Carbon\Carbon::parse($item->tanggal_bayar ?? $item->created_at)->format('Y-m-d') }}" required>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Metode Pembayaran</label>
                                                        <select name="metode_pembayaran" class="form-control" required>
                                                            <option value="CASH" {{ $item->metode_pembayaran == 'CASH' ? 'selected' : '' }}>CASH</option>
                                                            <option value="TRANSFER" {{ $item->metode_pembayaran == 'TRANSFER' ? 'selected' : '' }}>TRANSFER</option>
                                                            <option value="DEBET" {{ $item->metode_pembayaran == 'DEBET' ? 'selected' : '' }}>DEBET</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Status Pembayaran</label>
                                                        <select name="status" class="form-control" required>
                                                            <option value="PAID" {{ $item->status == 'PAID' ? 'selected' : '' }}>PAID (Lunas)</option>
                                                            <option value="UNPAID" {{ $item->status == 'UNPAID' ? 'selected' : '' }}>UNPAID (Belum Lunas)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer py-3">
                                                    <button type="button" class="btn btn-secondary font-weight-bold" data-bs-dismiss="modal">BATAL</button>
                                                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i> SIMPAN PERUBAHAN</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Belum ada data transaksi SPP.</td>
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
<div class="modal fade" id="modalTambahSpp" tabindex="-1" aria-labelledby="modalTambahSppLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('keuangan.spp.store') }}" method="POST" class="form-spp w-100">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title font-weight-bold" id="modalTambahSppLabel">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Transaksi SPP Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Pilih / Cari Siswa</label>
                        <select name="siswa_id" id="selectSiswaTambah" class="form-control select2-modal" onchange="handleSiswaChange(this)" required>
                            <option value="">-- Pilih Siswa --</option>
                            @foreach(\App\Models\Siswa::orderBy('nama', 'asc')->get() as $s)
                                <option value="{{ $s->id }}" data-kjp="{{ $s->is_kjp ? 1 : 0 }}">
                                    {{ $s->nisn }} - {{ $s->nama }} ({{ $s->is_kjp ? 'KJP' : 'NON KJP' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Tipe Siswa (KJP / Non KJP)</label>
                        <select name="is_kjp" id="inputTipeKjpTambah" class="form-control" required>
                            <option value="0">NON KJP</option>
                            <option value="1">Penerima KJP</option>
                        </select>
                    </div>

                    <!-- Alert Keterangan Subsidi untuk Siswa KJP -->
                    <div id="infoKjpAlert" class="alert alert-info py-2 mb-3" style="display: none; font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i> <strong>Siswa KJP:</strong> Membayar <strong>Rp 30.000 Cash</strong> + Subsidi KJP <strong>Rp 170.000</strong> (Status otomatis <strong>PAID</strong>).
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Bulan</label>
                        <select name="bulan" class="form-control" required>
                            <option value="">-- Pilih Bulan --</option>
                            @foreach(['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'] as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Nominal (Rp)</label>
                        <input type="text" 
                               name="nominal" 
                               id="inputNominalTambah" 
                               class="form-control input-nominal" 
                               placeholder="Contoh: 200.000" 
                               oninput="formatRupiahInput(this)" 
                               required>
                        <small class="text-muted d-block mt-1">*Min Rp 10.000 - Max Rp 10.000.000</small>
                        
                        <!-- Tombol Pilihan Nominal Cepat -->
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(25000)">25RB</button>
                            <button type="button" class="btn btn-warning text-dark font-weight-bold btn-sm px-2 py-1 text-xs" onclick="setNominal(30000)">30RB</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(50000)">50RB</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(75000)">75RB</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(100000)">100RB</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(150000)">150RB</button>
                            <button type="button" class="btn btn-info text-white font-weight-bold btn-sm px-2 py-1 text-xs" onclick="setNominal(170000)">170RB</button>
                            <button type="button" class="btn btn-success font-weight-bold btn-sm px-2 py-1 text-xs" onclick="setNominal(200000)">200RB (1 BLN)</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 text-xs" onclick="setNominal(400000)">400RB (2 BLN)</button>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Tanggal Bayar</label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-control" required>
                            <option value="CASH">CASH</option>
                            <option value="TRANSFER">TRANSFER</option>
                            <option value="DEBET">DEBET</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Status Pembayaran</label>
                        <select name="status" id="inputStatusTambah" class="form-control" required>
                            <option value="PAID">PAID (Lunas)</option>
                            <option value="UNPAID">UNPAID (Belum Lunas)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-3">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i> SIMPAN TRANSAKSI</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tambahkan Library CSS & JS Select2 (Jika belum ada di layout utama) -->
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

<script>
    // Fungsi otomatis mendeteksi status KJP saat siswa dipilih di Modal Tambah
    function handleSiswaChange(selectElement) {
        let selectedOption = selectElement.options[selectElement.selectedIndex];
        let isKjp = selectedOption.getAttribute('data-kjp');
        let selectTipeKjp = document.getElementById('inputTipeKjpTambah');
        let inputNominal = document.getElementById('inputNominalTambah');
        let infoKjpAlert = document.getElementById('infoKjpAlert');
        let statusSelect = document.getElementById('inputStatusTambah');

        if (selectElement.value !== "") {
            selectTipeKjp.value = isKjp;
            
            if (isKjp == "1") {
                inputNominal.value = "30.000";
                infoKjpAlert.style.display = "block";
                statusSelect.value = "PAID";
            } else {
                inputNominal.value = "200.000";
                infoKjpAlert.style.display = "none";
                statusSelect.value = "PAID";
            }
        } else {
            selectTipeKjp.value = "0";
            inputNominal.value = "";
            infoKjpAlert.style.display = "none";
        }
    }

    function formatRupiahInput(e) {
        let raw = e.value.replace(/\D/g, "");
        if (raw !== "") {
            let num = parseInt(raw, 10);
            if (num > 10000000) {
                num = 10000000;
            }
            e.value = new Intl.NumberFormat('id-ID').format(num);
        } else {
            e.value = "";
        }
    }

    function setNominal(nilai) {
        let input = document.getElementById('inputNominalTambah');
        if(input) {
            input.value = new Intl.NumberFormat('id-ID').format(nilai);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            document.body.appendChild(modal);
        });

        // Inisialisasi Select2 pada Modal agar fitur pencarian dan kustomisasi warna jalan
        if (typeof jQuery !== 'undefined' && jQuery().select2) {
            $('.select2-modal').each(function() {
                $(this).select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $(this).closest('.modal'),
                    width: '100%',
                    templateResult: function(data) {
                        if (!data.id) {
                            return data.text;
                        }
                        var $element = $(data.element);
                        var isKjp = $element.attr('data-kjp');
                        
                        // Berikan warna secara dinamis pada item dropdown berdasarkan atribut data-kjp
                        var color = (isKjp == "1") ? "#0d6efd" : "#dc3545";
                        var $span = $('<span style="color: ' + color + '; font-weight: bold;"></span>');
                        $span.text(data.text);
                        return $span;
                    }
                });
            });
        }

        const forms = document.querySelectorAll('.form-spp');
        forms.forEach(function(form) {
            form.addEventListener('submit', function() {
                const nominalInput = form.querySelector('.input-nominal');
                if (nominalInput) {
                    nominalInput.value = nominalInput.value.replace(/\D/g, "");
                }
            });
        });
    });
</script>
@endsection