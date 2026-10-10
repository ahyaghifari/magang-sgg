<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master shift per perusahaan (kamus kode shift), dikelola admin di panel Filament.
     * Dipakai intern untuk mengisi jadwal shift hariannya (tabel intern_shift_assignments,
     * dibuat di tahap berikutnya). Tabel baru — tidak menyentuh tabel/data yang sudah ada.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);                                  // mis. P, S, M — unik per perusahaan
            $table->string('name', 100);                                 // mis. "Pagi", "Malam"
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_overnight')->default(false);             // jam pulang jatuh keesokan harinya
            $table->unsignedSmallInteger('break_minutes')->default(0);   // dipotong dari durasi kerja
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('checkin_buffer_minutes')->default(0);   // jendela tap masuk sebelum jam masuk
            $table->unsignedSmallInteger('checkout_buffer_minutes')->default(0);  // jendela tap pulang setelah jam pulang
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
