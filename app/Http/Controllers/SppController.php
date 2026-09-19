<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PembayaranSpp;
use App\Models\Siswa;
use App\Models\Kelas;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SppController extends Controller
{
    public function index(Request $request)
    {
        $query = PembayaranSpp::with(['siswa.kelas']);

        // 1. Filter Berdasarkan Status KJP
        if ($request->has('kjp_filter') && $request->kjp_filter !== null && $request->kjp_filter !== '') {
            $filterVal = (int) $request->kjp_filter;
            $query->whereHas('siswa', function($subQ) use ($filterVal) {
                $subQ->where('is_kjp', $filterVal);
            });
        }

        $isFiltered = false;
        $labelTotal = "Total Transaksi Hari Ini";
        $queryStatistik = clone $query;

        // 2. Filter Berdasarkan Tahun Ajaran
        if ($request->filled('filter_tahun_ajaran')) {
            $query->where('tahun_ajaran', $request->filter_tahun_ajaran);
            $queryStatistik->where('tahun_ajaran', $request->filter_tahun_ajaran);
            $isFiltered = true;
            $labelTotal = "Total Tahun Ajaran " . $request->filter_tahun_ajaran;
        }

        // 3. Filter Berdasarkan Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_bayar', [$request->start_date, $request->end_date]);
            $queryStatistik->whereBetween('tanggal_bayar', [$request->start_date, $request->end_date]);
            $isFiltered = true;
            $labelTotal = "Total Tgl " . date('d M Y', strtotime($request->start_date)) . " - " . date('d M Y', strtotime($request->end_date));
        } elseif ($request->filled('start_date')) {
            $query->whereDate('tanggal_bayar', '>=', $request->start_date);
            $queryStatistik->whereDate('tanggal_bayar', '>=', $request->start_date);
            $isFiltered = true;
            $labelTotal = "Total Mulai Tgl " . date('d M Y', strtotime($request->start_date));
        } elseif ($request->filled('end_date')) {
            $query->whereDate('tanggal_bayar', '<=', $request->end_date);
            $queryStatistik->whereDate('tanggal_bayar', '<=', $request->end_date);
            $isFiltered = true;
            $labelTotal = "Total s/d Tgl " . date('d M Y', strtotime($request->end_date));
        } elseif (!$request->filled('filter_tahun_ajaran')) {
            $query->whereDate('tanggal_bayar', date('Y-m-d'));
        }

        // 4. Filter Berdasarkan Kelas
        if ($request->filled('kelas')) {
            $query->whereHas('siswa.kelas', function($q) use ($request) {
                $q->where('nama_kelas', $request->kelas);
            });
            $queryStatistik->whereHas('siswa.kelas', function($q) use ($request) {
                $q->where('nama_kelas', $request->kelas);
            });
            $isFiltered = true;
        }

        $sppPayments = $query->latest()->get();
        $siswas = Siswa::all();
        $filterKjp = $request->kjp_filter;

        if ($isFiltered || ($request->has('kjp_filter') && $request->kjp_filter !== '')) {
            $totalNominal = $queryStatistik->sum('nominal');
        } else {
            $totalNominal = PembayaranSpp::whereDate('tanggal_bayar', date('Y-m-d'))->sum('nominal');
        }
        
        return view('keuangan.spp', compact('sppPayments', 'siswas', 'filterKjp', 'totalNominal', 'labelTotal', 'isFiltered'));
    }

    // METHOD AJAX: Mengembalikan daftar bulan yang belum lunas secara berurutan untuk siswa tertentu
    public function getBulanBelumLunas($siswaId)
    {
        $siswa = Siswa::with('pembayaranspps')->findOrFail($siswaId);
        $isKjp = (int) ($siswa->is_kjp ?? 0) === 1;
        $listBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        
        $transaksiList = $siswa->pembayaranspps->where('status', 'PAID');
        $bulanTersedia = [];

        if ($isKjp) {
            $targetCash = 30000;
            $targetDebet = 170000;
            
            $totalAkumulasiCash = $transaksiList->filter(function($item) {
                return !($item->metode_pembayaran == 'DEBET' && $item->nominal == 170000);
            })->sum('nominal');

            if ($totalAkumulasiCash == 0 && $transaksiList->count() > 0) {
                $totalAkumulasiCash = $transaksiList->sum('nominal');
            }

            foreach ($listBulan as $b) {
                $cashBulanIni = 0;
                if ($totalAkumulasiCash >= $targetCash) {
                    $cashBulanIni = $targetCash;
                    $totalAkumulasiCash -= $targetCash;
                } elseif ($totalAkumulasiCash > 0 && $totalAkumulasiCash < $targetCash) {
                    $cashBulanIni = $totalAkumulasiCash;
                    $totalAkumulasiCash = 0;
                }

                $cashLunas = ($cashBulanIni >= $targetCash);

                $sudahDebetBulanIni = $transaksiList->contains(function($item) use ($b, $targetDebet) {
                    return strtolower(trim($item->bulan)) === strtolower(trim($b)) 
                        && $item->metode_pembayaran == 'DEBET' 
                        && $item->nominal >= $targetDebet;
                });

                $isLunasKjp = ($cashLunas && $sudahDebetBulanIni);

                if ($isLunasKjp) {
                    $bulanTersedia[] = ['bulan' => $b, 'status' => 'LUNAS', 'is_lunas' => true];
                } else {
                    $ket = 'BELUM LUNAS';
                    if ($cashLunas && !$sudahDebetBulanIni) {
                        $ket = 'BELUM GESEK DEBET KJP';
                    }
                    $bulanTersedia[] = ['bulan' => $b, 'status' => $ket, 'is_lunas' => false];
                }
            }
        } else {
            foreach ($listBulan as $b) {
                $sudahBayar = $transaksiList->contains(function($item) use ($b) {
                    return strtolower(trim($item->bulan)) === strtolower(trim($b));
                });

                if ($sudahBayar) {
                    $bulanTersedia[] = ['bulan' => $b, 'status' => 'LUNAS', 'is_lunas' => true];
                } else {
                    $bulanTersedia[] = ['bulan' => $b, 'status' => 'BELUM LUNAS', 'is_lunas' => false];
                }
            }
        }

        return response()->json([
            'is_kjp' => $isKjp,
            'bulan_tersedia' => $bulanTersedia
        ]);
    }

    // Menampilkan halaman Laporan SPP dengan Logika Distribusi Cash Kronologis & Debet KJP yang Akurat
    public function laporanSpp(Request $request)
    {
        $query = Siswa::with(['pembayaranspps' => function($q) use ($request) {
            $q->where('status', 'PAID');

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $q->whereBetween('tanggal_bayar', [$request->start_date, $request->end_date]);
            } elseif ($request->filled('start_date')) {
                $q->whereDate('tanggal_bayar', '>=', $request->start_date);
            } elseif ($request->filled('end_date')) {
                $q->whereDate('tanggal_bayar', '<=', $request->end_date);
            }
        }, 'kelas']);

        if ($request->has('nama') && !empty($request->nama)) {
            $query->where('nama', 'LIKE', '%' . $request->nama . '%');
        }

        if ($request->has('id_kelas') && !empty($request->id_kelas)) {
            $query->where('kelas_id', $request->id_kelas);
        } elseif ($request->has('kelas') && !empty($request->kelas)) {
            $query->whereHas('kelas', function($q) use ($request) {
                $q->where('nama_kelas', $request->kelas);
            });
        }

        $siswas = $query->get();
        $kelasList = Kelas::all();
        
        $listBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        
        foreach ($siswas as $siswa) {
            $rincianAlokasi = [];
            $transaksiList = $siswa->pembayaranspps;
            $isKjp = (int) ($siswa->is_kjp ?? 0) === 1;

            if ($isKjp) {
                $targetCashPerBulan = 30000;
                $targetDebet = 170000;

                $totalCashMasuk = $transaksiList->filter(function($item) {
                    $metode = strtoupper(trim($item->metode_pembayaran));
                    return in_array($metode, ['CASH', 'TRANSFER']);
                })->sum('nominal');

                $sisaCashDistribusi = $totalCashMasuk;

                foreach ($listBulan as $bulan) {
                    $cashBulanIni = 0;
                    if ($sisaCashDistribusi >= $targetCashPerBulan) {
                        $cashBulanIni = $targetCashPerBulan;
                        $sisaCashDistribusi -= $targetCashPerBulan;
                    } elseif ($sisaCashDistribusi > 0 && $sisaCashDistribusi < $targetCashPerBulan) {
                        $cashBulanIni = $sisaCashDistribusi;
                        $sisaCashDistribusi = 0;
                    }

                    $cashLunas = ($cashBulanIni >= $targetCashPerBulan);

                    $trxBulanIni = $transaksiList->filter(function($item) use ($bulan) {
                        return strtolower(trim($item->bulan)) === strtolower(trim($bulan));
                    });

                    $sudahDebetBulanIni = $trxBulanIni->contains(function($item) use ($targetDebet) {
                        return strtoupper(trim($item->metode_pembayaran)) === 'DEBET' && $item->nominal >= $targetDebet;
                    });

                    $canDebet = ($cashLunas && !$sudahDebetBulanIni);
                    $isSudahDebet = ($cashLunas && $sudahDebetBulanIni);

                    $rincianAlokasi[$bulan] = [
                        'can_debet'    => $canDebet,
                        'sudah_debet'  => $isSudahDebet,
                        'nominal_cash' => $cashBulanIni
                    ];
                }
            } else {
                $targetBulanan = 200000;
                $sisaSaldo = $transaksiList->sum('nominal');

                foreach ($listBulan as $bulan) {
                    if ($sisaSaldo >= $targetBulanan) {
                        $rincianAlokasi[$bulan] = [
                            'nominal'    => $targetBulanan,
                            'status'     => 'LUNAS',
                            'keterangan' => 'Lunas'
                        ];
                        $sisaSaldo -= $targetBulanan;
                    } elseif ($sisaSaldo > 0 && $sisaSaldo < $targetBulanan) {
                        $nominalCicil = $sisaSaldo;
                        $rincianAlokasi[$bulan] = [
                            'nominal'    => $nominalCicil,
                            'status'     => 'CICILAN',
                            'keterangan' => 'Cicilan Rp ' . number_format($nominalCicil, 0, ',', '.')
                        ];
                        $sisaSaldo = 0;
                    } else {
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

    // Method konfirmasi debet KJP otomatis dengan dukungan kelipatan
    public function debetKjp(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required',
            'nominal'  => 'required',
        ]);

        try {
            $siswa = Siswa::with('pembayaranspps')->findOrFail($request->siswa_id);
            if ((int)($siswa->is_kjp ?? 0) !== 1) {
                return redirect()->back()->with('error', 'Siswa ini bukan penerima KJP!');
            }

            $nominalInput = (float) str_replace('.', '', $request->nominal);
            $targetDebet = 170000;

            if ($nominalInput <= 0 || $nominalInput % $targetDebet !== 0) {
                return redirect()->back()->with('error', 'Nominal debet KJP harus kelipatan Rp 170.000!');
            }

            $jumlahBulanDibayar = (int) ($nominalInput / $targetDebet);
            $tahunAjaran = $request->tahun_ajaran ?? '2026/2027';
            $listBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

            $transaksiList = $siswa->pembayaranspps->where('status', 'PAID')->where('tahun_ajaran', $tahunAjaran);

            // Cari bulan berurutan yang cash-nya sudah lunas tapi debetnya belum
            $bulanBelumDebet = [];
            $totalCashMasuk = $transaksiList->filter(function($item) {
                return in_array(strtoupper(trim($item->metode_pembayaran)), ['CASH', 'TRANSFER']);
            })->sum('nominal');

            $sisaCashDistribusi = $totalCashMasuk;

            foreach ($listBulan as $b) {
                $cashBulanIni = 0;
                if ($sisaCashDistribusi >= 30000) {
                    $cashBulanIni = 30000;
                    $sisaCashDistribusi -= 30000;
                }

                $cashLunas = ($cashBulanIni >= 30000);
                $sudahDebet = $transaksiList->contains(function($item) use ($b, $targetDebet) {
                    return strtolower(trim($item->bulan)) === strtolower(trim($b)) 
                        && strtoupper(trim($item->metode_pembayaran)) === 'DEBET' 
                        && $item->nominal >= $targetDebet;
                });

                if ($cashLunas && !$sudahDebet) {
                    $bulanBelumDebet[] = $b;
                }
            }

            if (empty($bulanBelumDebet)) {
                return redirect()->back()->with('error', 'GAGAL: Seluruh bulan (Juli - Juni) sudah lunas debet KJP atau kewajiban cash/transfer belum terpenuhi!');
            }

            if ($jumlahBulanDibayar > count($bulanBelumDebet)) {
                return redirect()->back()->with('error', 'GAGAL: Nominal debet melebihi sisa bulan yang belum dibayar! Sisa bulan yang dapat didebet hanya ' . count($bulanBelumDebet) . ' bulan (Maks: Rp ' . number_format(count($bulanBelumDebet) * $targetDebet, 0, ',', '.') . ').');
            }

            // Eksekusi penyimpanan kelipatan per bulan secara berurutan
            for ($i = 0; $i < $jumlahBulanDibayar; $i++) {
                $bulanTarget = $bulanBelumDebet[$i];
                
                PembayaranSpp::create([
                    'siswa_id'           => $request->siswa_id,
                    'is_kjp'             => 1,
                    'tahun_ajaran'       => $tahunAjaran,
                    'bulan'              => $bulanTarget,
                    'nominal'            => $targetDebet,
                    'tanggal_bayar'      => $request->tanggal_bayar ?? now(),
                    'metode_pembayaran'  => 'DEBET',
                    'status'             => 'PAID',
                ]);
            }

            return redirect()->back()->with('success', 'Berhasil! Debet KJP sebesar Rp ' . number_format($nominalInput, 0, ',', '.') . ' untuk ' . $jumlahBulanDibayar . ' bulan (' . implode(', ', array_slice($bulanBelumDebet, 0, $jumlahBulanDibayar)) . ') telah dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses debet KJP: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tahun_ajaran'      => 'required',
            'bulan'             => 'required',
            'nominal'           => 'required|numeric',
            'tanggal_bayar'     => 'required|date',
            'metode_pembayaran' => 'required',
            'status'            => 'required'
        ]);

        $siswa = Siswa::with('pembayaranspps')->findOrFail($request->siswa_id);
        $isKjp = (int) ($siswa->is_kjp ?? 0) === 1;
        $metode = strtoupper(trim($request->metode_pembayaran));
        $nominalInput = (float) str_replace('.', '', $request->nominal);
        $tahunAjaran = $request->tahun_ajaran;

        $listBulan = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        
        $transaksiList = $siswa->pembayaranspps->where('status', 'PAID')
            ->where('tahun_ajaran', $tahunAjaran);

        if ($isKjp) {
            $targetCashPerBulan = 30000;
            $totalTargetCashSetahun = $targetCashPerBulan * count($listBulan); // Rp 360.000

            $totalCashSudahBayar = $transaksiList->filter(function($item) {
                return in_array(strtoupper(trim($item->metode_pembayaran)), ['CASH', 'TRANSFER']);
            })->sum('nominal');

            // 1. JIKA METODE CASH / TRANSFER
            if (in_array($metode, ['CASH', 'TRANSFER'])) {
                if ($totalCashSudahBayar >= $totalTargetCashSetahun) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'GAGAL: Kewajiban Cash/Transfer untuk periode tahun ajaran ' . $tahunAjaran . ' sudah lunas penuh (12 bulan / Rp 360.000). Siswa KJP selanjutnya hanya dapat menggunakan metode <b>DEBET</b>.');
                }

                $sisaKekuranganCash = $totalTargetCashSetahun - $totalCashSudahBayar;
                $kembalianCash = 0;

                if ($nominalInput > $sisaKekuranganCash) {
                    $kembalianCash = $nominalInput - $sisaKekuranganCash;
                    $nominalInput = $sisaKekuranganCash;
                }

                PembayaranSpp::create([
                    'siswa_id'           => $request->siswa_id,
                    'is_kjp'             => 1,
                    'tahun_ajaran'       => $tahunAjaran,
                    'bulan'              => $request->bulan,
                    'nominal'            => $nominalInput,
                    'tanggal_bayar'      => $request->tanggal_bayar,
                    'metode_pembayaran'  => $metode,
                    'status'             => $request->status,
                ]);

                $pesanSukses = 'Transaksi pembayaran Cash/Transfer berhasil ditambahkan!';
                if ($kembalianCash > 0) {
                    $pesanSukses .= ' <b>[ADA KEMBALIAN CASH KE SISWA: Rp ' . number_format($kembalianCash, 0, ',', '.') . ']</b>';
                }

                return redirect()->route('keuangan.spp')->with('success', $pesanSukses);
            }

            // 2. JIKA METODE DEBET (Kelipatan Rp 170.000)
            if ($metode === 'DEBET') {
                $targetDebet = 170000;
                if ($nominalInput % $targetDebet !== 0) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'GAGAL: Nominal pembayaran DEBET KJP harus kelipatan Rp 170.000!');
                }

                $jumlahBulanDibayar = (int) ($nominalInput / $targetDebet);

                // Tentukan bulan mana saja yang cash-nya sudah lunas tapi debetnya belum
                $sisaCashDistribusi = $totalCashSudahBayar;
                $bulanBelumDebet = [];

                foreach ($listBulan as $b) {
                    $cashBulanIni = 0;
                    if ($sisaCashDistribusi >= 30000) {
                        $cashBulanIni = 30000;
                        $sisaCashDistribusi -= 30000;
                    }

                    $cashLunas = ($cashBulanIni >= 30000);
                    $sudahDebet = $transaksiList->contains(function($item) use ($b, $targetDebet) {
                        return strtolower(trim($item->bulan)) === strtolower(trim($b)) 
                            && strtoupper(trim($item->metode_pembayaran)) === 'DEBET' 
                            && $item->nominal >= $targetDebet;
                    });

                    if ($cashLunas && !$sudahDebet) {
                        $bulanBelumDebet[] = $b;
                    }
                }

                if (empty($bulanBelumDebet)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'GAGAL: Seluruh bulan (Juli sampai Juni) sudah lunas debet KJP atau kewajiban Cash/Transfer awal (Rp 360.000) belum terpenuhi!');
                }

                if ($jumlahBulanDibayar > count($bulanBelumDebet)) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'GAGAL: Nominal debet kelebihan! Sisa bulan yang dapat didebet hanya ' . count($bulanBelumDebet) . ' bulan (Maks: Rp ' . number_format(count($bulanBelumDebet) * $targetDebet, 0, ',', '.') . '). Transaksi diblokir.');
                }

                // Eksekusi simpan transaksi debet kelipatan per bulan secara berurutan
                for ($i = 0; $i < $jumlahBulanDibayar; $i++) {
                    $bulanTarget = $bulanBelumDebet[$i];

                    PembayaranSpp::create([
                        'siswa_id'           => $request->siswa_id,
                        'is_kjp'             => 1,
                        'tahun_ajaran'       => $tahunAjaran,
                        'bulan'              => $bulanTarget,
                        'nominal'            => $targetDebet,
                        'tanggal_bayar'      => $request->tanggal_bayar,
                        'metode_pembayaran'  => 'DEBET',
                        'status'             => $request->status,
                    ]);
                }

                return redirect()->route('keuangan.spp')->with('success', 'Berhasil! Transaksi DEBET KJP kelipatan Rp ' . number_format($nominalInput, 0, ',', '.') . ' untuk ' . $jumlahBulanDibayar . ' bulan (' . implode(', ', array_slice($bulanBelumDebet, 0, $jumlahBulanDibayar)) . ') berhasil disimpan.');
            }
        }

        // PENYIMPANAN TRANSAKSI NORMAL / NON-KJP
        PembayaranSpp::create([
            'siswa_id'           => $request->siswa_id,
            'is_kjp'             => $isKjp ? 1 : 0,
            'tahun_ajaran'       => $tahunAjaran,
            'bulan'              => $request->bulan,
            'nominal'            => $nominalInput,
            'tanggal_bayar'      => $request->tanggal_bayar,
            'metode_pembayaran'  => $request->metode_pembayaran,
            'status'             => $request->status,
        ]);

        return redirect()->route('keuangan.spp')->with('success', 'Transaksi pembayaran SPP berhasil ditambahkan!');
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
            $isKjp = $siswa->is_kjp ?? $spp->is_kjp;

            $spp->update([
                'siswa_id'           => $request->siswa_id,
                'is_kjp'             => $isKjp,
                'tahun_ajaran'       => $request->tahun_ajaran,
                'bulan'              => $request->bulan,
                'nominal'            => (float) str_replace('.', '', $request->nominal),
                'tanggal_bayar'      => $request->tanggal_bayar ?? $spp->tanggal_bayar,
                'metode_pembayaran'  => $request->metode_pembayaran ?? $spp->metode_pembayaran,
                'status'             => $request->status ?? $spp->status,
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



    public function exportExcel(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $listBulan = ['JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI'];
        
        $kelasList = Kelas::orderBy('nama_kelas', 'asc')->get();
        $allSiswas = Siswa::with('kelas')->get();

        foreach ($kelasList as $k) {
            $namaKelas = $k->nama_kelas;
            
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($namaKelas);

            $sheet->setCellValue('A1', 'NO');
            $sheet->setCellValue('B1', 'NAMA SISWA');
            $sheet->setCellValue('C1', 'KLS');

            $colIndex = 4;
            foreach ($listBulan as $bulan) {
                $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2);

                $sheet->mergeCells("{$startCol}1:{$endCol}1");
                $sheet->setCellValue("{$startCol}1", $bulan);

                $sheet->setCellValue("{$startCol}2", 'TANGGAL');
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . '2', 'CASH');
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2) . '2', 'DEBET');

                $colIndex += 3;
            }

            $siswas = $allSiswas->filter(function($siswa) use ($k, $namaKelas) {
                return (isset($siswa->kelas_id) && $siswa->kelas_id == $k->id) || 
                       (isset($siswa->kelas) && is_object($siswa->kelas) && $siswa->kelas->nama_kelas == $namaKelas);
            })->sortBy('nama');

            $rowNum = 3;
            $no = 1;

            foreach ($siswas as $siswa) {
                $sheet->setCellValue("A{$rowNum}", $no++);
                $sheet->setCellValue("B{$rowNum}", $siswa->nama);
                $sheet->setCellValue("C{$rowNum}", $namaKelas);

                $pembayaranList = PembayaranSpp::where('siswa_id', $siswa->id)->get();

                $colIndex = 4;
                foreach ($listBulan as $targetBulan) {
                    $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $cashCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    $debetCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2);

                    $trxBulan = $pembayaranList->filter(function($item) use ($targetBulan) {
                        $bDb = strtoupper(trim($item->bulan));
                        if (in_array($bDb, ['AGUSTUS', 'AGU', 'AGT', 'AUGUST'])) {
                            $bDb = 'AGUSTUS';
                        }
                        return $bDb === $targetBulan;
                    });

                    if ($trxBulan->isNotEmpty()) {
                        $tanggalBayar = $trxBulan->max('tanggal_bayar') ?? $trxBulan->max('created_at');
                        $sheet->setCellValue("{$startCol}{$rowNum}", $tanggalBayar ? date('d/m/Y', strtotime($tanggalBayar)) : '');

                        $totalCash = $trxBulan->filter(function($item) {
                            $metode = strtoupper(trim($item->metode_pembayaran));
                            return $metode === 'CASH' || $metode === 'TRANSFER';
                        })->sum('nominal');

                        if ($totalCash > 0) {
                            $sheet->setCellValue("{$cashCol}{$rowNum}", $totalCash);
                        }

                        $totalDebet = $trxBulan->where('metode_pembayaran', 'DEBET')->sum('nominal');
                        if ($totalDebet > 0) {
                            $sheet->setCellValue("{$debetCol}{$rowNum}", $totalDebet);
                        }
                    }

                    $colIndex += 3;
                }
                $rowNum++;
            }

            $highestRow = max(3, $rowNum - 1);
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex - 1);
            $allCellRange = "A1:{$highestCol}{$highestRow}";

            $sheet->getStyle("A1:C2")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E78'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $sheet->getStyle("D1:{$highestCol}2")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2F5597'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $sheet->getStyle($allCellRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD9D9D9'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $colIndex = 6;
            for ($i = 0; $i < count($listBulan); $i++) {
                $debetColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getStyle("{$debetColLetter}1:{$debetColLetter}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
                $colIndex += 3;
            }

            $sheet->getStyle("A3:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B3:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C3:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            for ($col = 1; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol); $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                if ($colLetter === 'B') {
                    $sheet->getColumnDimension($colLetter)->setWidth(28);
                } elseif ($colLetter === 'A' || $colLetter === 'C') {
                    $sheet->getColumnDimension($colLetter)->setWidth(8);
                } else {
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'LAPORAN_REKAP_SPP_' . str_replace('/', '-', $request->input('tahun_ajaran', '2026-2027')) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}