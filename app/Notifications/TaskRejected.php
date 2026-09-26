<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke pembimbing (User pemberi tugas) saat intern menunda tugas
 * (App\Livewire\Tasks\Index::confirmReject()) — mis. belum bisa mengerjakan
 * atau ada urusan lain.
 */
class TaskRejected extends Notification
{
    use BuildsWebPush;

    public function __construct(private readonly Task $task)
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
        $internName = $this->task->intern?->nama ?? 'Peserta magang';

        return $this->pushMessage(
            title: '⏸️ ' . $internName . ' menunda tugas',
            lines: [
                '📋 ' . $this->task->title,
                $this->task->rejection_reason ? '💬 "' . $this->task->rejection_reason . '"' : null,
            ],
            url: route('pembimbing.tasks'),
            actionLabel: 'Lihat Tugas',
            sender: $this->task->intern?->user,
        );
    }
}
