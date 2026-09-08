<?php

namespace App\Support;

class Brand
{
    /**
     * URL logo utama Syifa Global Group.
     *
     * Pakai file pertama yang ada di public/images/syifa-logo.{png,jpg,jpeg,webp,svg}.
     * PNG transparan (background-removed) diutamakan. Kalau belum ada satupun, tetap
     * kembalikan path .png supaya penggantian file cukup menaruh syifa-logo.png.
     */
    public static function logoUrl(): string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            $relative = "images/syifa-logo.{$ext}";

            if (is_file(public_path($relative))) {
                return asset($relative);
            }
        }

        return asset('images/syifa-logo.png');
    }
}
