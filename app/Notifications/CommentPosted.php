<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Journal;
use App\Models\Task;
use App\Notifications\Concerns\BuildsWebPush;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Dikirim ke semua pihak di sebuah utas Diskusi jurnal/tugas (intern pemilik, pembimbing,
 * mentor, pemberi tugas, dan siapa pun yang pernah ikut berkomentar) saat ada komentar
 * baru — kecuali penulis komentarnya sendiri. Lihat HasCommentThread::notifyCommentRecipients().
 */
class CommentPosted extends Notification
{
    use BuildsWebPush;

    public function __construct(private readonly Comment $comment, private readonly Journal|Task $commentable)
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
        $author = $this->comment->author?->name ?? 'Seseorang';
        $isTask = $this->commentable instanceof Task;
        $isOwner = $this->commentable->intern?->user_id === $notifiable->id;

        $where = $isTask
            ? 'tugas "' . Str::limit($this->commentable->title, 40) . '"'
            : 'jurnal ' . ($isOwner ? 'kamu' : ($this->commentable->intern?->nama ?? 'peserta'))
                . ' ' . $this->commentable->date?->translatedFormat('d M');

        // Intern pemilik dibawa ke halaman miliknya; pembimbing/mentor ke halaman portal mereka.
        $url = match (true) {
            $isTask && $isOwner => route('tasks.index'),
            $isTask => route('pembimbing.tasks'),
            $isOwner => route('journals.index'),
            default => route('pembimbing.activities', [
                'dateFrom' => $this->commentable->date?->toDateString(),
                'dateTo' => $this->commentable->date?->toDateString(),
            ]),
        };

        return $this->pushMessage(
            title: "💬 {$author} berkomentar",
            lines: [
                ($isTask ? '📋 Di ' : '📖 Di ') . $where,
                '"' . Str::limit($this->comment->body, 140) . '"',
            ],
            url: $url,
            actionLabel: 'Balas',
            sender: $this->comment->author,
            // Satu utas = satu notifikasi di HP, supaya panel notifikasi tidak penuh oleh satu diskusi.
            tag: ($isTask ? 'task' : 'journal') . '-comments-' . $this->commentable->id,
        );
    }
}
