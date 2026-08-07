<?php

namespace App\Http\Controllers;

use App\Models\Data_angkatan;
use App\Exports\UsersExportSiswa;
use App\Models\Detail_siswa;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class SiswaController extends Controller
{
    public function updateAbsensi(Request $request, $id)
    {
        try {
            $absensi = Absensi::findOrFail($id);
            $absensi->update($request->all());

            return response()->json(['success' => true, 'message' => 'Absensi updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getSiswaByUser($id_user)
    {
        try {
            $siswa = Siswa::where('id_user', $id_user)->with('kelas')->first();
            return response()->json(['success' => true, 'data' => $siswa]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getSiswaKelasAbsensi(Request $request)
    {
        $kelas = $request->query('kelas');

        $siswa = Siswa::whereHas('kelas', function ($query) use ($kelas) {
            $query->where('nama_kelas', $kelas);
        })->get();

        return response()->json($siswa);
    }

    public function getSiswaByKelas(Request $request)
    {
        $selectedKelas = $request->query('kelas');
        
        try {
            $idKelas = Kelas::where('nama_kelas', $selectedKelas)->value('id');
            $siswaList = Siswa::where('id_kelas', $idKelas)->pluck('nama')->toArray();
            Log::info('Data siswa diambil:', $siswaList);
            
            return response()->json($siswaList);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil daftar siswa.']);
        }
    }

   public function index(Request $request)
{
    // Mulai query dasar untuk mengambil semua data siswa beserta relasi kelasnya
    $query = Siswa::with('kelas');

    // Filter berdasarkan Status Siswa jika dipilih di dropdown
    if ($request->has('status') && $request->status != '') {
        $query->where('status', $request->status);
    }

    // Filter berdasarkan Kelas jika dipilih di dropdown
    if ($request->has('kelas') && $request->kelas != '') {
        $query->where('id_kelas', $request->kelas);
    }

    // Eksekusi query dengan mengurutkan data terbaru
    $siswa = $query->latest()->get();
    
    // Ambil data master kelas untuk pilihan dropdown filter
    $kelas = Kelas::all();

    return view('pages.administrasi.data-siswa.siswa', [
        'siswas' => $siswa,
        'kelas'  => $kelas
    ])->with('title', 'Data Siswa');
}

    public function create()
    {
        $kelas = Kelas::all();
        return view('pages.administrasi.data-siswa.tambah', [
            'agamas'     => ['islam', 'kristen', 'buddha', 'konghucu', 'hindu'],
            'list_kelas' => $kelas
        ])->with('title', 'Tambah siswa');
    }

    public function store(Request $request)
    {
        $messages = [
            'regex'  => ':attribute harus diisi dengan huruf saja',
            'unique' => 'data ini sudah digunakan'
        ];
        
        $validate_data = [
            'nama'          => 'regex:/^[a-zA-Z\s]+$/',
            'nik'           => 'required|unique:siswas',
            'nis'           => 'required|unique:siswas',
            'nisn'          => 'required|unique:siswas',
            "no_pendaftar"  => 'required',
            "tempat_lahir"  => 'required',
            "tanggal_lahir" => 'required',
            "jenis_kelamin" => 'required',
            "agama"         => 'required',
            "nama_ayah"     => 'required',
            "nama_ibu"      => 'required',
            "nama_wali"     => 'required',
            "kelas"         => 'required',
            "no_telp"       => 'required',
            "status"        => 'required',
            "alamat"        => 'required',
            "foto"          => 'required',
        ];

        if ($request->status == 'pindahan') {
            $validate_data['asal_sekolah'] = 'required';
        }

        $this->validate($request, $validate_data, $messages);

        $filegambar = null;
        if ($request->hasFile('foto')) {
            $tujuan_upload = 'storage/murid/img';
            $file = $request->file('foto');
            $filegambar = time() . "_" . $file->getClientOriginalName();
            $file->move($tujuan_upload, $filegambar);
        }

        $user_new = User::create([
            'username' => $request->nis,
            'email'    => $request->nis . '@student.sch.id',
            'password' => Hash::make($request->nis),
            'role'     => 'siswa',
        ])->id;

        $siswa_new = Siswa::create([
            'nis'           => $request->nis,
            'nisn'          => $request->nisn,
            'nik'           => $request->nik,
            'no_pendaftar'  => $request->no_pendaftar,
            'nama'          => $request->nama,
            'nama_ayah'     => $request->nama_ayah,
            'nama_ibu'      => $request->nama_ibu,
            'nama_wali'     => $request->nama_wali,
            'jenis_kelamin' => $request->jenis_kelamin,
            'agama'         => $request->agama,
            'no_telp'       => $request->no_telp,
            'status'        => $request->status == 'pindahan' ? 'mutasi' : 'belum_lulus',
            'tempat_lahir'  => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'foto'          => $filegambar,
            'alamat'        => $request->alamat,
            'id_kelas'      => $request->kelas,
            'id_angkatan'   => Data_angkatan::firstWhere('tahun_masuk', now()->year)->id ?? 1,
            'id_user'       => $user_new,
        ])->id;

        if ($request->status == 'pindahan') {
            $tanggal_masuk = now()->format('Y-m-d');
            $kelasObj = Kelas::find($request->kelas);
            $namaKelas = $kelasObj ? $kelasObj->nama_kelas : '';

            if ($request->has('tanggal_masuk')) {
                $tanggal_masuk = $request->tanggal_masuk;
            }
            Detail_siswa::create([
                'asal_sekolah'  => $request->asal_sekolah,
                'tanggal_masuk' => $tanggal_masuk,
                'kelas_awal'    => $namaKelas,
                'id_siswa'      => $siswa_new,
            ]);
        }

        return redirect()->route('siswa_main')->with('toast_success', 'Data Siswa Berhasil di Tambahkan');
    }

    public function edit(Siswa $siswa)
    {
        $kelas = Kelas::all();
        return view('pages.administrasi.data-siswa.edit', [
            'siswa'        => $siswa,
            'kelas_list'   => $kelas,
            'status_siswa' => ['lulus', 'belum lulus', 'mutasi', 'keluar'],
        ])->with('title', 'Data Siswa');
    }

public function update(Request $request, Siswa $siswa)
    {
        // Tangkap semua inputan secara mutlak termasuk is_kjp
        $siswa->nis           = $request->nis;
        $siswa->nisn          = $request->nisn;
        $siswa->nik           = $request->nik;
        $siswa->nama          = $request->nama;
        $siswa->nama_ayah     = $request->nama_ayah;
        $siswa->nama_ibu      = $request->nama_ibu;
        $siswa->nama_wali     = $request->nama_wali;
        $siswa->jenis_kelamin = $request->jenis_kelamin;
        $siswa->agama         = $request->agama;
        $siswa->no_telp       = $request->no_telp;
        $siswa->status        = $request->status;
        $siswa->tempat_lahir  = $request->tempat_lahir;
        $siswa->tanggal_lahir = $request->tanggal_lahir;
        $siswa->alamat        = $request->alamat;
        $siswa->id_kelas      = $request->kelas;
        
        // PASTIKAN INI ADA: Menangkap nilai is_kjp dari form (0 atau 1)
        $siswa->is_kjp        = $request->has('is_kjp') ? (int) $request->is_kjp : 0;

        if ($request->hasFile('foto')) {
            $tujuan_upload = 'storage/murid/img/';
            $file = $request->file('foto');
            $filegambar = time() . "_" . $siswa->nis . "_" . $file->getClientOriginalName();
            $file->move($tujuan_upload, $filegambar);

            $old_file = public_path($tujuan_upload . $siswa->foto);
            if (File::exists($old_file)) {
                File::delete($old_file);
            }
            $siswa->foto = $filegambar;
        }

        // Simpan permanen ke database
        $siswa->save();

        return redirect('/administrasi/siswa')->with('toast_success', 'Data Siswa Berhasil di Ubah');
    }


    public function out_page(Request $request)
    {
        $query = Siswa::where(function ($query) {
            $query->where('status', 'keluar')
                  ->orWhere('status', 'lulus');
        });

        if ($request->has('nama') && $request->nama != '') {
            $query->where('nama', 'LIKE', '%' . $request->nama . '%');
        }
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $siswa = $query->get();

        return view('pages.administrasi.data-siswa.keluar', [
            'siswas' => $siswa
        ])->with('title', 'Siswa Keluar');
    }

    public function out(Request $request, Siswa $siswa)
    {
        $data = [
            'status'   => $request->status,
            'id_kelas' => null
        ];
        $siswa->update($data);
        return redirect()->route('siswa_out')->with('toast_success', 'Data Siswa Berhasil di Ubah');
    }

    public function destroy(Siswa $siswa)
    {
        $tujuan_upload = public_path('storage/murid/img/' . $siswa->foto);
        if (File::exists($tujuan_upload)) {
            File::delete($tujuan_upload);
        }
        $siswa->delete();

        return redirect()->route('siswa_out')->with('toast_success', 'Data Siswa Berhasil di Hapus');
    }

    public function export()
    {
        return Excel::download(new UsersExportSiswa, 'usersSiswa.xlsx');
    }
}