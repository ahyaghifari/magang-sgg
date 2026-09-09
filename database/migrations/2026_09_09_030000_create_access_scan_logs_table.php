<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->index();                       // ID mentah dari mesin sidik jari
            $table->foreignId('intern_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nip')->nullable();
            $table->boolean('matched')->default(false);                   // apakah employee_id cocok NIP intern
            $table->dateTime('scanned_at')->index();
            $table->date('scan_date')->nullable();
            $table->time('scan_time')->nullable();
            $table->string('device_name')->nullable();
            $table->timestamps();

            // Satu tap = satu (orang, waktu). Dedupe agar sync berulang tidak menggandakan.
            $table->unique(['employee_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_scan_logs');
    }
};
