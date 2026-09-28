<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke User milik seorang Intern saat pembimbing memberi tugas baru
 * lewat web (App\Livewire\Pembimbing\Tasks::assignTask()).
 */
class TaskAssigned extends Notification
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
        return $this->pushMessage(
            title: 'Tugas baru dari ' . ($this->task->assignedBy?->name ?? 'pembimbing'),
            lines: [
                $this->task->title,
                $this->task->description ? Str::limit($this->task->description, 100) : null,
                'Tenggat: ' . ($this->task->due_date
                    ? $this->task->due_date->translatedFormat('l, d M Y') . ' · ' . $this->task->due_date->format('H:i')
                    : 'tidak ada'),
            ],
            url: route('tasks.index'),
            actionLabel: 'Lihat Tugas',
            sender: $this->task->assignedBy,
        );
    }
}
