<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranSpp extends Model
{
    use HasFactory;

    // Sesuaikan dengan nama tabel asli di database
    protected $table = 'spp_payments';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran',
        'bulan',
        'nominal',
        'tanggal_bayar',
        'metode_pembayaran',
        'status'
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id');
    }
}