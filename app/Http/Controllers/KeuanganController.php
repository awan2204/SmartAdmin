<?php

namespace App\Http\Controllers;

use App\Models\SppPayment;
use App\Imports\SppImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KeuanganController extends Controller
{
    /**
     * Halaman Utama SPP
     */
    public function spp()
    {
        $title = "Manajemen Pembayaran SPP";
        
        // PERBAIKAN: Tambahkan with('siswa') agar relasi data siswa terbawa dengan sempurna
        $sppPayments = SppPayment::with('siswa')->latest()->get();

        return view('keuangan.spp', compact('title', 'sppPayments'));
    }

    /**
     * Proses Import File Excel SPP
     */
    public function sppImport(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file_excel');
        
        // Membuat unique hash berdasarkan nama file dan ukuran file
        $fileHash = md5_file($file->getRealPath());
        
        // Cek ke session apakah hash file ini sudah pernah di-upload sebelumnya
        if (session()->has('uploaded_file_' . $fileHash)) {
            return redirect()->back()->with('error', 'File Excel ini sudah pernah di-import sebelumnya! Tidak boleh ada duplikasi file.');
        }

        // Simpan tanda bahwa file ini sudah di-import
        session()->put('uploaded_file_' . $fileHash, true);

        // Jalankan proses import
        Excel::import(new SppImport($request->input('tahun_ajaran')), $file);

        return redirect()->back()->with('success', 'Data SPP Berhasil Diimport dan Dilindungi dari Dobel Data!');
    }

    /**
     * Simpan Transaksi Baru Secara Manual
     */
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

        // Simpan atau update jika siswa membayar bulan & tahun ajaran yang sama
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

        return redirect()->back()->with('success', 'Transaksi pembayaran SPP baru berhasil disimpan!');
    }
}