<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Tenggat sekarang bisa menyimpan jam, bukan cuma tanggal — data lama otomatis
            // jadi jam 00:00 (tetap tampil sebagai tanggal saja bila waktunya 00:00).
            $table->dateTime('due_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('due_date')->nullable()->change();
        });
    }
};
