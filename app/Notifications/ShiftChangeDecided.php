<?php

namespace App\Notifications;

use App\Models\ShiftChangeRequest;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke User milik intern saat Mentor-nya menyetujui/menolak pengajuan perubahan
 * jadwal shift (App\Services\Shift\ShiftChangeRequestService::decide()).
 */
class ShiftChangeDecided extends Notification
{
    use BuildsWebPush;

    public function __construct(private readonly ShiftChangeRequest $request)
    {
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
        $approved = $this->request->status === ShiftChangeRequest::STATUS_APPROVED;
        $mentor = $this->request->decider?->name;

        return $this->pushMessage(
            title: $approved ? 'Pengajuan ganti shift disetujui' : 'Pengajuan ganti shift ditolak',
            lines: [
                'Tanggal: ' . $this->request->date->locale('id')->translatedFormat('D, d M Y'),
                'Shift: ' . $this->request->changeLabel(),
                ($approved ? 'Disetujui' : 'Ditolak') . ($mentor ? ' oleh ' . $mentor : '') . '.',
                $this->request->decision_note ? 'Catatan: ' . $this->request->decision_note : null,
            ],
            url: route('shifts.index', ['month' => $this->request->date->format('Y-m')]),
            actionLabel: 'Lihat Jadwal',
        );
    }
}
