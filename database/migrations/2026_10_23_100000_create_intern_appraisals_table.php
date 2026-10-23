<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = [
        'nilai_performance', 'nilai_motivation', 'nilai_responsibility', 'nilai_cooperativeness',
        'nilai_attendance', 'nilai_job_knowledge', 'nilai_quality_of_work', 'nilai_job_speed',
        'nilai_initiative', 'nilai_improvement',
    ];

    /**
     * Penilaian akhir kini dipisah per penilai (mentor, pembimbing, pimpinan) — satu baris
     * per (intern, role). Kolom nilai_* di tabel interns jadi hasil akumulasi (rata-rata
     * dari semua penilai yang sudah mengisi), dihitung ulang tiap ada perubahan.
     */
    public function up(): void
    {
        Schema::create('intern_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intern_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            foreach (self::FIELDS as $field) {
                $table->decimal($field, 3, 2)->nullable();
            }
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['intern_id', 'role']);
        });

        // Nilai lama (satu set di tabel interns) dipindah jadi penilaian milik penilai terakhirnya.
        $rows = DB::table('interns')
            ->leftJoin('users', 'users.id', '=', 'interns.dinilai_oleh')
            ->whereNotNull('interns.nilai_akhir')
            ->get(array_merge(['interns.id as intern_id', 'interns.dinilai_oleh', 'interns.catatan_penilaian', 'users.role as role'],
                array_map(fn ($f) => "interns.$f", self::FIELDS)));

        foreach ($rows as $row) {
            $data = (array) $row;
            $role = in_array($data['role'], ['mentor', 'pembimbing', 'pimpinan'], true) ? $data['role'] : 'pembimbing';

            DB::table('intern_appraisals')->insert(array_merge(
                ['intern_id' => $data['intern_id'], 'role' => $role, 'user_id' => $data['dinilai_oleh'],
                    'catatan' => $data['catatan_penilaian'], 'created_at' => now(), 'updated_at' => now()],
                array_intersect_key($data, array_flip(self::FIELDS)),
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_appraisals');
    }
};
