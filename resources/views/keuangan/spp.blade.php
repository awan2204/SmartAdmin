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

    .select2-container--default .select2-results__option[data-kjp="1"] {
        color: #0d6efd !important;
        font-weight: bold;
    }
    .select2-container--default .select2-results__option[data-kjp="0"] {
        color: #dc3545 !important;
    }

    /* Styling Card Total Harian Modern dengan Huruf & Angka Extra Besar */
    .card-stat-modern {
        background: linear-gradient(135deg, #02b2af 0%, #008080 100%);
        border-radius: 8px;
        color: white;
        box-shadow: 0 4px 15px rgba(0, 128, 128, 0.3);
        transition: transform 0.2s ease;
    }
    .card-stat-modern:hover {
        transform: translateY(-2px);
    }

    /* Styling Jadwal Sholat Minimalis & Elegan */
    .sholat-item {
        background: rgba(0, 128, 128, 0.05);
        border-radius: 6px;
        padding: 6px 6px;
        text-align: center;
        border: 1px solid rgba(0, 128, 128, 0.15);
        transition: all 0.3s ease;
        position: relative;
    }
    .sholat-time {
        font-weight: 800;
        color: #008080;
        font-size: 13px;
    }
    .sholat-name {
        font-size: 11px;
        color: #555;
        font-weight: bold;
        text-transform: uppercase;
    }

    /* Efek Kedip / Pulse Super Hidup untuk Waktu Sholat Terdekat */
    @keyframes blinkingCard {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 128, 128, 0.7); background-color: rgba(0, 128, 128, 0.1); }
        50% { transform: scale(1.04); box-shadow: 0 0 15px 5px rgba(0, 128, 128, 0.4); background-color: rgba(0, 128, 128, 0.25); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 128, 128, 0.7); background-color: rgba(0, 128, 128, 0.1); }
    }
    .sholat-active {
        animation: blinkingCard 1.2s infinite ease-in-out;
        border: 2px solid #008080 !important;
        background: rgba(0, 128, 128, 0.15) !important;
    }
    .badge-terdekat {
        font-size: 8px;
        background: #008080;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        position: absolute;
        top: -9px;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        letter-spacing: 0.5px;
    }
</style>

