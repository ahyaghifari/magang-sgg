<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan perubahan jadwal shift dari intern, diputuskan Mentor-nya. Satu baris = satu
     * tanggal. "Lama" = isian saat diajukan (shift / Libur / kosong), "diajukan" = shift atau Libur.
     * Libur dicatat seperti jadwal harian: requested_off_day = true, tanpa shift (id & kode null).
     * Shift dicatat lewat jenisnya (requested_shift_code: Pagi/Siang/Malam) + id master shift bila
     * sudah ada — master yang belum ada dibuat otomatis saat Mentor menyetujui.
     * Status: pending → approved / rejected (oleh mentor) atau cancelled (oleh intern).
     * Tabel baru — tidak menyentuh tabel/data yang sudah ada.
     */
    public function up(): void
    {
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained('interns')->cascadeOnDelete();
            $table->date('date');
            // nullOnDelete: riwayat pengajuan tidak menghalangi admin menghapus master shift.
            $table->foreignId('old_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->boolean('old_off_day')->default(false);
            $table->foreignId('requested_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('requested_shift_code', 20)->nullable();     // jenis shift diajukan; null = Libur
            $table->boolean('requested_off_day')->default(false);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['intern_id', 'status']);
            $table->index(['intern_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};
