@extends('components.main')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <!-- DIPERBAIKI: Menggunakan d-flex justify-content-between align-items-center agar tombol turun dan rapi sejajar -->
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center px-4">
                        <h6 class="text-white text-capitalize m-0">Laporan & Rekapitulasi SPP Bulanan</h6>
                        <!-- Tombol Kembali ke Menu Pembayaran SPP -->
                        <a href="{{ route('keuangan.spp') }}" class="btn btn-light btn-sm mb-0">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <!-- Form Filter -->
                    <div class="px-4 pb-3">
                        <form method="GET" action="{{ route('keuangan.laporan-spp') }}" class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label class="form-label font-weight-bold">Nama Siswa:</label>
                                <input type="text" class="form-control border px-2" name="nama" value="{{ $cariNama ?? '' }}" placeholder="Cari nama...">
                            </div>
                            <div class="col-auto">
                                <label class="form-label font-weight-bold">Kelas:</label>
                                <select name="id_kelas" class="form-control border px-2">
                                    <option value="">-- Semua Kelas --</option>
                                    @foreach($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}" {{ (isset($cariKelas) && $cariKelas == $kelas->id) ? 'selected' : '' }}>
                                            {{ $kelas->nama_kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto align-self-end">
                                <button type="submit" class="btn btn-primary mb-0">Filter</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabel Data -->
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    <th class="text-center">#</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th>Tipe KJP</th>
                                    <th>Kelas</th>
                                    <th>Total Dibayar</th>
                                    <th>Rincian Status Per Bulan (Target: Rp 200.000/bln)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($siswas as $index => $siswa)
                                    @php
                                        $totalDibayarSemua = $siswa->pembayaranspps->sum('nominal');
                                    @endphp
                                    <tr>
                                        <td class="align-middle text-center text-xs">{{ $index + 1 }}</td>
                                        <td class="align-middle text-xs">{{ $siswa->nisn }}</td>
                                        
                                        <!-- Nama Siswa -->
                                        <td class="align-middle text-xs font-weight-bold text-dark">
                                            {{ $siswa->nama }}
                                        </td>

                                        <!-- Kolom Tipe KJP -->
                                        <td class="align-middle text-xs">
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

                                        <td class="align-middle text-xs">{{ $siswa->kelas->nama_kelas ?? 'Belum Ada' }}</td>
                                        
                                        <td class="align-middle text-xs font-weight-bold">
                                            Rp {{ number_format($totalDibayarSemua, 0, ',', '.') }}
                                        </td>

                                        {{-- Rincian Status Per Bulan --}}
                                        <td class="align-middle text-xs">
                                            <div class="d-flex flex-wrap gap-1" style="max-width: 480px;">
                                                @foreach($listBulan as $bulan)
                                                    @php
                                                        $infoBulan = $siswa->rincianAlokasi[$bulan] ?? ['nominal' => 0, 'status' => 'BELUM'];
                                                    @endphp

                                                    @if($infoBulan['status'] == 'LUNAS')
                                                        <span class="badge badge-sm bg-gradient-success m-1" title="{{ $bulan }}: Lunas (Rp 200.000)">
                                                            {{ $bulan }} (Lunas)
                                                        </span>
                                                    @elseif($infoBulan['status'] == 'CICILAN')
                                                        <span class="badge badge-sm bg-gradient-warning m-1 text-dark" title="{{ $bulan }}: Cicilan Rp {{ number_format($infoBulan['nominal'], 0, ',', '.') }}">
                                                            {{ $bulan }} (Rp {{ number_format($infoBulan['nominal'], 0, ',', '.') }})
                                                        </span>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-secondary m-1" style="opacity: 0.5;" title="{{ $bulan }}: Belum bayar">
                                                            {{ $bulan }} (Belum)
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-secondary">Data siswa tidak ditemukan.</td>
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

{{-- Script untuk memblokir tombol Back Browser dan memaksa kembali lewat tombol khusus --}}
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
        window.location.href = "{{ route('keuangan.spp') }}";
    };
</script>
@endsection