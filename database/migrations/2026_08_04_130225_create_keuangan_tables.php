<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel SPP Payments
        Schema::create('spp_payments', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel siswa/users yang sudah ada di SmartSchool
            $table->unsignedBigInteger('siswa_id'); 
            $table->string('tahun_ajaran', 10); // Contoh: '2026/2027'
            $table->tinyInteger('bulan');       // 1 (Jan) - 12 (Des)
            $table->date('tanggal_bayar')->nullable();
            $table->decimal('nominal', 12, 2)->default(0);
            $table->enum('metode_pembayaran', ['CASH', 'DEBET', 'TRANSFER'])->default('CASH');
            $table->enum('status', ['PAID', 'UNPAID'])->default('UNPAID');
            $table->timestamps();
            $table->softDeletes(); // Keamanan data agar tidak terhapus permanen
        });

        // 2. Tabel Daftar Ulang
        Schema::create('daftar_ulang_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('siswa_id');
            $table->string('tahun_ajaran', 10);
            $table->string('jenis_biaya'); // Contoh: Seragam, Buku, SPP Awal
            $table->decimal('total_biaya', 12, 2);
            $table->decimal('jumlah_dibayar', 12, 2)->default(0);
            $table->enum('status', ['LUNAS', 'DICICIL', 'BELUM_BAYAR'])->default('BELUM_BAYAR');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_payments');
        Schema::dropIfExists('daftar_ulang_payments');
    }
};