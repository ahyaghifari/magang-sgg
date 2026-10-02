<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Penanda "satu kali pemberian tugas" — tugas yang dikirim sekaligus ke beberapa
            // intern dari satu form berbagi batch_id yang sama, supaya di sisi pembimbing/mentor
            // bisa ditampilkan jadi satu kotak (di sisi intern tetap satu tugas per orang).
            $table->uuid('batch_id')->nullable()->after('assigned_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};
