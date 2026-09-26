<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Support\Carbon;
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
        $internName = $this->leaveRequest->intern?->nama ?? 'Peserta magang';
        $type = $this->leaveRequest->type === 'sakit' ? 'Sakit' : 'Izin';

        $start = $this->leaveRequest->start_date ? Carbon::parse($this->leaveRequest->start_date) : null;
        $end = $this->leaveRequest->end_date ? Carbon::parse($this->leaveRequest->end_date) : null;

        return $this->pushMessage(
            title: ($type === 'Sakit' ? '🤒' : '📝') . " Pengajuan {$type} dari {$internName}",
            lines: [
                $start ? '📅 ' . $start->translatedFormat('d M Y') . ($end && ! $end->isSameDay($start) ? ' – ' . $end->translatedFormat('d M Y') : '') : null,
                '💬 "' . $this->leaveRequest->reason . '"',
            ],
            url: route('pembimbing.leaves'),
            actionLabel: 'Tinjau',
            sender: $this->leaveRequest->intern?->user,
        );
    }
}
