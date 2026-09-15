<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke User milik intern pengaju saat pembimbing menyetujui/menolak
 * pengajuan izin/sakitnya (App\Livewire\Pembimbing\Leaves::approve()/reject()).
 */
class LeaveRequestReviewed extends Notification
{
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

        return (new WebPushMessage)
            ->title($isApproved ? 'Pengajuan izin disetujui' : 'Pengajuan izin ditolak')
            ->body($isApproved
                ? 'Pengajuan izin/sakitmu sudah disetujui pembimbing.'
                : 'Pengajuan izin/sakitmu ditolak.' . ($this->leaveRequest->review_note ? ' Catatan: ' . $this->leaveRequest->review_note : ''))
            ->icon('/images/syifa-logo.png')
            ->data(['url' => route('leaves.index')]);
    }
}
