<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('nip');                    // = interns.nip = access_logs.employee_id
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('working_minutes')->nullable();
            $table->string('status');                 // 'present' | 'late' | 'absent'
            $table->boolean('out_of_window')->default(false);
            $table->string('source')->default('access_logs_fixed');
            $table->timestamps();

            $table->unique(['nip', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
