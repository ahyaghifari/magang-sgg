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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained()->cascadeOnDelete();

            // Pembimbing terkait tugas ini — pemberi tugas (web) atau yang disebut intern (verbal).
            // Null bila diisi intern tanpa menunjuk pembimbing tertentu.
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // 'verbal' = dicatat sendiri oleh intern (tugas disampaikan lisan oleh pembimbing).
            // 'web'    = dibuat langsung oleh pembimbing lewat web.
            $table->enum('source', ['verbal', 'web'])->default('verbal');

            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');

            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
