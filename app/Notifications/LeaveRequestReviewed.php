<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke User milik intern pengaju saat pembimbing menyetujui/menolak
 * pengajuan izin/sakitnya (App\Livewire\Pembimbing\Leaves::approve()/reject()).
 */
class LeaveRequestReviewed extends Notification
{
    use BuildsWebPush;

    public function __construct(private readonly LeaveRequest $leaveRequest)
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
        $isApproved = $this->leaveRequest->status === 'approved';

        $reviewer = $this->leaveRequest->reviewer?->name;

        return $this->pushMessage(
            title: $isApproved ? '✅ Pengajuan izin disetujui' : '❌ Pengajuan izin ditolak',
            lines: [
                ($isApproved ? 'Pengajuan izin/sakitmu sudah disetujui' : 'Pengajuan izin/sakitmu ditolak')
                    . ($reviewer ? ' oleh ' . $reviewer : '') . '.',
                $this->leaveRequest->review_note ? '💬 "' . $this->leaveRequest->review_note . '"' : null,
            ],
            url: route('leaves.index'),
            actionLabel: 'Lihat Izin',
        );
    }
}
