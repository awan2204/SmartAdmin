<?php

namespace App\Http\Controllers;

use App\Models\SppPayment;
use App\Models\Siswa;
use Illuminate\Http\Request;

class KeuanganController extends Controller
{
    public function spp()
    {
        $title = "Manajemen Pembayaran SPP";
        $sppPayments = SppPayment::with('siswa')->latest()->get();
        return view('keuangan.spp', compact('title', 'sppPayments'));
    }

    public function sppImport(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'tahun_ajaran' => 'required|string',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $tahun = $request->input('tahun_ajaran');

        if (($handle = fopen($path, "r")) !== FALSE) {
            $rowNo = 0;
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNo++;
                if ($rowNo == 1) continue;
                if (count($row) < 2) continue;

                $nisn      = trim($row[0] ?? '');
                $namaSiswa = trim($row[1] ?? '');
                $kelas     = trim($row[2] ?? '7A');
                $bulan     = trim($row[4] ?? 'JULI');
                
                $rawJumlah = $row[5] ?? 0;
                $jumlah    = is_numeric($rawJumlah) ? (float)$rawJumlah : (float)preg_replace('/[^0-9]/', '', $rawJumlah);

                if (empty($namaSiswa)) continue;

                $siswa = Siswa::where('nama', $namaSiswa)->first();

                if (!$siswa) {
                    $siswa = Siswa::create([
                        'nama'          => $namaSiswa,
                        'nisn'          => !empty($nisn) ? $nisn : (string)rand(1000000001, 9999999999),
                        'nis'           => (string)rand(100000, 999999),
                        'nik'           => (string)rand(1000000000000000, 9999999999999999),
                        'no_telp'       => '08' . rand(100000000, 999999999),
                        'kelas'         => $kelas,
                        'status'        => 'Aktif',
                        'jenis_kelamin' => 'L',
                        'alamat'        => '-',
                        'nama_ayah'     => '-',
                        'nama_ibu'      => '-',
                    ]);
                }

                SppPayment::updateOrCreate(
                    [
                        'siswa_id'     => $siswa->id,
                        'tahun_ajaran' => $tahun,
                        'bulan'        => $bulan,
                    ],
                    [
                        'tanggal_bayar'     => now(),
                        'nominal'           => $jumlah > 0 ? $jumlah : 200000,
                        'metode_pembayaran' => 'TRANSFER',
                        'status'            => 'PAID',
                    ]
                );
            }
            fclose($handle);
        }

        return redirect()->back()->with('success', 'Data Berhasil Masuk!');
    }

    public function sppStore(Request $request)
    {
        $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tahun_ajaran'      => 'required|string',
            'bulan'             => 'required|string',
            'nominal'           => 'required|numeric|min:0',
            'tanggal_bayar'     => 'required|date',
            'metode_pembayaran' => 'required|in:CASH,TRANSFER,DEBET',
            'status'            => 'required|in:PAID,UNPAID',
        ]);

        SppPayment::updateOrCreate(
            [
                'siswa_id'     => $request->siswa_id,
                'tahun_ajaran' => $request->tahun_ajaran,
                'bulan'        => $request->bulan,
            ],
            [
                'tanggal_bayar'     => $request->tanggal_bayar,
                'nominal'           => $request->nominal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status'            => $request->status,
            ]
        );

        return redirect()->back()->with('success', 'Transaksi berhasil disimpan!');
    }
}