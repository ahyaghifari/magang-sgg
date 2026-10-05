<?php

namespace App\Support;

use App\Models\Shift;

/**
 * Warna & ikon per jenis shift untuk tampilan (kalender, panel aksi, rekap presensi) —
 * satu sumber supaya warnanya sama di semua halaman. Warna sebenarnya ada di CSS
 * (.shift-tone-* di resources/css/app.css, termasuk mode gelap); bg/fg di sini cadangan inline.
 */
class ShiftTone
{
    /** @return array{key: string, class: string, bg: string, fg: string, icon: string} */
    public static function for(?string $type): array
    {
        $tone = match ($type) {
            'Pagi' => ['key' => 'pagi', 'bg' => '#fef3c7', 'fg' => '#92400e', 'icon' => 'fa-sun'],
            'Siang' => ['key' => 'siang', 'bg' => '#e0f2fe', 'fg' => '#075985', 'icon' => 'fa-cloud-sun'],
            'Libur' => ['key' => 'libur', 'bg' => '#ffe4e6', 'fg' => '#be123c', 'icon' => 'fa-mug-hot'],
            default => ['key' => 'lain', 'bg' => 'var(--surface-alt)', 'fg' => 'var(--text-muted)', 'icon' => 'fa-clock'],
        };

        return $tone + ['class' => 'shift-tone-' . $tone['key']];
    }

    /** Jam singkat untuk label sel: "12–21", "8.30–16.30". */
    public static function shortRange(?Shift $shift): string
    {
        if (! $shift) {
            return '';
        }

        $fmt = function (?string $t) {
            [$h, $m] = array_map('intval', explode(':', (string) $t) + [0, 0]);

            return $m ? sprintf('%d.%02d', $h, $m) : (string) $h;
        };

        return $fmt($shift->start_time) . '–' . $fmt($shift->end_time);
    }
}
