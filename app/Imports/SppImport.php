<?php

namespace App\Imports;

use App\Models\PembayaranSpp;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SppImport implements ToCollection, WithHeadingRow
{
    protected $tahunAjaran;

    public function __construct($tahunAjaran = null)
    {
        $this->tahunAjaran = $tahunAjaran;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Ambil data dengan fleksibilitas key (mendukung berbagai variasi penamaan kolom)
                $nisn       = trim($row['nisn'] ?? '');
                $namaSiswa  = trim($row['nama_siswa'] ?? ($row['nama'] ?? ''));
                $kelas      = trim($row['kelas'] ?? '7A');
                $bulan      = trim($row['bulan'] ?? 'JULI');
                
                $rawJumlah  = $row['jumlah'] ?? ($row['nominal'] ?? 0);
                $jumlah     = is_numeric($rawJumlah) ? (float) $rawJumlah : (float) preg_replace('/[^0-9]/', '', $rawJumlah);
                
                $tahun      = $this->tahunAjaran ?? '2026/2027';

                if (empty($namaSiswa)) {
                    continue; // Lewati jika baris kosong
                }

                // Cari siswa berdasarkan NISN atau Nama
                $siswa = null;
                if (!empty($nisn)) {
                    $siswa = Siswa::where('nisn', $nisn)->first();
                }
                
                if (!$siswa) {
                    $siswa = Siswa::where('nama', 'LIKE', '%' . $namaSiswa . '%')->first();
                }

                // Jika siswa belum ada, buat baru secara otomatis
                if (!$siswa) {
                    $siswa = Siswa::create([
                        'nama'          => strtoupper($namaSiswa),
                        'nisn'          => !empty($nisn) ? $nisn : (string) rand(1000000001, 9999999999),
                        'nis'           => (string) rand(100000, 999999),
                        'nik'           => (string) rand(1000000000000000, 9999999999999999),
                        'no_telp'       => '08' . rand(100000000, 999999999),
                        'kelas'         => $kelas,
                        'status'        => 'Aktif',
                        'jenis_kelamin' => 'L',
                        'alamat'        => '-',
                        'nama_ayah'     => '-',
                        'nama_ibu'      => '-',
                    ]);
                }

                // Ambil tanggal dengan aman (mendukung tanggal_waktu_bayar, tanggal_bayar, dll)
                $tglBayarRaw = $row['tanggal_waktu_bayar'] ?? ($row['tanggal_bayar'] ?? ($row['tanggal'] ?? null));
                $tanggalBayar = date('Y-m-d');

                if (!empty($tglBayarRaw)) {
                    try {
                        if (is_numeric($tglBayarRaw)) {
                            $tanggalBayar = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tglBayarRaw)->format('Y-m-d');
                        } else {
                            $tanggalBayar = Carbon::parse($tglBayarRaw)->format('Y-m-d');
                        }
                    } catch (\Exception $ex) {
                        $tanggalBayar = date('Y-m-d');
                    }
                }

                // Simpan atau Update ke tabel spp_payments
                PembayaranSpp::updateOrCreate(
                    [
                        'siswa_id'     => $siswa->id,
                        'tahun_ajaran' => $tahun,
                        'bulan'        => ucfirst(strtolower($bulan)),
                    ],
                    [
                        'tanggal_bayar'     => $tanggalBayar,
                        'nominal'           => $jumlah,
                        'metode_pembayaran' => strtoupper($row['metode'] ?? 'TRANSFER'),
                        'status'            => strtoupper($row['status'] ?? 'PAID'),
                    ]
                );

            } catch (\Exception $e) {
                // Jika baris tertentu gagal, catat detail error dan barisnya ke file storage/logs/laravel.log
                Log::error("Gagal import pada baris ke-" . ($index + 2) . " ({$namaSiswa}): " . $e->getMessage());
            }
        }
    }
}