<?php

namespace App\Support;

use App\Models\Intern;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Isi teks sertifikat PKL = data asli dari database, ditimpa editan dari editor sertifikat
 * (tabel sertifikat_overrides) per field: `override ?? asli`. Dipakai bersama oleh editor
 * sertifikat, PDF DomPDF (certificates/pkl.blade.php), dan validasi simpan — supaya daftar
 * field-nya cuma didefinisikan sekali di sini.
 *
 * Override HANYA untuk tampilan sertifikat: nilai penilaian asli (kolom nilai_* & nilai_akhir
 * di tabel interns) tidak pernah diubah. Yang disimpan hanya field yang berbeda dari data asli
 * (diff()), jadi field yang tidak diedit tetap dinamis mengikuti database.
 */
class CertificateContent
{
    /** Field teks biasa → panjang maksimum. */
    public const FIELDS = [
        // Halaman depan
        'judul' => 255,
        'pengantar' => 255,
        'nama' => 255,
        'sekolah' => 255,
        'kegiatan' => 2000,
        'periode' => 255,
        'predikat' => 255,
        'tanggal' => 255,
        'ttd_nama' => 255,
        'ttd_jabatan' => 255,
        'ttd2_nama' => 255,
        'ttd2_jabatan' => 255,
        'no' => 255,
        // Halaman belakang (form penilaian)
        'form_judul' => 255,
        'form_subjudul' => 255,
        'info_nama' => 255,
        'info_unit' => 255,
        'info_sekolah' => 255,
        'info_periode' => 255,
        'kategori_1' => 255,
        'kategori_2' => 255,
        'catatan_penilai' => 2000,
        'nilai_akhir' => 20,
        'rating' => 255,
        'form_tanggal' => 255,
        'form_ttd_nama' => 255,
        'form_ttd_jabatan' => 255,
    ];

    /** Sub-field tiap baris kriteria → panjang maksimum. Baris dikunci pakai key Intern::CRITERIA. */
    public const KRITERIA_FIELDS = [
        'nama' => 255,
        'catatan' => 500,
        'nilai' => 20,
    ];

    /**
     * Data asli dari database (sama dengan yang tampil sebelum ada fitur override).
     * Bentuk: [field => string, ..., 'kriteria' => [criteria_key => [nama, catatan, nilai]]].
     */
    public static function defaults(Intern $intern): array
    {
        $intern->loadMissing(['institusi', 'unit.company', 'pembimbing', 'mentor']);

        $tanggal = 'Banjarbaru, ' . Carbon::now()->translatedFormat('d F Y');
        $periode = $intern->tanggal_mulai && $intern->tanggal_selesai
            ? $intern->tanggal_mulai->translatedFormat('d F Y') . ' – ' . $intern->tanggal_selesai->translatedFormat('d F Y')
            : '-';
        $company = $intern->unit->company->name ?? 'Syifa Global Group';
        $signer = $intern->pembimbing->name ?? $intern->mentor->name ?? '..............................';

        $kriteria = [];
        foreach (Intern::CRITERIA as $field => $c) {
            $kriteria[$field] = [
                'nama' => mb_strtoupper($c['title']),
                'catatan' => $c['description'],
                'nilai' => $intern->{$field} !== null ? number_format((float) $intern->{$field}, 2) : '-',
            ];
        }

        return [
            'judul' => 'SERTIFIKAT PENGHARGAAN',
            'pengantar' => 'Dengan bangga diberikan kepada:',
            'nama' => $intern->nama,
            'sekolah' => $intern->institusi->name ?? '-',
            'kegiatan' => 'Atas partisipasi dan dedikasinya dalam menyelesaikan program Praktik Kerja Lapangan (PKL) di '
                . $company
                . '. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.',
            'periode' => $periode,
            'predikat' => $intern->predikat() ?? '-',
            'tanggal' => $tanggal,
            'ttd_nama' => $signer,
            'ttd_jabatan' => 'Head of Department',
            'ttd2_nama' => '..............................',
            'ttd2_jabatan' => 'Pimpinan Perusahaan',
            'no' => sprintf('%03d/SGG-INT/%s', $intern->id, now()->format('Y')),

            'form_judul' => 'PENILAIAN',
            'form_subjudul' => 'Appraisal on the Job Training Result',
            'info_nama' => $intern->nama,
            'info_unit' => $intern->unit->name ?? '-',
            'info_sekolah' => $intern->institusi->name ?? '-',
            'info_periode' => $periode,
            'kategori_1' => 'ATTITUDE',
            'kategori_2' => 'KNOWLEDGE & SKILL',
            'kriteria' => $kriteria,
            'catatan_penilai' => (string) ($intern->catatan_penilaian ?? ''),
            'nilai_akhir' => number_format((float) ($intern->nilai_akhir ?? 0), 2),
            'rating' => $intern->predikat() ?? 'Poor',
            'form_tanggal' => $tanggal,
            'form_ttd_nama' => $signer,
            'form_ttd_jabatan' => 'Head of Department',
        ];
    }

