@extends('components.main')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center px-4">
                        <h6 class="text-white text-capitalize m-0">Laporan & Rekapitulasi SPP Bulanan</h6>
                        <!-- Tombol Kembali ke Menu Pembayaran SPP -->
                        <a href="{{ route('keuangan.spp') }}" class="btn btn-light btn-sm mb-0">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    
                    {{-- BANNER INFORMASI FILTER TANGGAL AKTIF --}}
                    @if(request('start_date') || request('end_date') || request('kelas') || request('id_kelas') || request('nama'))
                        <div class="px-4 pt-2">
                            <div class="alert alert-info py-2 mb-3 text-sm text-white shadow-sm">
                                <i class="fas fa-filter mr-1"></i> <strong>Filter Aktif:</strong> 
                                @if(request('start_date')) Dari: <strong>{{ request('start_date') }}</strong> @endif
                                @if(request('end_date')) s/d: <strong>{{ request('end_date') }}</strong> @endif
                                @if(request('nama')) | Nama: <strong>{{ request('nama') }}</strong> @endif
                            </div>
                        </div>
                    @endif

                    <!-- Form Filter (Sudah Disertakan Tanggal Mulai & Selesai) -->
                    <div class="px-4 pb-3">
                        <form method="GET" action="{{ route('keuangan.laporan-spp') }}" class="row g-3 align-items-end">
                            
                            {{-- Input Nama Siswa --}}
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold text-xs">Nama Siswa:</label>
                                <input type="text" class="form-control form-control-sm border px-2" name="nama" value="{{ request('nama') }}" placeholder="Cari nama...">
                            </div>

                            {{-- Input Kelas --}}
                            <div class="col-md-3">
                                <label class="form-label font-weight-bold text-xs">Kelas:</label>
                                <select name="id_kelas" class="form-control form-control-sm border px-2">
                                    <option value="">-- Semua Kelas --</option>
                                    @foreach($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}" {{ request('id_kelas') == $kelas->id ? 'selected' : '' }}>
                                            {{ $kelas->nama_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Input Dari Tanggal --}}
                            <div class="col-md-2">
                                <label class="form-label font-weight-bold text-xs">Dari Tanggal:</label>
                                <input type="date" class="form-control form-control-sm border px-2" name="start_date" value="{{ request('start_date') }}">
                            </div>

                            {{-- Input Sampai Tanggal --}}
                            <div class="col-md-2">
                                <label class="form-label font-weight-bold text-xs">Sampai Tanggal:</label>
                                <input type="date" class="form-control form-control-sm border px-2" name="end_date" value="{{ request('end_date') }}">
                            </div>

                            {{-- Tombol Filter & Reset --}}
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm mb-0 px-3"><i class="fas fa-search mr-1"></i> Filter</button>
                                <a href="{{ route('keuangan.laporan-spp') }}" class="btn btn-outline-secondary btn-sm mb-0" title="Reset Filter"><i class="fas fa-sync"></i></a>
                            </div>
                        </form>
                    </div>

                    <!-- Tabel Data Utama -->
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 border-bottom-2">
                                    <th class="text-center">#</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Tipe KJP</th>
                                    <th>Kelas</th>
                                    <th>Total Dibayar</th>
                                    <th>Rincian Status Per Bulan (Target: Rp 200.000/bln)</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($siswas as $index => $siswa)
                                    @php
                                        $totalDibayarSemua = $siswa->pembayaranspps->sum('nominal');
                                    @endphp
                                    <tr style="border-bottom: 3px solid #dee2e6;">
                                        <td class="align-middle text-center text-xs font-weight-bold py-4">{{ $index + 1 }}</td>
                                        <td class="align-middle text-xs py-4">{{ $siswa->nisn }}</td>
                                        
                                        <!-- Nama Siswa -->
                                        <td class="align-middle text-xs font-weight-bold text-dark py-4">
                                            {{ $siswa->nama }}
                                        </td>

                                        <!-- Kolom Tipe KJP -->
                                        <td class="align-middle text-xs py-4">
                                            @if(isset($siswa->is_kjp) && $siswa->is_kjp == 1)
                                                <span class="badge badge-sm px-2 py-1" style="background-color: #008080; color: #fff; font-size: 9px;">
                                                    <i class="fas fa-id-card mr-1"></i> KJP
                                                </span>
                                            @else
                                                <span class="badge badge-sm px-2 py-1" style="background-color: #6c757d; color: #fff; font-size: 9px;">
                                                    NON KJP
                                                </span>
                                            @endif
                                        </td>

                                        <td class="align-middle text-xs py-4">{{ $siswa->kelas->nama_kelas ?? 'Belum Ada' }}</td>
                                        
                                        <td class="align-middle text-xs font-weight-bold py-4">
                                            Rp {{ number_format($totalDibayarSemua, 0, ',', '.') }}
                                        </td>

                                        {{-- Rincian Status Per Bulan --}}
                                        <td class="align-middle text-xs py-4">
                                            <div class="d-flex flex-wrap gap-1" style="max-width: 700px;">
                                                @foreach($listBulan as $bulan)
                                                    @php
                                                        $infoBulan = $siswa->rincianAlokasi[$bulan] ?? [
                                                            'can_debet' => false, 
                                                            'sudah_debet' => false, 
                                                            'status' => 'BELUM', 
                                                            'nominal' => 0, 
                                                            'nominal_cash' => 0,
                                                            'keterangan' => 'Belum'
                                                        ];
                                                        $nominalCashBulanIni = $infoBulan['nominal_cash'] ?? 0;
                                                    @endphp

                                                    @if(isset($siswa) && $siswa->is_kjp == 1)
                                                        {{-- KOTAK CARD BULANAN KHUSUS SISWA KJP --}}
                                                        <div class="card border shadow-none m-1 text-center" style="width: 105px; border-radius: 6px; overflow: hidden;">
                                                            <div class="bg-dark text-white py-1" style="font-size: 10px; font-weight: bold;">
                                                                {{ $bulan }}
                                                            </div>
                                                            <div class="p-1 bg-light">
                                                                @if(isset($infoBulan['sudah_debet']) && $infoBulan['sudah_debet'])
                                                                    <span class="badge bg-success w-100 py-1 mb-1" style="font-size: 9px;">LUNAS (DEBET)</span>
                                                                    <span class="text-success font-weight-bold d-block" style="font-size: 7.5px;">Cash + Debet 170rb</span>
                                                                @elseif(isset($infoBulan['can_debet']) && $infoBulan['can_debet'])
                                                                    <form id="form-debet-{{ $siswa->id }}-{{ $bulan }}" action="{{ route('keuangan.spp.debet-kjp') }}" method="POST">
                                                                        @csrf
                                                                        <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                                                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                                                                        <button type="button" onclick="confirmDebet('{{ $siswa->id }}', '{{ $bulan }}', '{{ $siswa->nama }}')" class="btn btn-warning btn-xs w-100 py-1 text-dark font-weight-bold mb-1" style="font-size: 9px; line-height: 1.2;">
                                                                            <i class="fas fa-hand-pointer mr-1"></i> BELUM DEBET
                                                                        </button>
                                                                    </form>
                                                                    {{-- Status Cash Lunas 30rb --}}
                                                                    <span class="text-success font-weight-bold d-block" style="font-size: 8px;">
                                                                        Cash: Rp {{ number_format($nominalCashBulanIni, 0, ',', '.') }} (Lunas)
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary text-white w-100 py-1 mb-1" style="font-size: 7.5px; opacity: 0.8;">BELUM BAYAR CASH</span>
                                                                    <span class="text-muted d-block" style="font-size: 7.5px;">
                                                                        @if($nominalCashBulanIni > 0)
                                                                            Baru bayar: Rp {{ number_format($nominalCashBulanIni, 0, ',', '.') }}
                                                                        @else
                                                                            Belum ada pembayaran
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        {{-- TAMPILAN SISWA NON KJP (RAPI, TANPA GANDA) --}}
                                                        @if(isset($infoBulan['status']) && $infoBulan['status'] == 'LUNAS')
                                                            <span class="badge badge-sm bg-gradient-success m-1 px-2 py-2 text-center" style="font-size: 10px; min-width: 90px;" title="{{ $bulan }}: Lunas">
                                                                <strong>{{ strtoupper($bulan) }}</strong><br>
                                                                <span style="font-size: 8.5px; font-weight: normal;">LUNAS</span>
                                                            </span>
                                                        @elseif(isset($infoBulan['status']) && $infoBulan['status'] == 'CICILAN')
                                                            <span class="badge badge-sm bg-gradient-warning m-1 px-2 py-2 text-dark text-center" style="font-size: 10px; min-width: 90px;" title="{{ $bulan }}: Cicilan">
                                                                <strong>{{ strtoupper($bulan) }}</strong><br>
                                                                <span style="font-size: 8.5px; font-weight: bold;">Rp {{ number_format($infoBulan['nominal'] ?? 0, 0, ',', '.') }}</span>
                                                            </span>
                                                        @else
                                                            <span class="badge badge-sm bg-gradient-secondary m-1 px-2 py-2 text-center" style="opacity: 0.6; font-size: 10px; min-width: 90px;" title="{{ $bulan }}: Belum bayar">
                                                                <strong>{{ strtoupper($bulan) }}</strong><br>
                                                                <span style="font-size: 8.5px; font-weight: normal;">BELUM</span>
                                                            </span>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>

                                        <!-- Tombol Aksi Ikon Mata -->
                                        <td class="align-middle text-center py-4">
                                            <button class="btn btn-info btn-sm px-3 mb-0" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa{{ $siswa->id }}" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-secondary">Data siswa tidak ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DETAIL SISWA DI LUAR TABEL --}}
