<?php

namespace App\Notifications;

use App\Models\Intern;
use App\Models\ShiftChangeRequest;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke Mentor seorang intern saat intern itu mengajukan perubahan jadwal shift
 * (App\Services\Shift\ShiftChangeRequestService::submitEntries()). Beberapa tanggal yang diajukan
 * sekaligus digabung jadi satu notifikasi. Pembimbing & Pimpinan tidak diberi tahu.
 */
class ShiftChangeRequested extends Notification
{
    use BuildsWebPush;

    /** @param  Collection<int, ShiftChangeRequest>  $requests */
    public function __construct(
        private readonly Intern $intern,
        private readonly Collection $requests,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        $requests = $this->requests->sortBy('date')->values();
        $first = $requests->first();

        $count = $requests->count();
        $name = $this->intern->nama ?? 'peserta magang';

        // Satu tanggal: "Tanggal: Sen, 06 Okt 2026" + "Shift: Pagi → Siang".
        // Beberapa tanggal: tiap baris "Sen, 06 Okt: Pagi → Siang" (maks. 3), lalu "+N lainnya".
        if ($count === 1) {
            $lines = [
                'Tanggal: ' . $first->date->locale('id')->translatedFormat('D, d M Y'),
                'Shift: ' . $first->changeLabel(),
            ];
        } else {
            $lines = $requests->take(3)
                ->map(fn ($r) => $r->date->locale('id')->translatedFormat('D, d M') . ': ' . $r->changeLabel())
                ->all();
            if ($count > 3) {
                $lines[] = '+' . ($count - 3) . ' lainnya';
            }
        }
        $lines[] = 'Alasan: ' . $first->reason;

        return $this->pushMessage(
            title: $count === 1 ? 'Pengajuan perubahan shift dari ' . $name : "{$count} pengajuan perubahan shift dari {$name}",
            lines: $lines,
            url: route('pembimbing.shifts', ['intern' => $this->intern->id, 'month' => $first->date->format('Y-m')]),
            actionLabel: 'Tinjau',
            sender: $this->intern->user,
        );
    }
}
