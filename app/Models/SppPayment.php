<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranSpp extends Model
{
    use HasFactory;

    // SAMAKAN DENGAN NAMA TABEL DB
    protected $table = 'spp_payments'; 

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran',
        'bulan',
        'nominal',
        'tanggal_bayar',
        'metode_pembayaran',
        'is_kjp',
        'status',
    ];

    protected $casts = [
        'is_kjp' => 'boolean',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}   