<!-- Header & Breadcrumb -->
<div class="content-header mb-2">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="m-0 font-weight-bold text-dark" style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                    <i class="fas fa-wallet text-teal mr-2"></i> Manajemen Pembayaran SPP
                </h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right bg-transparent p-0 m-0">
                    <li class="breadcrumb-item"><a href="#" class="text-teal">Keuangan</a></li>
                    <li class="breadcrumb-item active text-muted">Pembayaran SPP</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        {{-- Pesan Notifikasi Sukses / Error --}}
        @if(session('success'))
            <div class="alert alert-dismissible fade show shadow-sm text-white font-weight-bold" style="background-color: #198754 !important; border: none;" role="alert">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-dismissible fade show shadow-sm text-white font-weight-bold" style="background-color: #dc3545 !important; border: none;" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> {!! session('error') !!}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- BAGIAN 1: EXPORT EXCEL & CARD JADWAL SHOLAT LIVE HIDUP BERKEDIP --}}
        <div class="row mb-3">
            <!-- Kolom Kiri: Export Excel -->
            <div class="col-lg-5 col-md-6 mb-3 mb-md-0">
                <div class="card card-outline card-success shadow-sm mb-0 h-100">
                    <div class="card-header bg-white py-2">
                        <h6 class="card-title font-weight-bold text-dark m-0" style="font-size: 13px;">
                            <i class="fas fa-file-excel mr-1 text-success"></i> EXPORT DATA SPP KE EXCEL
                        </h6>
                    </div>
                    <div class="card-body py-3 d-flex align-items-center">
                        <form action="{{ route('keuangan.spp.export') }}" method="GET" class="w-100">
                            <div class="row align-items-end g-2">
                                <div class="col-sm-5 mb-1 mb-sm-0">
                                    <label class="form-label font-weight-bold text-xs text-secondary mb-1">Tahun Ajaran</label>
                                    <input type="text" name="tahun_ajaran" class="form-control form-control-sm font-weight-bold" value="2026/2027" required>
                                </div>
                                <div class="col-sm-7 mb-1 mb-sm-0">
                                    <button type="submit" class="btn btn-success btn-sm font-weight-bold w-100 py-1.5 shadow-sm">
                                        <i class="fas fa-file-download mr-1"></i> Download Excel SPP
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Card Jadwal Sholat Live & Indikator Terdekat Berkedip Hidup -->
            <div class="col-lg-7 col-md-6">
                <div class="card card-outline card-teal shadow-sm mb-0 h-100" style="border-color: #008080 !important;">
                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                        <h6 class="card-title font-weight-bold text-dark m-0" style="font-size: 13px;">
                            <i class="fas fa-mosque mr-1 text-teal"></i> JADWAL SHOLAT OTOMATIS (<span id="tanggalHijriahMasehi">DKI Jakarta</span>)
                        </h6>
                        <span class="badge badge-teal px-2 py-1" style="font-size: 10px; background-color: #008080; color: #fff;">
                            <i class="fas fa-clock mr-1"></i> <span id="jamRealtime">--:--:--</span>
                        </span>
                    </div>
                    <div class="card-body py-2 px-3 d-flex align-items-center">
                        <div class="row w-100 g-1 align-items-center">
                            <div class="col">
                                <div class="sholat-item" id="box_Subuh">
                                    <span class="sholat-name d-block">Subuh</span>
                                    <span class="sholat-time" id="s_subuh">--:--</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sholat-item" id="box_Dzuhur">
                                    <span class="sholat-name d-block">Dzuhur</span>
                                    <span class="sholat-time" id="s_dzuhur">--:--</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sholat-item" id="box_Ashar">
                                    <span class="sholat-name d-block">Ashar</span>
                                    <span class="sholat-time" id="s_ashar">--:--</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sholat-item" id="box_Maghrib">
                                    <span class="sholat-name d-block">Maghrib</span>
                                    <span class="sholat-time" id="s_maghrib">--:--</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="sholat-item" id="box_Isya">
                                    <span class="sholat-name d-block">Isya</span>
                                    <span class="sholat-time" id="s_isya">--:--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- WIDGET TOTAL TRANSAKSI & FILTER --}}
        <div class="row mb-4">
            <!-- WIDGET TOTAL TRANSAKSI -->
            <div class="col-lg-5 col-md-6 mb-3 mb-md-0 d-flex">
                <div class="card-stat-modern p-4 d-flex flex-column justify-content-between w-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase font-weight-bold tracking-wider opacity-9" style="font-size: 14px; letter-spacing: 0.5px;">{{ $labelTotal ?? 'Total Transaksi Hari Ini' }}</span>
                        
                        @if(isset($isFiltered) && $isFiltered)
                            <a href="{{ route('keuangan.spp') }}" class="badge bg-light text-dark px-2 py-1 shadow-sm text-decoration-none font-weight-bold" title="Kembali ke Transaksi Hari Ini">
                                <i class="fas fa-undo-alt mr-1 text-primary"></i> Reset ke Hari Ini
                            </a>
                        @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="font-weight-bolder mb-0" style="font-size: 3rem; letter-spacing: -1px; line-height: 1.1;">Rp {{ number_format($totalNominal ?? 0, 0, ',', '.') }}</h2>
                        <div class="bg-white-opacity rounded-circle text-teal" style="background: rgba(255,255,255,0.2); width: 65px; height: 65px; display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0;">
                            <i class="fas fa-cash-register"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD FORM FILTER PERIODE TRANSAKSI -->
            <div class="col-lg-7 col-md-6 d-flex">
                <div class="card card-outline card-secondary shadow-sm mb-0 w-100">
                    <div class="card-header bg-white py-2">
                        <h6 class="card-title font-weight-bold text-dark m-0" style="font-size: 13px;"><i class="fas fa-filter mr-1 text-secondary"></i> FILTER PERIODE TRANSAKSI</h6>
                    </div>
                    <div class="card-body py-3 d-flex flex-column justify-content-between">
                        
                        <div id="dateAlertWarning" class="alert alert-danger py-2 mb-2 shadow-sm" style="display: none; font-size: 12px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Perhatian:</strong> Tanggal <b>"Dari"</b> tidak boleh lebih besar dari tanggal <b>"Sampai"</b>! Silakan sesuaikan kembali.
                        </div>

                        <form action="{{ route('keuangan.spp') }}" method="GET" id="formFilterSpp" class="row g-2 align-items-end mb-0" onsubmit="return validateFilterDates(event)">
                            
                            @if(request('kjp_filter'))
                                <input type="hidden" name="kjp_filter" value="{{ request('kjp_filter') }}">
                            @endif

                            <div class="col-md-3 mb-1">
                                <label for="start_date" class="form-label font-weight-bold text-xs text-secondary mb-1">Dari</label>
                                <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>

                            <div class="col-md-3 mb-1">
                                <label for="end_date" class="form-label font-weight-bold text-xs text-secondary mb-1">Sampai</label>
                                <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>

                            <div class="col-md-3 mb-1">
                                <label for="kelas" class="form-label font-weight-bold text-xs text-secondary mb-1">Kelas</label>
                                <select name="kelas" id="kelas" class="form-control form-control-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach(\App\Models\Kelas::orderBy('nama_kelas', 'asc')->get() as $k)
                                        <option value="{{ $k->nama_kelas }}" {{ request('kelas') == $k->nama_kelas ? 'selected' : '' }}>
                                            {{ $k->nama_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Tahun Ajaran (30 Tahun Otomatis Diawali 2026/2027) -->
                            <div class="col-md-3 mb-1">
                                <label for="filter_tahun_ajaran" class="form-label font-weight-bold text-xs text-secondary mb-1">Tahun Ajaran</label>
                                <select name="filter_tahun_ajaran" id="filter_tahun_ajaran" class="form-control form-control-sm">
                                    <option value="">-- Semua --</option>
                                    @php
                                        $startYear = 2026;
                                        $currentActive = request('filter_tahun_ajaran');
                                    @endphp
                                    @for ($i = 0; $i < 30; $i++)
                                        @php
                                            $y1 = $startYear + $i;
                                            $y2 = $y1 + 1;
                                            $tahunAjaranVal = $y1 . '/' . $y2;
                                        @endphp
                                        <option value="{{ $tahunAjaranVal }}" {{ $currentActive == $tahunAjaranVal ? 'selected' : '' }}>
                                            {{ $tahunAjaranVal }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-12 mt-2 text-end">
                                <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3">
                                    <i class="fas fa-search mr-1"></i> Terapkan
                                </button>
                                <a href="{{ route('keuangan.spp') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">
                                    <i class="fas fa-sync mr-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: CARD TABEL DATA TRANSAKSI SPP --}}
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="card-title font-weight-bold text-dark m-0"><i class="fas fa-list mr-2 text-primary"></i> Transaksi Pembayaran SPP Siswa</h5>
                
                <div class="card-tools d-flex align-items-center gap-2">
                    <form method="GET" action="{{ route('keuangan.spp') }}" class="d-inline-block mr-2">
                        @if(request('start_date')) <input type="hidden" name="start_date" value="{{ request('start_date') }}"> @endif
                        @if(request('end_date')) <input type="hidden" name="end_date" value="{{ request('end_date') }}"> @endif
                        @if(request('kelas')) <input type="hidden" name="kelas" value="{{ request('kelas') }}"> @endif
                        @if(request('filter_tahun_ajaran')) <input type="hidden" name="filter_tahun_ajaran" value="{{ request('filter_tahun_ajaran') }}"> @endif

                        <select name="kjp_filter" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">-- Filter Semua KJP --</option>
                            <option value="1" {{ (isset($filterKjp) && $filterKjp == '1') || request('kjp_filter') === '1' ? 'selected' : '' }}>Penerima KJP</option>
                            <option value="0" {{ (isset($filterKjp) && $filterKjp == '0') || request('kjp_filter') === '0' ? 'selected' : '' }}>NON KJP</option>
                        </select>
                    </form>

                    <a href="{{ route('keuangan.laporan-spp', request()->all()) }}" class="btn btn-info btn-sm mr-2 font-weight-bold" target="_blank">
                        <i class="fas fa-file-alt mr-1"></i> Laporan SPP
                    </a>
                    
                    <button class="btn btn-primary btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahSpp">
                        <i class="fas fa-plus mr-1"></i> Transaksi Baru
                    </button>
                </div>
            </div>
            <div class="card-body px-0 pt-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Tipe</th>
                                <th>Bulan</th>
                                <th>Jumlah</th>
                                <th>Tanggal & Waktu Bayar</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sppPayments as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $item->siswa->nisn ?? '-' }}</td>
                                    <td class="font-weight-bold text-dark">{{ $item->siswa->nama ?? 'Siswa Tidak Ditemukan' }}</td>
                                    <td>{{ $item->siswa->kelas->nama_kelas ?? $item->siswa->kelas ?? '-' }}</td>
                                    
                                    <td>
                                        @if(optional($item->siswa)->is_kjp == 1 || $item->is_kjp == 1)
                                            <span class="badge badge-kjp px-2 py-1"><i class="fas fa-id-card mr-1"></i> KJP</span>
                                        @else
                                            <span class="badge badge-non-kjp px-2 py-1">NON KJP</span>
                                        @endif
                                    </td>

                                    <td>{{ $item->bulan }}</td>
                                    <td>
                                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                                        @if((optional($item->siswa)->is_kjp == 1 || $item->is_kjp == 1) && $item->nominal == 30000)
                                            <br><small class="text-muted font-italic">(Rp 30.000 Cash + Rp 170.000 Subsidi)</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $item->metode_pembayaran == 'CASH' ? 'primary' : ($item->metode_pembayaran == 'DEBET' ? 'info' : 'secondary') }}">
                                            {{ $item->metode_pembayaran }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item->status == 'PAID')
                                            <span class="badge bg-success">PAID</span>
                                        @else
                                            <span class="badge bg-warning text-dark">UNPAID</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#modalEditSpp{{ $item->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('keuangan.spp.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- MODAL EDIT SPP -->
                                <div class="modal fade" id="modalEditSpp{{ $item->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form action="{{ route('keuangan.spp.update', $item->id) }}" method="POST" class="form-spp-submit w-100">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-dark py-3">
                                                    <h5 class="modal-title font-weight-bold">
                                                        <i class="fas fa-edit mr-1"></i> Edit Transaksi SPP
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4 text-left">
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
                                                            <option value="1" {{ (int)$item->is_kjp === 1 || optional($item->siswa)->is_kjp === 1 ? 'selected' : '' }}>Penerima KJP</option>
                                                            <option value="0" {{ (int)$item->is_kjp === 0 && optional($item->siswa)->is_kjp !== 1 ? 'selected' : '' }}>NON KJP</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-secondary">Tahun Ajaran</label>
                                                        <select name="tahun_ajaran" class="form-control" required>
                                                            @php
                                                                $startYearEdit = 2026;
                                                            @endphp
                                                            @for ($j = 0; $j < 30; $j++)
                                                                @php
                                                                    $ey1 = $startYearEdit + $j;
                                                                    $ey2 = $ey1 + 1;
                                                                    $optVal = $ey1 . '/' . $ey2;
                                                                @endphp
                                                                <option value="{{ $optVal }}" {{ $item->tahun_ajaran == $optVal ? 'selected' : '' }}>
                                                                    {{ $optVal }}
                                                                </option>
                                                            @endfor
                                                        </select>
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
                                                    <button type="submit" class="btn btn-warning font-weight-bold text-white"><i class="fas fa-save mr-1"></i> SIMPAN PERUBAHAN</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">Belum ada data transaksi SPP.</td>
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
        <form action="{{ route('keuangan.spp.store') }}" method="POST" class="form-spp-submit w-100">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title font-weight-bold" id="modalTambahSppLabel">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Transaksi SPP Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-left">
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
                        <select id="inputTipeKjpTambah" class="form-control bg-light" disabled>
                            <option value="0">NON KJP</option>
                            <option value="1">Penerima KJP</option>
                        </select>
                        <input type="hidden" name="is_kjp" id="inputHiddenTipeKjp" value="0">
                    </div>

                    <div id="infoKjpAlert" class="alert alert-info py-2 mb-3" style="display: none; font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i> <strong>Siswa KJP:</strong> Membayar <strong>Rp 30.000 Cash</strong> + Subsidi KJP <strong>Rp 170.000</strong>.
                    </div>

                    <!-- TAHUN AJARAN OTOMATIS BERGANTI SETIAP TANGGAL 1 JULI -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Tahun Ajaran</label>
                        <select name="tahun_ajaran" class="form-control" required>
                            @php
                                $currentMonth = (int) date('m');
                                $currentCalendarYear = (int) date('Y');
                                
                                // Aturan: Berganti periode setiap tanggal 1 Juli (Bulan 7 ke atas masuk tahun ajaran baru)
                                if ($currentMonth >= 7) {
                                    $defaultActiveTahunAjaran = $currentCalendarYear . '/' . ($currentCalendarYear + 1);
                                } else {
                                    $defaultActiveTahunAjaran = ($currentCalendarYear - 1) . '/' . $currentCalendarYear;
                                }

                                $startYearModal = 2026;
                            @endphp

                            @for ($k = 0; $k < 30; $k++)
                                @php
                                    $my1 = $startYearModal + $k;
                                    $my2 = $my1 + 1;
                                    $valTahunAjaran = $my1 . '/' . $my2;
                                @endphp
                                <option value="{{ $valTahunAjaran }}" {{ $defaultActiveTahunAjaran == $valTahunAjaran ? 'selected' : '' }}>
                                    {{ $valTahunAjaran }} {{ $defaultActiveTahunAjaran == $valTahunAjaran ? '— (Tahun Berjalan)' : '' }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-secondary">Bulan (Otomatis Sesuai Tunggakan)</label>
                        <select name="bulan" class="form-control" required>
                            <option value="">-- Pilih Siswa Terlebih Dahulu --</option>
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

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

<script>
    let globalTimings = null;

    function fetchJadwalSholat() {
        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let yyyy = today.getFullYear();
        let dateStr = `${dd}-${mm}-${yyyy}`;
        let url = `https://api.aladhan.com/v1/timingsByCity/${dateStr}?city=Jakarta&country=Indonesia&method=20`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.data && data.data.timings) {
                    globalTimings = data.data.timings;
                    document.getElementById('s_subuh').innerText = globalTimings.Fajr;
                    document.getElementById('s_dzuhur').innerText = globalTimings.Dhuhr;
                    document.getElementById('s_ashar').innerText = globalTimings.Asr;
                    document.getElementById('s_maghrib').innerText = globalTimings.Maghrib;
                    document.getElementById('s_isya').innerText = globalTimings.Isha;
                    if(data.data.date && data.data.date.readable) {
                        document.getElementById('tanggalHijriahMasehi').innerText = data.data.date.readable;
                    }
                    highlightSholatTerdekat();
                }
            }).catch(error => console.error("Gagal memuat jadwal sholat:", error));
    }

    function updateClockAndCheckSholat() {
        let now = new Date();
        let hours = String(now.getHours()).padStart(2, '0');
        let minutes = String(now.getMinutes()).padStart(2, '0');
        let seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('jamRealtime').innerText = `${hours}:${minutes}:${seconds}`;

        if (globalTimings && seconds === "0") {
            highlightSholatTerdekat();
        }
    }

    function highlightSholatTerdekat() {
        if (!globalTimings) return;

        let now = new Date();
        let currentTime = now.getHours() * 60 + now.getMinutes();

        let prayers = [
            { name: 'Subuh', time: globalTimings.Fajr, id: 'box_Subuh' },
            { name: 'Dzuhur', time: globalTimings.Dhuhr, id: 'box_Dzuhur' },
            { name: 'Ashar', time: globalTimings.Asr, id: 'box_Ashar' },
            { name: 'Maghrib', time: globalTimings.Maghrib, id: 'box_Maghrib' },
            { name: 'Isya', time: globalTimings.Isha, id: 'box_Isya' }
        ];

        let parsedPrayers = prayers.map(p => {
            let parts = p.time.split(':');
            let pMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            return { ...p, minutes: pMinutes };
        });

        let targetPrayer = parsedPrayers.find(p => currentTime < p.minutes);
        if (!targetPrayer) {
            targetPrayer = parsedPrayers[0];
        }

        parsedPrayers.forEach(p => {
            let el = document.getElementById(p.id);
            if (el) {
                el.classList.remove('sholat-active');
                let badge = el.querySelector('.badge-terdekat');
                if (badge) badge.remove();
            }
        });

        let activeEl = document.getElementById(targetPrayer.id);
        if (activeEl && !activeEl.querySelector('.badge-terdekat')) {
            activeEl.classList.add('sholat-active');
            let badge = document.createElement('span');
            badge.className = 'badge-terdekat';
            badge.innerHTML = '<i class="fas fa-bell fa-shake mr-0.5"></i> TERDEKAT';
            activeEl.appendChild(badge);
        }
    }

    function validateFilterDates(event) {
        let startDate = document.getElementById('start_date').value;
        let endDate = document.getElementById('end_date').value;
        let warningBox = document.getElementById('dateAlertWarning');

        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            warningBox.style.display = "block";
            event.preventDefault();
            return false;
        }
        warningBox.style.display = "none";
        return true;
    }

    function handleSiswaChange(selectElement) {
        let siswaId = selectElement.value;
        let selectBulan = document.querySelector('#modalTambahSpp select[name="bulan"]');
        let selectTipeKjp = document.getElementById('inputTipeKjpTambah');
        let hiddenTipeKjp = document.getElementById('inputHiddenTipeKjp');
        let inputNominal = document.getElementById('inputNominalTambah');
        let infoKjpAlert = document.getElementById('infoKjpAlert');

        selectBulan.innerHTML = '<option value="">-- Pilih Bulan --</option>';

        if (siswaId !== "") {
            fetch(`/keuangan/spp/bulan-belum-lunas/${siswaId}`)
                .then(response => response.json())
                .then(data => {
                    selectTipeKjp.value = data.is_kjp ? "1" : "0";
                    hiddenTipeKjp.value = data.is_kjp ? "1" : "0";

                    if (data.is_kjp) {
                        inputNominal.value = "170.000";
                        if(infoKjpAlert) infoKjpAlert.style.display = "block";
                    } else {
                        inputNominal.value = "200.000";
                        if(infoKjpAlert) infoKjpAlert.style.display = "none";
                    }

                    if (data.bulan_tersedia && data.bulan_tersedia.length > 0) {
                        data.bulan_tersedia.forEach(item => {
                            let opt = document.createElement('option');
                            opt.value = item.bulan;
                            opt.textContent = `${item.bulan.padEnd(10, ' ')}  —  [ ${item.status} ]`;
                            
                            if (item.is_lunas) {
                                opt.disabled = true;
                                opt.style.color = '#adb5bd';
                            }
                            selectBulan.appendChild(opt);
                        });

                        let unpaiItem = data.bulan_tersedia.find(i => !i.is_lunas);
                        if (unpaiItem) {
                            selectBulan.value = unpaiItem.bulan;
                        }
                    }
                })
                .catch(error => console.error("Gagal memuat status siswa:", error));
        } else {
            selectTipeKjp.value = "0";
            hiddenTipeKjp.value = "0";
            inputNominal.value = "";
            if(infoKjpAlert) infoKjpAlert.style.display = "none";
            
            let defaultBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
            defaultBulan.forEach(b => {
                let opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                selectBulan.appendChild(opt);
            });
        }
    }

    function formatRupiahInput(e) {
        let raw = e.value.replace(/\D/g, "");
        if (raw !== "") {
            let num = parseInt(raw, 10);
            if (num > 10000000) num = 10000000;
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
        fetchJadwalSholat();
        setInterval(updateClockAndCheckSholat, 1000);

        if (typeof jQuery !== 'undefined' && jQuery().select2) {
            $('.select2-modal').each(function() {$(this).select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $(this).closest('.modal'),
                    width: '100%'
                }); 
            });
        }

        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form && form.matches('form') && !form.id.includes('formFilterSpp')) {
                const nominalInput = form.querySelector('.input-nominal');
                if (nominalInput) {
                    nominalInput.value = nominalInput.value.replace(/\./g, "");
                }
            }
        });
    });
</script>
@endsection