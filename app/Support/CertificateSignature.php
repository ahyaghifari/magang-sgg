<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Gambar tanda tangan & stempel di sertifikat. File asli ada di storage/app/private (tidak
 * masuk git — salin manual ke tiap server). Boleh berupa PNG biasa atau versi terenkripsi
 * `<nama>.png.enc` (php artisan certificate:encrypt-images) — yang terenkripsi diutamakan.
 * Latar putihnya dibuat transparan dan dipangkas sekali; hasilnya (PNG kecil) di-cache dalam
 * keadaan terenkripsi juga. Kalau file belum ada → null, sertifikat tetap tampil tanpa gambar.
 */
class CertificateSignature
{
    // Nama file di storage/app/private: stempel.png = gambar stempel, ttd.png = gambar tanda tangan.
    private const STAMP_FILE = 'stempel.png';

    private const SIGNATURE_FILE = 'ttd.png';

    public const FILES = [self::STAMP_FILE, self::SIGNATURE_FILE];

    /** Lebar maksimum hasil olahan (px) — cukup tajam untuk dicetak ±150px di kanvas A4 1123px. */
    private const MAX_WIDTH = 600;

    /** @return array{stamp: string, signature: string}|null */
    public static function dataUris(): ?array
    {
        $stamp = self::dataUri(self::STAMP_FILE);
        $signature = self::dataUri(self::SIGNATURE_FILE);

        return $stamp && $signature ? compact('stamp', 'signature') : null;
    }

    private static function dataUri(string $file): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $path = storage_path("app/private/{$file}");
        $encrypted = is_file($path . '.enc');

        if (! $encrypted && ! is_file($path)) {
            return null;
        }

        $path = $encrypted ? $path . '.enc' : $path;

        // Kunci cache ikut isi file (hash) → ganti/tukar file otomatis memakai hasil baru
        // (waktu ubah saja tidak cukup: rename/tukar nama tidak mengubahnya).
        // Nilai di cache dienkripsi supaya gambar rahasia tidak tersimpan polos di cache store.
        $cached = Cache::rememberForever('certificate-signature:v2:' . md5_file($path), function () use ($path, $encrypted) {
            $raw = file_get_contents($path);

            try {
                $png = $encrypted ? Crypt::decryptString($raw) : $raw;
            } catch (DecryptException $e) {
                report($e);

                return null;
            }

            $uri = self::process($png);

            return $uri ? Crypt::encryptString($uri) : null;
        });

        return $cached ? Crypt::decryptString($cached) : null;
    }

    /** Putihkan→transparan (halus di tepi), pangkas margin kosong, kecilkan, lalu jadi data URI. */
    private static function process(string $png): ?string
    {
        $src = @imagecreatefromstring($png);

        if (! $src) {
            return null;
        }

        if (imagesx($src) > self::MAX_WIDTH) {
            $src = imagescale($src, self::MAX_WIDTH);
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));

        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($src, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $min = min($r, $g, $b);

                // >= 235 dianggap kertas (transparan); <= 190 pekat; di antaranya naik bertahap.
                $opacity = $min >= 235 ? 0 : ($min <= 190 ? 1 : (235 - $min) / 45);

                if ($opacity <= 0) {
                    continue;
                }

                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, (int) round(127 * (1 - $opacity))));
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX <= $minX || $maxY <= $minY) {
            return null;
        }

        $cropped = imagecrop($out, ['x' => $minX, 'y' => $minY, 'width' => $maxX - $minX + 1, 'height' => $maxY - $minY + 1]);

        if (! $cropped) {
            return null;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);

        ob_start();
        imagepng($cropped, null, 9);

        return 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
    }
}