@foreach($siswas as $siswa)
    @php
        $totalDibayarSemua = $siswa->pembayaranspps->sum('nominal');
        $targetTahunan = 2400000;
        $sisaKurang = max(0, $targetTahunan - $totalDibayarSemua);
        
        // URUTKAN TRANSAKSI DARI YANG PALING BARU KE PALING LAMA
        $riwayatTransaksi = $siswa->pembayaranspps->sortByDesc('created_at');
    @endphp
    <div class="modal fade" id="modalDetailSiswa{{ $siswa->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white py-3">
                    <h5 class="modal-title font-weight-bold text-white">
                        <i class="fas fa-user-graduate mr-1"></i> Detail Pembayaran SPP - {{ $siswa->nama }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Info Singkat Siswa -->
                    <div class="row mb-3 bg-light p-3 rounded">
                        <div class="col-md-4">
                            <small class="text-muted d-block font-weight-bold">NISN</small>
                            <span class="text-dark font-weight-bold">{{ $siswa->nisn }}</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block font-weight-bold">Kelas</small>
                            <span class="text-dark font-weight-bold">{{ $siswa->kelas->nama_kelas ?? 'Belum Ada' }}</span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block font-weight-bold">Status KJP</small>
                            <span>
                                @if(isset($siswa->is_kjp) && $siswa->is_kjp == 1)
                                    <span class="badge" style="background-color: #008080; color: #fff;">PENERIMA KJP</span>
                                @else
                                    <span class="badge bg-secondary">NON KJP</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Ringkasan Keuangan -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <small class="text-muted font-weight-bold">Total Telah Dibayar (Masuk)</small>
                                <h5 class="text-success font-weight-bold mb-0">Rp {{ number_format($totalDibayarSemua, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <small class="text-muted font-weight-bold">Perkiraan Kekurangan (Target Setahun)</small>
                                <h5 class="text-danger font-weight-bold mb-0">Rp {{ number_format($sisaKurang, 0, ',', '.') }}</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Riwayat Transaksi Historis Siswa Bersangkutan (Urut Terbaru ke Lama) -->
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-history mr-1"></i> Riwayat Transaksi Pembayaran</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-sm">
                            <thead>
                                <tr class="bg-secondary text-white">
                                    <th>#</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Bulan Tagihan</th>
                                    <th>Nominal</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatTransaksi as $trx)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $trx->created_at ? \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y, H:i') : '-' }}</td>
                                        <td><strong>{{ $trx->bulan }}</strong></td>
                                        <td>Rp {{ number_format($trx->nominal, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $trx->metode_pembayaran == 'CASH' ? 'primary' : ($trx->metode_pembayaran == 'DEBET' ? 'info' : 'secondary') }}">
                                                {{ $trx->metode_pembayaran }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $trx->status == 'PAID' ? 'success' : 'warning' }}">
                                                {{ $trx->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">Belum ada catatan riwayat transaksi untuk siswa ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Tambahkan CDN SweetAlert2 (jika belum ada di layout utama) & Script Konfirmasi --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDebet(siswaId, bulan, namaSiswa) {
        Swal.fire({
            title: 'Konfirmasi Debet KJP',
            html: `Apakah Anda yakin ingin mendebet subsidi KJP sebesar <b>Rp 170.000</b> dari Rekening DKI untuk siswa <b>${namaSiswa}</b> pada bulan <b>${bulan}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-debet-' + siswaId + '-' + bulan).submit();
            }
        });
    }

    {{-- Script untuk memblokir tombol Back Browser --}}
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
        window.location.href = "{{ route('keuangan.spp') }}";
    };
</script>
@endsection