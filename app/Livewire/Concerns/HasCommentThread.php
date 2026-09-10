<?php

namespace App\Livewire\Concerns;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;

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

        $model->comments()->create([
            'user_id' => auth()->id(),
            'body' => $body,
        ]);

        unset($this->commentDrafts[$id]);
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment) {
            return;
        }

        if ($comment->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            return;
        }

        $comment->delete();
    }
}
