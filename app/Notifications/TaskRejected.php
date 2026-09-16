<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke pembimbing (User pemberi tugas) saat intern menolak tugas
 * (App\Livewire\Tasks\Index::confirmReject()) — mis. belum bisa mengerjakan
 * atau ada urusan lain.
 */
class TaskRejected extends Notification
{
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

        return (new WebPushMessage)
            ->title('Tugas ditolak oleh ' . $internName)
            ->body($this->task->title . ($this->task->rejection_reason ? ': ' . $this->task->rejection_reason : ''))
            ->icon('/images/syifa-logo.png')
            ->data(['url' => route('pembimbing.tasks')]);
    }
}
