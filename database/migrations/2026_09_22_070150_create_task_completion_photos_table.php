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
        Schema::create('task_completion_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });

        // Tidak perlu migrasi data dari completion_photo_path — belum ada baris yang memakainya.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('completion_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('completion_photo_path')->nullable();
        });

        Schema::dropIfExists('task_completion_photos');
    }
};
