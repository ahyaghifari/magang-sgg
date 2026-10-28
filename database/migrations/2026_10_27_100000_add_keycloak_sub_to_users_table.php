<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: DB lokal dev sudah punya kolom ini dari percobaan SSO sebelumnya.
        if (Schema::hasColumn('users', 'keycloak_sub')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Diisi otomatis oleh syifa/keycloak-sso saat login SSO pertama (dicocokkan lewat NIP).
            $table->string('keycloak_sub')->nullable()->unique()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['keycloak_sub']);
            $table->dropColumn('keycloak_sub');
        });
    }
};
