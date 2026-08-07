<?php

namespace App\Http\Controllers;

use App\Models\ELearning;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ELearningController extends Controller
{
   public function index()
{
    $title = 'E-Learning'; // Tambahkan variabel ini
    $materiList = ELearning::with('kelas')->latest()->get();
    $kelases = Kelas::all();

    return view('administrasi.elearning', compact('title', 'materiList', 'kelases'));
}

    public function store(Request $request)
    {
        $request->validate([
            'judul_materi' => 'required|string|max:255',
            'mata_pelajaran' => 'required|string',
            'kelas_id' => 'required|exists:kelas,id',
            'tipe' => 'required|in:materi,tugas',
            'file_attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file_attachment')) {
            $filePath = $request->file('file_attachment')->store('elearning_files', 'public');
        }

        ELearning::create([
            'judul_materi' => $request->judul_materi,
            'mata_pelajaran' => $request->mata_pelajaran,
            'kelas_id' => $request->kelas_id,
            'tipe' => $request->tipe,
            'deskripsi' => $request->deskripsi,
            'tenggat_waktu' => $request->tenggat_waktu,
            'file_attachment' => $filePath,
        ]);

        return redirect()->back()->with('success', 'Materi/Tugas berhasil ditambahkan!');
    }

    public function destroy($id)
    {
        $data = ELearning::findOrFail($id);
        if ($data->file_attachment) {
            Storage::disk('public')->delete($data->file_attachment);
        }
        $data->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }
    public function download($id)
{
    $elearning = Elearning::findOrFail($id);
    
    // Mengambil lokasi file menggunakan kolom file_attachment
    $filePath = storage_path('app/public/' . $elearning->file_attachment);

    // Cek apakah file fisiknya benar-benar ada di storage
    if (!file_exists($filePath)) {
        return back()->with('error', 'File tidak ditemukan di storage server.');
    }

    return response()->download($filePath);
}
}