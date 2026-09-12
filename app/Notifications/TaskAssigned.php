<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke User milik seorang Intern saat pembimbing memberi tugas baru
 * lewat web (App\Livewire\Pembimbing\Tasks::assignTask()).
 */
class TaskAssigned extends Notification
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
        return (new WebPushMessage)
            ->title('Tugas baru dari pembimbing')
            ->body($this->task->title)
            ->icon('/images/syifa-logo.png')
            ->data(['url' => route('tasks.index')]);
    }
}
