<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    protected $fillable = [
        'nisn', 
        'nis',
        'nik',
        'nama', 
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'jenis_kelamin',
        'agama',
        'no_telp',
        'status',
        'sekolah',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'foto',
        'id_kelas',
        'id_angkatan',
        'id_user',
        'is_kjp'
    ];

    protected $casts = [
        'is_kjp' => 'integer',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas');
    }

    public function pembayaranspps()
    {
        return $this->hasMany(PembayaranSpp::class, 'siswa_id', 'id');
    }
}