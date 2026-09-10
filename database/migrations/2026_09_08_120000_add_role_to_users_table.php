<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user', 'intern', 'pembimbing'])
                ->default('user')
                ->after('unit_id');
        });

        // Isi awal dari peran Shield yang sudah ada (hanya bila tabelnya masih ada —
        // paket spatie/laravel-permission sudah dilepas pada instalasi baru).
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            $morph = (new User)->getMorphClass();

            $idsByRole = fn (array $names) => DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', $morph)
                ->whereIn('roles.name', $names)
                ->pluck('model_has_roles.model_id');

            DB::table('users')->whereIn('id', $idsByRole(['admin', 'super_admin']))->update(['role' => 'admin']);
            DB::table('users')->whereIn('id', $idsByRole(['peserta']))->update(['role' => 'intern']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
