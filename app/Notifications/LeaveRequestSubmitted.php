<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke setiap User berperan Pembimbing saat seorang intern mengajukan
 * izin/sakit baru (App\Livewire\Leaves\Create::save()). Pengajuan izin tidak
 * terikat ke satu pembimbing tertentu (siapa pun pembimbing bisa meninjau di
 * /izin-intern), jadi notifikasi ini dikirim ke semua pembimbing sekaligus.
 */
class LeaveRequestSubmitted extends Notification
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
        $internName = $this->leaveRequest->intern?->nama ?? 'Peserta magang';
        $type = $this->leaveRequest->type === 'sakit' ? 'Sakit' : 'Izin';

        return (new WebPushMessage)
            ->title("Pengajuan {$type} baru dari {$internName}")
            ->body($this->leaveRequest->reason)
            ->icon('/images/syifa-logo.png')
            ->data(['url' => route('pembimbing.leaves')]);
    }
}
