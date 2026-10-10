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
            'Malam' => ['key' => 'malam', 'bg' => '#e0e7ff', 'fg' => '#3730a3', 'icon' => 'fa-moon'],
            'Libur' => ['key' => 'libur', 'bg' => '#ffe4e6', 'fg' => '#be123c', 'icon' => 'fa-mug-hot'],
            default => ['key' => 'lain', 'bg' => 'var(--surface-alt)', 'fg' => 'var(--text-muted)', 'icon' => 'fa-clock'],
        };

        return $tone + ['class' => 'shift-tone-' . $tone['key']];
    }

    /**
     * Pilihan mode "Atur Per Tanggal": Pagi, Siang, Malam (jam dari master shift perusahaan bila
     * ada, selain itu jam bawaan) + Libur. value = nilai yang dikirim ke backend.
     *
     * @param  iterable<Shift>  $shifts  master shift perusahaan (boleh berisi model belum tersimpan)
     * @return array<int, array{value: string, label: string, time: string, class: string, icon: string}>
     */
    public static function entryOptions(iterable $shifts): array
    {
        $byCode = collect($shifts)->keyBy('code');
        $options = [];

        foreach (Shift::TYPES as $type) {
            $shift = $byCode->get($type);
            $start = $shift ? substr((string) $shift->start_time, 0, 5) : Shift::DEFAULT_TIMES[$type]['start'];
            $end = $shift ? substr((string) $shift->end_time, 0, 5) : Shift::DEFAULT_TIMES[$type]['end'];
            $tone = self::for($type);
            $options[] = ['value' => strtolower($type), 'label' => $type, 'time' => "{$start}–{$end}", 'class' => $tone['class'], 'icon' => $tone['icon']];
        }

        $tone = self::for('Libur');
        $options[] = ['value' => 'libur', 'label' => 'Libur', 'time' => 'Tidak masuk', 'class' => $tone['class'], 'icon' => $tone['icon']];

        return $options;
    }

    /** Isian saat ini per tanggal untuk badge di daftar "Atur Per Tanggal": [Y-m-d => 'Pagi'|'Libur'|...]. */
    public static function currentMap(iterable $entries): array
    {
        return collect($entries)
            ->mapWithKeys(fn ($e) => [$e->date->toDateString() => $e->off_day ? 'Libur' : ($e->shift?->code ?? 'Shift')])
            ->all();
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
