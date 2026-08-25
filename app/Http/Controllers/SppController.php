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

        // 1. Filter Berdasarkan Status KJP (Mutlak berdasarkan data master siswa & aman dari data transaksi lama)
        if ($request->has('kjp_filter') && $request->kjp_filter !== null && $request->kjp_filter !== '') {
            $filterVal = (int) $request->kjp_filter; // 1 untuk KJP, 0 untuk Non KJP
            
            $query->whereHas('siswa', function($subQ) use ($filterVal) {
                $subQ->where('is_kjp', $filterVal);
            });
        }

        $isFiltered = false;
        $labelTotal = "Total Transaksi Hari Ini";

        // Clone kueri statistik agar terisolasi dari get() tabel utama
        $queryStatistik = clone $query;

        // 2. Filter Berdasarkan Rentang Tanggal (start_date & end_date)
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
        } else {
            // Default jika tidak ada filter tanggal: Tampilkan transaksi hari ini di tabel utama juga
            $query->whereDate('tanggal_bayar', date('Y-m-d'));
        }

        // 3. Filter Berdasarkan Kelas
        if ($request->filled('kelas')) {
            $query->whereHas('siswa.kelas', function($q) use ($request) {
                $q->where('nama_kelas', $request->kelas);
            });
            $queryStatistik->whereHas('siswa.kelas', function($q) use ($request) {
                $q->where('nama_kelas', $request->kelas);
            });
            $isFiltered = true;
        }

        // Eksekusi data
        $sppPayments = $query->latest()->get();
        $siswas = Siswa::all();
        $filterKjp = $request->kjp_filter;

        // Hitung nominal statistik
        if ($isFiltered || ($request->has('kjp_filter') && $request->kjp_filter !== '')) {
            $totalNominal = $queryStatistik->sum('nominal');
        } else {
            $totalNominal = PembayaranSpp::whereDate('tanggal_bayar', date('Y-m-d'))->sum('nominal');
        }
        
        return view('keuangan.spp', compact('sppPayments', 'siswas', 'filterKjp', 'totalNominal', 'labelTotal', 'isFiltered'));
    }

    // Menampilkan halaman Laporan SPP dengan Logika Distribusi Cash Kronologis & Debet KJP
    public function laporanSpp(Request $request)
    {
        $query = Siswa::with(['pembayaranspps' => function($q) use ($request) {
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
                $targetCashBulanan = 30000;
                
                $sisaCashAku = $transaksiList->filter(function($item) {
                    return !($item->metode_pembayaran == 'DEBET' && $item->nominal == 170000);
                })->sum('nominal');

                if ($sisaCashAku == 0) {
                    $sisaCashAku = $transaksiList->sum('nominal');
                }

                foreach ($listBulan as $bulan) {
                    $sudahDebetAktual = $transaksiList
                        ->where('bulan', $bulan)
                        ->where('nominal', 170000)
                        ->where('metode_pembayaran', 'DEBET')
                        ->isNotEmpty();

                    $cashBulanIni = 0;
                    if ($sisaCashAku >= $targetCashBulanan) {
                        $cashBulanIni = $targetCashBulanan;
                        $sisaCashAku -= $targetCashBulanan;
                    } elseif ($sisaCashAku > 0 && $sisaCashAku < $targetCashBulanan) {
                        $cashBulanIni = $sisaCashAku;
                        $sisaCashAku = 0;
                    } else {
                        $cashBulanIni = 0;
                    }

                    $canDebet = ($cashBulanIni >= $targetCashBulanan && !$sudahDebetAktual);

                    $rincianAlokasi[$bulan] = [
                        'can_debet'    => $canDebet,
                        'sudah_debet'  => $sudahDebetAktual,
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
            $isKjp = $siswa->is_kjp ?? 0; 

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
            $isKjp = $siswa->is_kjp ?? $spp->is_kjp;

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

    // METHOD EXPORT EXCEL DENGAN STYLING PROFESIONAL & SEKAT BULAN TEGAS
    public function exportExcel(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Hapus sheet bawaan

        $listBulan = ['JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI'];
        
        $kelasList = Kelas::orderBy('nama_kelas', 'asc')->get();
        $allSiswas = Siswa::with('kelas')->get();

        foreach ($kelasList as $k) {
            $namaKelas = $k->nama_kelas;
            
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($namaKelas);

            // Baris 1 & 2: Header Tabel
            $sheet->setCellValue('A1', 'NO');
            $sheet->setCellValue('B1', 'NAMA SISWA');
            $sheet->setCellValue('C1', 'KLS');

            $colIndex = 4; // Mulai dari Kolom D (Juli)
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

            // Filter siswa di kelas ini
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

            // Styling Header & Tabel Utama (Warna Profesional: Biru Gelap Soft & Teks Putih)
            $sheet->getStyle("A1:C2")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1F4E78'], // Biru Elegan
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

            // Styling Header Bulan (Baris 1 dan 2)
            $sheet->getStyle("D1:{$highestCol}2")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2F5597'], // Biru Medium
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

            // Border tipis untuk seluruh sel data
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

            // Pertegas sekat antar bulan (Garis vertikal medium di setiap akhir kolom Debet tiap bulan)
            $colIndex = 6; // Kolom F (Debet bulan pertama/Juli)
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

            // Perataan khusus untuk kolom No, Nama, dan Kelas
            $sheet->getStyle("A3:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B3:B{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C3:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Padding lebar kolom otomatis agar tidak mepet / terpotong
            for ($col = 1; $col <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol); $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                if ($colLetter === 'B') {
                    $sheet->getColumnDimension($colLetter)->setWidth(28); // Kolom Nama Siswa lebih luas
                } elseif ($colLetter === 'A' || $colLetter === 'C') {
                    $sheet->getColumnDimension($colLetter)->setWidth(8); // Kolom No & Kelas
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