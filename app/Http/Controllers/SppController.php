<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PembayaranSpp;
use App\Models\Siswa;
use App\Models\Kelas;

class SppController extends Controller
{
    public function index(Request $request)
    {
        $query = PembayaranSpp::with('siswa');

        if ($request->has('kjp_filter') && $request->kjp_filter !== null && $request->kjp_filter !== '') {
            $query->where('is_kjp', $request->kjp_filter);
        }

        $sppPayments = $query->latest()->get();
        $siswas = Siswa::all();
        $filterKjp = $request->kjp_filter;

        // Hitung total transaksi khusus hari ini (berdasarkan tanggal hari ini di database)
        $totalHariIni = PembayaranSpp::whereDate('created_at', today())->sum('nominal');
        
        return view('keuangan.spp', compact('sppPayments', 'siswas', 'filterKjp', 'totalHariIni'));
    }

    // Menampilkan halaman Laporan SPP dengan Logika Beruntun Kronologis untuk Non KJP
    public function laporanSpp(Request $request)
    {
        $query = Siswa::with(['pembayaranspps', 'kelas']);

        if ($request->has('nama') && !empty($request->nama)) {
            $query->where('nama', 'LIKE', '%' . $request->nama . '%');
        }

        if ($request->has('id_kelas') && !empty($request->id_kelas)) {
            $query->where('kelas_id', $request->id_kelas)->orWhere('kelas', $request->id_kelas);
        }

        $siswas = $query->get();
        $kelasList = Kelas::all();
        
        $listBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        
        foreach ($siswas as $siswa) {
            $rincianAlokasi = [];
            $transaksiList = $siswa->pembayaranspps;
            $isKjp = (int) ($siswa->is_kjp ?? 0) === 1;

            if ($isKjp) {
                // --- LOGIKA KJP TETAP SAMA ---
                $targetCash = 30000;
                $targetSubsidi = 170000;
                $totalCashBayar = $transaksiList->filter(function($item) {
                    return !($item->metode_pembayaran == 'DEBET' && $item->nominal == 170000);
                })->sum('nominal');
                if ($totalCashBayar == 0) {
                    $totalCashBayar = $transaksiList->sum('nominal');
                }

                $jumlahBulanTercoverCash = floor($totalCashBayar / $targetCash);
                if ($totalCashBayar > 0 && $jumlahBulanTercoverCash == 0) {
                    $jumlahBulanTercoverCash = 1;
                }

                $indexBulan = 0;
                foreach ($listBulan as $bulan) {
                    $sudahDebetAktual = $transaksiList
                        ->where('bulan', $bulan)
                        ->where('nominal', 170000)
                        ->where('metode_pembayaran', 'DEBET')
                        ->isNotEmpty();

                    $canDebet = ($indexBulan < $jumlahBulanTercoverCash);

                    $rincianAlokasi[$bulan] = [
                        'can_debet'   => $canDebet,
                        'sudah_debet' => $sudahDebetAktual
                    ];
                    $indexBulan++;
                }
            } else {
                // --- LOGIKA KRONOLOGIS BERUNTUN UNTUK SISWA NON KJP ---
                $targetBulanan = 200000;
                // Total seluruh uang yang sudah dibayarkan siswa secara akumulasi
                $sisaSaldo = $transaksiList->sum('nominal');

                foreach ($listBulan as $bulan) {
                    if ($sisaSaldo >= $targetBulanan) {
                        // Jika saldo mencukupi 1 bulan penuh (Rp 200.000)
                        $rincianAlokasi[$bulan] = [
                            'nominal'    => $targetBulanan,
                            'status'     => 'LUNAS',
                            'keterangan' => 'Lunas'
                        ];
                        $sisaSaldo -= $targetBulanan; // Kurangi saldo yang sudah terpakai untuk bulan ini
                    } elseif ($sisaSaldo > 0 && $sisaSaldo < $targetBulanan) {
                        // Jika saldo bersisa tapi kurang dari 200rb (menjadi cicilan di bulan berjalan)
                        $nominalCicil = $sisaSaldo;
                        $rincianAlokasi[$bulan] = [
                            'nominal'    => $nominalCicil,
                            'status'     => 'CICILAN',
                            'keterangan' => 'Cicilan Rp ' . number_format($nominalCicil, 0, ',', '.')
                        ];
                        $sisaSaldo = 0; // Saldo habis terpakai sebagai cicilan
                    } else {
                        // Jika saldo sudah 0, bulan-bulan berikutnya otomatis belum bayar
                        $rincianAlokasi[$bulan] = [
                            'nominal'    => 0,
                            'status'     => 'BELUM',
                            'keterangan' => 'Belum Bayar'
                        ];
                    }
                }
            }
            $siswa->rincianAlokasi = $rincianAlokasi;
        }

        return view('keuangan.laporan_spp', compact('siswas', 'kelasList', 'listBulan'));
    }

