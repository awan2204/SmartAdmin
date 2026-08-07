<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\PembayaranSpp;

class SppController extends Controller
{
    public function index(Request $request)
    {
        $query = PembayaranSpp::with('siswa')->latest();

        if ($request->has('kjp_filter') && $request->kjp_filter !== null && $request->kjp_filter !== '') {
            $query->where('is_kjp', (int)$request->kjp_filter);
        }

        $sppPayments = $query->get();
        $filterKjp = $request->kjp_filter;

        return view('keuangan.spp', compact('sppPayments', 'filterKjp'));
    }

    public function store(Request $request)
    {
        $nominalClean = (int) preg_replace('/[^0-9]/', '', $request->nominal);
        $isKjpValue = $request->input('is_kjp') == '1' ? 1 : 0;

        // Logika Otomatis KJP: Jika siswa KJP dan membayar 30.000, status otomatis PAID
        $statusBayar = $request->status;
        if ($isKjpValue == 1 && $nominalClean == 30000) {
            $statusBayar = 'PAID';
        }

        $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tahun_ajaran'      => 'required|string',
            'bulan'             => 'required|string',
            'tanggal_bayar'     => 'required|date',
            'metode_pembayaran' => 'required|string',
            'status'            => 'required|string',
        ]);

        $spp = new PembayaranSpp();
        $spp->siswa_id          = $request->siswa_id;
        $spp->tahun_ajaran      = $request->tahun_ajaran;
        $spp->bulan             = $request->bulan;
        $spp->nominal           = $nominalClean;
        $spp->tanggal_bayar     = $request->tanggal_bayar;
        $spp->metode_pembayaran = $request->metode_pembayaran;
        $spp->is_kjp            = $isKjpValue;
        $spp->status            = $statusBayar;
        $spp->save();

        return redirect()->back()->with('success', 'Transaksi SPP berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $nominalClean = (int) preg_replace('/[^0-9]/', '', $request->nominal);
        $isKjpValue = $request->input('is_kjp') == '1' ? 1 : 0;

        // Logika Otomatis KJP pada saat Update
        $statusBayar = $request->status;
        if ($isKjpValue == 1 && $nominalClean == 30000) {
            $statusBayar = 'PAID';
        }

        $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tahun_ajaran'      => 'required|string',
            'bulan'             => 'required|string',
            'tanggal_bayar'     => 'required|date',
            'metode_pembayaran' => 'required|string',
            'status'            => 'required|string',
        ]);

        $spp = PembayaranSpp::findOrFail($id);
        $spp->siswa_id          = $request->siswa_id;
        $spp->tahun_ajaran      = $request->tahun_ajaran;
        $spp->bulan             = $request->bulan;
        $spp->nominal           = $nominalClean;
        $spp->tanggal_bayar     = $request->tanggal_bayar;
        $spp->metode_pembayaran = $request->metode_pembayaran;
        $spp->is_kjp            = $isKjpValue;
        $spp->status            = $statusBayar;
        $spp->save();

        return redirect()->back()->with('success', 'Transaksi SPP berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $spp = PembayaranSpp::findOrFail($id);
        $spp->delete();

        return redirect()->back()->with('success', 'Transaksi SPP berhasil dihapus!');
    }

    public function laporanSpp(Request $request)
    {
        $cariNama = $request->input('nama');
        $cariKelas = $request->input('id_kelas');
        
        $standardSpp = 200000;
        $listBulan = [
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'
        ];

        $query = Siswa::with(['kelas', 'pembayaranspps']);

        if ($cariNama) {
            $query->where('nama', 'LIKE', '%' . $cariNama . '%');
        }

        if ($cariKelas) {
            $query->where('id_kelas', $cariKelas);
        }

        $siswas = $query->get();

        $siswas->map(function($siswa) use ($standardSpp, $listBulan) {
            $sisaSaldo = $siswa->pembayaranspps->sum('nominal');
            $rincianAlokasi = [];

            foreach ($listBulan as $bulan) {
                if ($sisaSaldo >= $standardSpp) {
                    $rincianAlokasi[$bulan] = ['nominal' => $standardSpp, 'status' => 'LUNAS'];
                    $sisaSaldo -= $standardSpp;
                } elseif ($sisaSaldo > 0) {
                    $rincianAlokasi[$bulan] = ['nominal' => $sisaSaldo, 'status' => 'CICILAN'];
                    $sisaSaldo = 0;
                } else {
                    $rincianAlokasi[$bulan] = ['nominal' => 0, 'status' => 'BELUM'];
                }
            }

            $siswa->rincianAlokasi = $rincianAlokasi;
            return $siswa;
        });

        $kelasList = Kelas::all();

        return view('keuangan.laporan_spp', compact('siswas', 'kelasList', 'standardSpp', 'listBulan', 'cariNama', 'cariKelas'));
    }
}