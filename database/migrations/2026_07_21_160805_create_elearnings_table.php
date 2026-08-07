<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_learnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->string('judul_materi');
            $table->string('mata_pelajaran');
            $table->enum('tipe', ['materi', 'tugas']);
            $table->text('deskripsi')->nullable();
            $table->string('file_attachment')->nullable();
            $table->dateTime('tenggat_waktu')->nullable(); // Khusus jika tipe = tugas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_learnings');
    }
};