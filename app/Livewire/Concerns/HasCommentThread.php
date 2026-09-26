<?php

namespace App\Livewire\Concerns;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommentPosted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Utas komentar dua arah untuk jurnal & tugas. Komponen yang memakai trait ini
 * wajib mengimplementasikan resolveCommentable() untuk membatasi data yang boleh
 * dikomentari oleh user yang sedang login.
 */
trait HasCommentThread
{
    /** Draft komentar per baris, key = id model (jurnal/tugas). */
    public array $commentDrafts = [];

    /**
     * Kembalikan model jurnal/tugas untuk (type, id) HANYA bila user login berhak
     * mengaksesnya; selain itu null.
     */
    abstract protected function resolveCommentable(string $type, int $id): ?Model;

    public function addComment(string $type, int $id): void
    {
        if (! in_array($type, ['journal', 'task'], true)) {
            return;
        }

        $model = $this->resolveCommentable($type, $id);

        if (! $model) {
            return;
        }

        $body = trim((string) ($this->commentDrafts[$id] ?? ''));

        $this->validate([
            "commentDrafts.$id" => ['required', 'string', 'max:2000'],
        ], [
            "commentDrafts.$id.required" => 'Komentar tidak boleh kosong.',
            "commentDrafts.$id.max" => 'Komentar maksimal 2000 karakter.',
        ]);

        $comment = $model->comments()->create([
            'user_id' => auth()->id(),
            'body' => $body,
        ]);

        unset($this->commentDrafts[$id]);

        // Notifikasi push tidak boleh menggagalkan komentar yang sudah tersimpan.
        rescue(fn () => $this->notifyCommentRecipients($comment, $model));
    }

    /**
     * Kirim notifikasi push komentar baru ke semua pihak di utas ini — intern pemilik,
     * pembimbing & mentor intern, pemberi tugas (khusus tugas), dan siapa pun yang pernah
     * berkomentar di utas ini — kecuali penulis komentarnya sendiri.
     */
    protected function notifyCommentRecipients(Comment $comment, Model $model): void
    {
        $intern = $model->intern;

        $recipientIds = collect([
            $intern?->user_id,
            $intern?->pembimbing_id,
            $intern?->mentor_id,
            $model instanceof Task ? $model->assigned_by : null,
        ])
            ->merge($model->comments()->pluck('user_id'))
            ->filter()
            ->unique()
            ->reject(fn ($userId) => $userId === $comment->user_id);

        if ($recipientIds->isEmpty()) {
            return;
        }

        Notification::send(
            User::whereIn('id', $recipientIds)->get(),
            new CommentPosted($comment->setRelation('author', auth()->user()), $model),
        );
    }

    /** Komentar cuma boleh dihapus oleh yang menulisnya sendiri — bukan staff/admin lain. */
    public function deleteComment(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment || $comment->user_id !== auth()->id()) {
            return;
        }

        $comment->delete();
    }
}