    // Method konfirmasi debet KJP Rp 170.000 otomatis
    public function debetKjp(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required',
            'bulan'    => 'required',
        ]);

        try {
            $sudahAda = PembayaranSpp::where('siswa_id', $request->siswa_id)
                ->where('bulan', $request->bulan)
                ->where('nominal', 170000)
                ->where('metode_pembayaran', 'DEBET')
                ->exists();

            if ($sudahAda) {
                return redirect()->back()->with('error', 'Debet KJP untuk bulan ' . $request->bulan . ' sudah pernah dicatat sebelumnya!');
            }

            PembayaranSpp::create([
                'siswa_id'          => $request->siswa_id,
                'is_kjp'            => 1,
                'tahun_ajaran'      => '2026/2027',
                'bulan'             => $request->bulan,
                'nominal'           => 170000,
                'tanggal_bayar'     => now(),
                'metode_pembayaran' => 'DEBET',
                'status'            => 'PAID',
            ]);

            return redirect()->back()->with('success', 'Berhasil! Debet KJP Rp 170.000 untuk bulan ' . $request->bulan . ' telah dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses debet KJP: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'siswa_id'     => 'required', 
            'tahun_ajaran' => 'required', 
            'bulan'        => 'required', 
            'nominal'      => 'required',
        ]);

        try {
            $siswa = Siswa::find($request->siswa_id);
            $isKjp = $request->has('is_kjp') ? $request->is_kjp : ($siswa->is_kjp ?? 0);

            PembayaranSpp::create([
                'siswa_id'          => $request->siswa_id,
                'is_kjp'            => $isKjp,
                'tahun_ajaran'      => $request->tahun_ajaran,
                'bulan'             => $request->bulan,
                'nominal'           => (float) str_replace('.', '', $request->nominal),
                'tanggal_bayar'     => $request->tanggal_bayar ?? now(),
                'metode_pembayaran' => $request->metode_pembayaran ?? 'CASH',
                'status'            => $request->status ?? 'PAID',
            ]);

            return redirect()->back()->with('success', 'Transaksi berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'siswa_id'     => 'required', 
            'tahun_ajaran' => 'required', 
            'bulan'        => 'required', 
            'nominal'      => 'required',
        ]);

        try {
            $spp = PembayaranSpp::findOrFail($id);
            $siswa = Siswa::find($request->siswa_id);
            $isKjp = $request->has('is_kjp') ? $request->is_kjp : ($siswa->is_kjp ?? $spp->is_kjp);

            $spp->update([
                'siswa_id'          => $request->siswa_id,
                'is_kjp'            => $isKjp,
                'tahun_ajaran'      => $request->tahun_ajaran,
                'bulan'             => $request->bulan,
                'nominal'           => (float) str_replace('.', '', $request->nominal),
                'tanggal_bayar'     => $request->tanggal_bayar ?? $spp->tanggal_bayar,
                'metode_pembayaran' => $request->metode_pembayaran ?? $spp->metode_pembayaran,
                'status'            => $request->status ?? $spp->status,
            ]);

            return redirect()->back()->with('success', 'Data berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            PembayaranSpp::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Data berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }
}