<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar intern yang terdaftar di sebuah master shift (dipilih admin di form Master Shift).
     * Intern hanya bisa memilih shift tempat dia terdaftar saat mengisi Jadwal Shift-nya.
     * Tabel baru — tidak menyentuh tabel/data yang sudah ada.
     */
    public function up(): void
    {
        Schema::create('intern_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shift_id', 'intern_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_shift');
    }
};
