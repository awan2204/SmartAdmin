<?php

namespace App\Imports;

use App\Models\SppPayment;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SppImport implements ToCollection, WithHeadingRow
{
    protected $tahunAjaran;

    public function __construct($tahunAjaran = null)
    {
        $this->tahunAjaran = $tahunAjaran;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $nisn       = trim($row['nisn'] ?? '');
            $namaSiswa  = trim($row['nama_siswa'] ?? '');
            $kelas      = trim($row['kelas'] ?? '7A');
            $tahun      = trim($row['tahun_ajaran'] ?? $this->tahunAjaran ?? '2026/2027');
            $bulan      = trim($row['bulan'] ?? '');
            $jumlah     = (float) preg_replace('/[^0-9]/', '', $row['jumlah'] ?? 0);
            
            $metodeInput = strtoupper(trim($row['metode'] ?? 'CASH'));
            $metode      = in_array($metodeInput, ['CASH', 'DEBET', 'TRANSFER']) ? $metodeInput : 'CASH';

            // PERBAIKAN DI SINI: Deteksi status Excel dengan akurat
            $statusInput = strtolower(trim($row['status'] ?? 'lunas'));
            if (str_contains($statusInput, 'belum') || $statusInput == 'unpaid' || $statusInput == 'tunggakan') {
                $status = 'UNPAID';
            } else {
                $status = 'PAID';
            }

            if (empty($namaSiswa) || empty($bulan) || $jumlah <= 0) {
                continue;
            }

            // 1. Cari atau Buat Data Siswa
            $siswa = Siswa::where('nama', strtoupper($namaSiswa))
                ->when(!empty($nisn), function ($q) use ($nisn) {
                    return $q->orWhere('nisn', $nisn);
                })
                ->first();

            if (!$siswa) {
                $siswa = Siswa::create([
                    'nama'          => strtoupper($namaSiswa),
                    'nik'           => (string) rand(1000000000000000, 9999999999999999),
                    'nisn'          => !empty($nisn) ? $nisn : (string) rand(1000000000, 9999999999),
                    'nis'           => (string) rand(100000, 999999),
                    'no_telp'       => '08' . rand(100000000, 999999999),
                    'kelas'         => $kelas,
                    'status'        => 'Aktif',
                    'jenis_kelamin' => 'L', 
                    'alamat'        => '-',
                    'nama_ayah'     => '-',
                    'nama_ibu'      => '-',
                ]);
            } else {
                $siswa->update(['kelas' => $kelas]);
            }

            // 2. Simpan / Update Data Pembayaran SPP
            SppPayment::firstOrCreate(
                [
                    'siswa_id'     => $siswa->id,
                    'tahun_ajaran' => $tahun,
                    'bulan'        => ucfirst(strtolower($bulan)),
                ],
                [
                    'tanggal_bayar'     => now(),
                    'nominal'           => $jumlah,
                    'metode_pembayaran' => $metode,
                    'status'            => $status,
                ]
            );
        }
    }
}