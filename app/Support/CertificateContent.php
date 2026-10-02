<?php

namespace App\Support;

use App\Models\Intern;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Isi sertifikat PKL = data asli dari database, ditimpa editan dari editor sertifikat
 * (tabel sertifikat_overrides) per field: `override ?? asli`. Dipakai bersama oleh editor
 * sertifikat, PDF DomPDF (certificates/pkl.blade.php), dan validasi simpan — supaya daftar
 * field-nya cuma didefinisikan sekali di sini.
 *
 * Halaman DEPAN (FRONT_LAYOUT): tiap elemen punya teks + posisi (top/left px di kanvas A4
 * landscape 1123x794) + gaya (font, ukuran, tebal, miring, warna) yang bisa diubah di editor.
 * Halaman BELAKANG (form penilaian): teks saja.
 *
 * Format `sertifikat_overrides.data`:
 * - field depan: ['text' => ?, 'top' => ?, 'left' => ?, 'font' => ?, 'size' => ?, 'bold' => ?, 'italic' => ?, 'color' => ?]
 *   (hanya properti yang diubah). Data lama berupa string polos tetap dibaca sebagai 'text'.
 * - field belakang: string. Kriteria: ['kriteria' => [field => [nama, catatan, nilai]]].
 *
 * Override HANYA untuk tampilan sertifikat: nilai penilaian asli (kolom nilai_* & nilai_akhir
 * di tabel interns) tidak pernah diubah. Yang disimpan hanya yang berbeda dari data asli
 * (diff()), jadi yang tidak diedit tetap dinamis mengikuti database.
 */
class CertificateContent
{
    /** Ukuran kanvas halaman (px, 96 dpi = A4 landscape) — sama di editor & DomPDF. */
    public const PAGE_WIDTH = 1123;

    public const PAGE_HEIGHT = 794;

    /**
     * Font yang boleh dipilih di editor (daftar tertutup supaya pasti bisa dirender DomPDF).
     * 'dir' = folder TTF di resources/fonts (null = font bawaan DomPDF/sistem).
     */
    public const FONTS = [
        'jakarta' => ['label' => 'Plus Jakarta Sans', 'family' => 'Plus Jakarta Sans', 'fallback' => 'sans-serif', 'dir' => 'plus-jakarta-sans'],
        'poppins' => ['label' => 'Poppins', 'family' => 'Poppins', 'fallback' => 'sans-serif', 'dir' => 'poppins'],
        'roboto' => ['label' => 'Roboto', 'family' => 'Roboto', 'fallback' => 'sans-serif', 'dir' => 'roboto'],
        'montserrat' => ['label' => 'Montserrat', 'family' => 'Montserrat', 'fallback' => 'sans-serif', 'dir' => 'montserrat'],
        'playfair' => ['label' => 'Playfair Display', 'family' => 'Playfair Display', 'fallback' => 'serif', 'dir' => 'playfair-display'],
        'merriweather' => ['label' => 'Merriweather', 'family' => 'Merriweather', 'fallback' => 'serif', 'dir' => 'merriweather'],
        'sans' => ['label' => 'Sans-serif (sistem)', 'family' => 'Helvetica', 'fallback' => 'sans-serif', 'dir' => null],
        'serif' => ['label' => 'Serif (sistem)', 'family' => 'Times', 'fallback' => 'serif', 'dir' => null],
    ];

