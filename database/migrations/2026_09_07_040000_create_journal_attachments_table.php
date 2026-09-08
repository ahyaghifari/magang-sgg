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
        Schema::create('journal_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->string('type');               // photo | document | link
            $table->string('path')->nullable();   // path berkas untuk foto / dokumen (PDF)
            $table->string('url')->nullable();    // tautan eksternal untuk type = link
            $table->string('label')->nullable();  // keterangan / judul lampiran (opsional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_attachments');
    }
};
