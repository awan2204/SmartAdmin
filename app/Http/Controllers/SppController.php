<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Kelas;

class SppController extends Controller
{
    public function laporanSpp(Request $request)
    {
        $cariNama = $request->input('nama');
        $cariKelas = $request->input('id_kelas');
        
        $standardSpp = 250000; // Standar SPP per bulan
        $listBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $query = Siswa::with(['kelas', 'pembayaranspps']);

        if ($cariNama) {
            $query->where('nama', 'LIKE', '%' . $cariNama . '%');
        }

        if ($cariKelas) {
            $query->where('id_kelas', $cariKelas);
        }

        $siswas = $query->get();

        // Algoritma akumulasi saldo pembayaran untuk melimpah ke bulan berikutnya
        $siswas->map(function($siswa) use ($standardSpp, $listBulan) {
            $sisaSaldo = $siswa->pembayaranspps->sum('nominal');
            $rincianAlokasi = [];

            foreach ($listBulan as $bulan) {
                if ($sisaSaldo >= $standardSpp) {
                    $rincianAlokasi[$bulan] = [
                        'nominal' => $standardSpp,
                        'status' => 'LUNAS'
                    ];
                    $sisaSaldo -= $standardSpp;
                } elseif ($sisaSaldo > 0) {
                    $rincianAlokasi[$bulan] = [
                        'nominal' => $sisaSaldo,
                        'status' => 'CICILAN'
                    ];
                    $sisaSaldo = 0;
                } else {
                    $rincianAlokasi[$bulan] = [
                        'nominal' => 0,
                        'status' => 'BELUM'
                    ];
                }
            }

            $siswa->rincianAlokasi = $rincianAlokasi;
            return $siswa;
        });

        $kelasList = Kelas::all();

        return view('keuangan.laporan_spp', compact('siswas', 'kelasList', 'standardSpp', 'listBulan', 'cariNama', 'cariKelas'));
    }
}