    /**
     * Posisi & gaya bawaan tiap elemen halaman depan (px). width/align/lh/deco/prefix tetap;
     * yang bisa diubah editor: top, left, font, size, bold, italic, color.
     * color null = warna otomatis (predikat: mengikuti rating).
     */
    public const FRONT_LAYOUT = [
        'judul' => ['top' => 186, 'left' => 150, 'width' => 823, 'align' => 'center', 'font' => 'jakarta', 'size' => 38, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.2],
        'pengantar' => ['top' => 250, 'left' => 200, 'width' => 723, 'align' => 'center', 'font' => 'jakarta', 'size' => 14, 'bold' => false, 'italic' => false, 'color' => '#334155', 'lh' => 1.3],
        'nama' => ['top' => 276, 'left' => 170, 'width' => 783, 'align' => 'center', 'font' => 'playfair', 'size' => 44, 'bold' => true, 'italic' => true, 'color' => '#0b47a1', 'lh' => 1.2, 'deco' => 'underline'],
        'sekolah' => ['top' => 358, 'left' => 200, 'width' => 723, 'align' => 'center', 'font' => 'jakarta', 'size' => 15, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.3],
        'kegiatan' => ['top' => 392, 'left' => 175, 'width' => 773, 'align' => 'center', 'font' => 'jakarta', 'size' => 14, 'bold' => false, 'italic' => false, 'color' => '#334155', 'lh' => 1.7],
        'periode' => ['top' => 490, 'left' => 250, 'width' => 623, 'align' => 'center', 'font' => 'jakarta', 'size' => 13.5, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.3, 'prefix' => 'Periode: '],
        'predikat' => ['top' => 520, 'left' => 250, 'width' => 623, 'align' => 'center', 'font' => 'jakarta', 'size' => 14, 'bold' => true, 'italic' => false, 'color' => null, 'lh' => 1.3, 'deco' => 'pill', 'prefix' => 'Predikat: '],
        'tanggal' => ['top' => 630, 'left' => 95, 'width' => 270, 'align' => 'center', 'font' => 'jakarta', 'size' => 12, 'bold' => false, 'italic' => false, 'color' => '#334155', 'lh' => 1.3],
        'ttd_nama' => ['top' => 672, 'left' => 95, 'width' => 270, 'align' => 'center', 'font' => 'jakarta', 'size' => 14, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.3, 'deco' => 'signline'],
        'ttd_jabatan' => ['top' => 704, 'left' => 95, 'width' => 270, 'align' => 'center', 'font' => 'jakarta', 'size' => 11.5, 'bold' => false, 'italic' => false, 'color' => '#64748b', 'lh' => 1.3],
        'ttd2_nama' => ['top' => 672, 'left' => 758, 'width' => 270, 'align' => 'center', 'font' => 'jakarta', 'size' => 14, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.3, 'deco' => 'signline'],
        'ttd2_jabatan' => ['top' => 704, 'left' => 758, 'width' => 270, 'align' => 'center', 'font' => 'jakarta', 'size' => 11.5, 'bold' => false, 'italic' => false, 'color' => '#64748b', 'lh' => 1.3],
        'no' => ['top' => 744, 'left' => 95, 'width' => 520, 'align' => 'left', 'font' => 'jakarta', 'size' => 10.5, 'bold' => true, 'italic' => false, 'color' => '#042c6c', 'lh' => 1.3, 'prefix' => 'NO. SERTIFIKAT  '],
    ];

    /** Properti gaya/posisi yang boleh diubah per elemen depan. */
    public const STYLE_PROPS = ['top', 'left', 'font', 'size', 'bold', 'italic', 'color'];

    public const SIZE_MIN = 8;

    public const SIZE_MAX = 96;

