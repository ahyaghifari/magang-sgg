<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Role generik "User" dihapus — setiap akun sekarang wajib salah satu dari
        // Admin, Intern, atau Pembimbing. Jaga-jaga kalau masih ada baris lama
        // ber-role 'user', dipindah ke 'intern' dulu sebelum enum-nya diubah.
        DB::statement("UPDATE users SET role = 'intern' WHERE role = 'user'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'intern', 'pembimbing') NOT NULL DEFAULT 'intern'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'user', 'intern', 'pembimbing') NOT NULL DEFAULT 'user'");
    }
};
