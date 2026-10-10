<?php

namespace App\Support;

/**
 * Logo & ikon web (portal, login/daftar, panel admin, favicon, PWA). Hanya IKON — nama web
 * "Internship Syifa Global Group" selalu ditulis sebagai teks di sebelahnya.
 *
 * Logo perusahaan di Sertifikat PKL / PDF TIDAK lewat sini (InternCertificateController membaca
 * public/images/syifa-logo.* langsung), jadi mengganti ikon web tidak mengubah sertifikat.
 *
 * Setiap URL diberi ?v=<waktu ubah file> (cache-busting): bila file ikon ditimpa, browser & HP
 * otomatis memuat versi baru tanpa perlu menaikkan versi secara manual.
 */
class Brand
{
    /** Ikon web (PNG transparan persegi, 512x512). */
    public static function logoUrl(): string
    {
        return self::versioned('images/logo-simagang.png');
    }

    /** Favicon PNG 32x32 (dipakai juga panel admin & tab Editor Sertifikat). */
    public static function faviconUrl(): string
    {
        return self::versioned('images/favicon-32.png');
    }

    /** URL aset di public/ + ?v=<filemtime> bila file ada. */
    public static function versioned(string $relative): string
    {
        $path = public_path($relative);

        return asset($relative) . (is_file($path) ? '?v=' . filemtime($path) : '');
    }
}
