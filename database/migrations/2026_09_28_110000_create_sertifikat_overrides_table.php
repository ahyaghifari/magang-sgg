<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teks sertifikat PKL yang diedit lewat editor sertifikat — HANYA untuk tampilan
        // sertifikat (editor & PDF). Nilai penilaian asli di tabel interns tidak disentuh.
        // `data` hanya berisi field yang berbeda dari data asli (lihat App\Support\CertificateContent).
        Schema::create('sertifikat_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->unique()->constrained('interns')->cascadeOnDelete();
            $table->json('data');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_overrides');
    }
};