    /** Field teks → panjang maksimum. */
    public const FIELDS = [
        // Halaman depan (juga punya posisi & gaya — lihat FRONT_LAYOUT)
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
            'nama' => self::capitalizeName((string) $intern->nama),
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
            'info_nama' => self::capitalizeName((string) $intern->nama),
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

    /** Warna bawaan per rating (predikat depan & rating belakang). */
    public static function ratingColor(?string $rating): string
    {
        return match (trim((string) $rating)) {
            'Excellent' => '#1c8a4d',
            'Good' => '#0b47a1',
            'Fair' => '#b45309',
            'Below Average' => '#c2410c',
            default => '#b91c1c',
        };
    }

    /** Posisi & gaya bawaan satu elemen depan (warna otomatis sudah diisi). */
    public static function defaultStyle(string $key, array $content): array
    {
        $style = self::FRONT_LAYOUT[$key];
        $style['color'] ??= self::ratingColor($content['predikat'] ?? null);

        return $style;
    }

    /**
     * Data asli digabung editan tersimpan, plus:
     * - 'styles' => [field depan => posisi & gaya final] (lihat FRONT_LAYOUT)
     * - 'overridden' => daftar field yang teksnya memakai editan (mis. ['nama', 'kriteria.nilai_motivation.nilai'])
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
            $text = self::overrideText($data[$key] ?? null);
            if ($text !== null) {
                $merged[$key] = $text;
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

        // Nama di sertifikat WAJIB berhuruf depan kapital tiap kata (seperti nama resmi),
        // termasuk kalau diedit lewat editor — mis. "al fatih" → "Al Fatih".
        $merged['nama'] = self::capitalizeName($merged['nama']);
        $merged['info_nama'] = self::capitalizeName($merged['info_nama']);

        // Posisi & gaya halaman depan: bawaan ditimpa properti yang pernah diubah.
        $styles = [];
        foreach (array_keys(self::FRONT_LAYOUT) as $key) {
            $style = self::defaultStyle($key, $merged);
            if (is_array($data[$key] ?? null)) {
                foreach (self::STYLE_PROPS as $prop) {
                    if (array_key_exists($prop, $data[$key])) {
                        $style[$prop] = $data[$key][$prop];
                    }
                }
            }
            $styles[$key] = $style;
        }

        $merged['styles'] = $styles;
        $merged['overridden'] = $overridden;
        $merged['meta'] = [
            'updated_by' => $override?->editor?->name,
            'updated_at' => $override?->updated_at,
        ];

        return $merged;
    }

    /**
     * CSS inline satu elemen depan — dipakai editor & PDF supaya posisi dan gaya identik.
     * $forPdf: DomPDF tidak butuh fallback font generik (font didaftarkan via @font-face).
     */
    public static function styleCss(array $s, bool $forPdf = false): string
    {
        $font = self::FONTS[$s['font']] ?? self::FONTS['jakarta'];
        $family = "'{$font['family']}'" . ($forPdf ? '' : ", {$font['fallback']}");

        return sprintf(
            'top:%spx;left:%spx;width:%spx;text-align:%s;font-family:%s;font-size:%spx;font-weight:%s;font-style:%s;color:%s;line-height:%s;',
            round((float) $s['top'], 1),
            round((float) $s['left'], 1),
            $s['width'],
            $s['align'],
            $family,
            round((float) $s['size'], 1),
            $s['bold'] ? 'bold' : 'normal',
            $s['italic'] ? 'italic' : 'normal',
            $s['color'],
            $s['lh'],
        );
    }

    /** Font yang dipakai elemen depan (untuk memuat @font-face hanya yang perlu di DomPDF). */
    public static function usedFonts(array $content): array
    {
        return collect($content['styles'] ?? [])->pluck('font')->push('jakarta')->unique()->values()->all();
    }

    /**
     * Bersihkan input dari editor: hanya key yang dikenal, tag HTML dibuang, spasi dirapikan,
     * dipotong ke panjang maksimum; posisi dibatasi di dalam halaman, font dari daftar,
     * ukuran dalam rentang, warna format #rrggbb. Key/properti yang tidak dikenal diabaikan.
     */
    public static function sanitize(array $input): array
    {
        $clean = [];

        foreach (self::FIELDS as $key => $max) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];

            if (isset(self::FRONT_LAYOUT[$key])) {
                // Field depan: string lama = teks saja; objek = teks + posisi/gaya.
                if (is_string($value) || is_numeric($value)) {
                    $clean[$key] = ['text' => self::cleanText((string) $value, $max)];
                } elseif (is_array($value)) {
                    $item = self::cleanStyle($key, $value);
                    if (isset($value['text']) && (is_string($value['text']) || is_numeric($value['text']))) {
                        $item['text'] = self::cleanText((string) $value['text'], $max);
                    }
                    if ($item !== []) {
                        $clean[$key] = $item;
                    }
                }
            } elseif (is_string($value) || is_numeric($value)) {
                $clean[$key] = self::cleanText((string) $value, $max);
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

    /** Ambil hanya yang berbeda dari data asli — ini yang disimpan ke sertifikat_overrides.data. */
    public static function diff(Intern $intern, array $clean): array
    {
        $defaults = self::defaults($intern);
        $diff = [];

        foreach (array_keys(self::FIELDS) as $key) {
            if (! array_key_exists($key, $clean)) {
                continue;
            }
            $defaultText = self::cleanText((string) $defaults[$key], self::FIELDS[$key]);

            if (isset(self::FRONT_LAYOUT[$key])) {
                $item = [];
                if (isset($clean[$key]['text']) && $clean[$key]['text'] !== $defaultText) {
                    $item['text'] = $clean[$key]['text'];
                }
                $defaultStyle = self::defaultStyle($key, $defaults);
                foreach (self::STYLE_PROPS as $prop) {
                    if (array_key_exists($prop, $clean[$key]) && ! self::sameValue($clean[$key][$prop], $defaultStyle[$prop])) {
                        $item[$prop] = $clean[$key][$prop];
                    }
                }
                if ($item !== []) {
                    $diff[$key] = $item;
                }
            } elseif ($clean[$key] !== $defaultText) {
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

    /**
     * Huruf pertama tiap kata dijadikan kapital (juga setelah tanda hubung/apostrof/titik),
     * huruf lainnya DIBIARKAN seperti aslinya — "muhammad naufal azd-zaki" → "Muhammad Naufal
     * Azd-Zaki", sedangkan "M. NASYWA" tetap "M. NASYWA".
     */
    public static function capitalizeName(string $name): string
    {
        return preg_replace_callback(
            '/(^|[\s\-\'.])(\p{Ll})/u',
            fn ($m) => $m[1] . mb_strtoupper($m[2]),
            trim($name),
        );
    }

    /** Teks editan dari nilai override: string lama, atau objek baru dengan 'text'. */
    protected static function overrideText(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        return is_array($value) && is_string($value['text'] ?? null) ? $value['text'] : null;
    }

    /** Validasi & rapikan properti posisi/gaya satu elemen depan. */
    protected static function cleanStyle(string $key, array $value): array
    {
        $width = self::FRONT_LAYOUT[$key]['width'];
        $out = [];

        if (isset($value['top']) && is_numeric($value['top'])) {
            $out['top'] = round(max(0, min(self::PAGE_HEIGHT - 10, (float) $value['top'])), 1);
        }
        if (isset($value['left']) && is_numeric($value['left'])) {
            $out['left'] = round(max(0, min(self::PAGE_WIDTH - $width, (float) $value['left'])), 1);
        }
        if (isset($value['font']) && is_string($value['font']) && isset(self::FONTS[$value['font']])) {
            $out['font'] = $value['font'];
        }
        if (isset($value['size']) && is_numeric($value['size'])) {
            $out['size'] = round(max(self::SIZE_MIN, min(self::SIZE_MAX, (float) $value['size'])), 1);
        }
        foreach (['bold', 'italic'] as $flag) {
            if (array_key_exists($flag, $value)) {
                $out[$flag] = filter_var($value[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (isset($value['color']) && is_string($value['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $value['color'])) {
            $out['color'] = strtolower($value['color']);
        }

        return $out;
    }

    protected static function sameValue(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.05;
        }
        if (is_string($a) && is_string($b)) {
            return strtolower($a) === strtolower($b);
        }

        return $a === $b;
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
