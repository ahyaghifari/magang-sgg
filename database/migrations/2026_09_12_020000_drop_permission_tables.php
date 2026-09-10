<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Filament Shield + spatie/laravel-permission dilepas. Akses super admin kini
 * ditentukan lewat config('access.super_admin_emails'), jadi tabel peran/izin
 * tidak dipakai lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }

    public function down(): void
    {
        // Tidak dibuat ulang — paket spatie/laravel-permission sudah tidak terpasang.
    }
};
