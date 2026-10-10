<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Desain seragam untuk semua notifikasi push portal. Notifikasi push memakai tampilan
 * bawaan OS (Android/iOS/Windows) — warna & font tidak bisa diatur — jadi "desain"-nya
 * lewat elemen yang didukung: judul singkat, isi beberapa baris berlabel (tanpa emoji), gambar
 * foto profil pengirim di kanan (hanya kalau ada), ikon kecil status bar Android (siluet
 * putih), dan tombol aksi. Klik tombol/notifikasinya ditangani public/sw.js.
 */
trait BuildsWebPush
{
    /**
     * @param  array<int, string>  $lines  baris isi notifikasi (baris kosong/null dibuang)
     */
    protected function pushMessage(
        string $title,
        array $lines,
        string $url,
        string $actionLabel = 'Buka',
        ?User $sender = null,
        ?string $tag = null,
    ): WebPushMessage {
        $message = (new WebPushMessage)
            ->title($title)
            ->body(implode("\n", array_filter($lines, fn ($line) => filled($line))))
            ->badge(\App\Support\Brand::versioned('images/notif-badge.png'))
            ->action($actionLabel, 'open')
            ->action('Nanti', 'dismiss')
            ->vibrate([120, 60, 120])
            // Prioritas tinggi + simpan 24 jam di server push, supaya HP yang sedang
            // tidur/offline sebentar tetap menerimanya begitu tersambung lagi.
            ->options(['TTL' => 86400, 'urgency' => 'high'])
            ->data(['url' => $url]);

        // Gambar besar di kanan hanya kalau ada foto profil pengirim — logo sudah tampil sebagai
        // ikon aplikasi di kiri atas notifikasi, jadi tidak perlu diulang di kanan.
        if ($icon = $this->pushIconFor($sender)) {
            $message->icon($icon);
        }

        if ($tag) {
            // Satu tag = satu notifikasi di HP; yang baru menggantikan yang lama sambil tetap berbunyi.
            $message->tag($tag)->renotify();
        }

        return $message;
    }

    /** Foto profil pengirim (kalau dia intern & sudah unggah foto), selain itu null. */
    protected function pushIconFor(?User $sender): ?string
    {
        $avatar = $sender?->intern?->avatar_path;

        return $avatar ? url('storage/' . $avatar) : null;
    }
}
