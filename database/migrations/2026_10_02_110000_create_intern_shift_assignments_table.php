<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal shift harian per intern (roster): satu intern + satu tanggal = satu entri, berisi
     * shift ATAU Libur (off_day = true → shift_id null). Tanggal tanpa entri = belum diisi
     * (presensi memakai jadwal kerja tetap perusahaan seperti biasa). Tabel baru — tidak
     * menyentuh tabel/data yang sudah ada.
     */
    public function up(): void
    {
        Schema::create('intern_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->date('date');
            // restrictOnDelete: master shift yang sudah dipakai jadwal tidak bisa dihapus (riwayat aman).
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->boolean('off_day')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['intern_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_shift_assignments');
    }
};