    /**
     * Data asli digabung editan tersimpan (per field, per sub-field kriteria), plus:
     * - 'overridden' => daftar field yang sedang memakai editan (mis. ['nama', 'kriteria.nilai_motivation.nilai'])
     * - 'meta' => ['updated_by' => nama|null, 'updated_at' => Carbon|null] untuk info "terakhir diedit".
     */
    public static function for(Intern $intern): array
    {
        $defaults = self::defaults($intern);
        // Pengaman: kalau migrasi sertifikat_overrides belum dijalankan (mis. kode baru sudah
        // diupload ke server tapi belum `php artisan migrate`), sertifikat & PDF tetap jalan
        // memakai data asli — bukan error 500 saat intern mengunduh sertifikat.
        $override = match (true) {
            $intern->relationLoaded('sertifikatOverride') => $intern->sertifikatOverride,
            self::overridesTableExists() => $intern->sertifikatOverride()->with('editor')->first(),
            default => null,
        };
        $data = $override?->data ?? [];

        $merged = $defaults;
        $overridden = [];

        foreach (array_keys(self::FIELDS) as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $merged[$key] = $data[$key];
                $overridden[] = $key;
            }
        }

        foreach ($defaults['kriteria'] as $field => $row) {
            foreach (array_keys(self::KRITERIA_FIELDS) as $sub) {
                $value = $data['kriteria'][$field][$sub] ?? null;
                if (is_string($value)) {
                    $merged['kriteria'][$field][$sub] = $value;
                    $overridden[] = "kriteria.{$field}.{$sub}";
                }
            }
        }

        $merged['overridden'] = $overridden;
        $merged['meta'] = [
            'updated_by' => $override?->editor?->name,
            'updated_at' => $override?->updated_at,
        ];

        return $merged;
    }

    /**
     * Bersihkan input dari editor: hanya key yang dikenal, string, tag HTML dibuang, spasi
     * dirapikan, dipotong ke panjang maksimum. Key/baris yang tidak dikenal diabaikan.
     */
    public static function sanitize(array $input): array
    {
        $clean = [];

        foreach (self::FIELDS as $key => $max) {
            if (array_key_exists($key, $input) && (is_string($input[$key]) || is_numeric($input[$key]))) {
                $clean[$key] = self::cleanText((string) $input[$key], $max);
            }
        }

        foreach (array_keys(Intern::CRITERIA) as $field) {
            foreach (self::KRITERIA_FIELDS as $sub => $max) {
                $value = $input['kriteria'][$field][$sub] ?? null;
                if (is_string($value) || is_numeric($value)) {
                    $clean['kriteria'][$field][$sub] = self::cleanText((string) $value, $max);
                }
            }
        }

        return $clean;
    }

    /** Ambil hanya field yang berbeda dari data asli — ini yang disimpan ke sertifikat_overrides.data. */
    public static function diff(Intern $intern, array $clean): array
    {
        $defaults = self::defaults($intern);
        $diff = [];

        foreach (array_keys(self::FIELDS) as $key) {
            if (array_key_exists($key, $clean) && $clean[$key] !== self::cleanText((string) $defaults[$key], self::FIELDS[$key])) {
                $diff[$key] = $clean[$key];
            }
        }

        foreach ($defaults['kriteria'] as $field => $row) {
            foreach (self::KRITERIA_FIELDS as $sub => $max) {
                if (isset($clean['kriteria'][$field][$sub]) && $clean['kriteria'][$field][$sub] !== self::cleanText((string) $row[$sub], $max)) {
                    $diff['kriteria'][$field][$sub] = $clean['kriteria'][$field][$sub];
                }
            }
        }

        return $diff;
    }

    protected static function overridesTableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('sertifikat_overrides');
    }

    protected static function cleanText(string $value, int $max): string
    {
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value);
        $value = preg_replace("/\n{3,}/u", "\n\n", $value);

        return mb_substr(trim($value), 0, $max);
    }
}
