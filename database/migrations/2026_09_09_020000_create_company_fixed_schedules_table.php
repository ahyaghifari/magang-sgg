<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_fixed_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');           // 0=Minggu .. 6=Sabtu (Carbon::dayOfWeek)
            $table->boolean('is_off_day')->default(false);
            $table->time('start_time')->nullable();               // wajib bila !is_off_day
            $table->time('end_time')->nullable();                 // wajib bila !is_off_day
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('checkin_buffer_minutes')->default(0);   // untuk flag out_of_window
            $table->unsignedSmallInteger('checkout_buffer_minutes')->default(0);  // untuk flag out_of_window
            $table->timestamps();

            $table->unique(['company_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fixed_schedules');
    }
};
