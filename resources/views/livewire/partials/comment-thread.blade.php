{{--
    Utas komentar dua arah untuk jurnal / tugas.
    Wajib di-include dengan: ['type' => 'journal'|'task', 'model' => $journal|$task]
    Komponen induk harus memakai App\Livewire\Concerns\HasCommentThread.
--}}
@php($__authId = auth()->id())
<div style="margin-top:0.9rem; padding-top:0.75rem; border-top:1px solid var(--border-soft);"
     wire:key="comments-{{ $type }}-{{ $model->id }}">
    <p class="text-sm" style="font-weight:600; color:var(--text-muted); margin-bottom:0.5rem;">
        <i class="fa-regular fa-comments" style="margin-right:0.35rem;"></i>
        Diskusi
        @if ($model->comments->isNotEmpty())
            <span style="color:var(--text-faint);">({{ $model->comments->count() }})</span>
        @endif
    </p>

    @foreach ($model->comments as $comment)
        <div wire:key="comment-{{ $comment->id }}"
             style="display:flex; gap:0.6rem; padding:0.55rem 0; border-bottom:1px solid var(--border-soft);">
            <span class="portal-user-avatar" style="flex-shrink:0; width:1.9rem; height:1.9rem; font-size:0.75rem;">
                {{ strtoupper(mb_substr($comment->author?->name ?? '?', 0, 1)) }}
            </span>
            <div style="min-width:0; flex:1;">
                <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                    <span class="text-sm" style="font-weight:700; color:var(--text-heading);">
                        {{ $comment->author?->name ?? 'Pengguna dihapus' }}
                    </span>
                    @if ($comment->author && $comment->author->hasAdminRole())
                        <span class="badge" style="background:#e0e7ff; color:#3730a3;">Admin</span>
                    @elseif ($comment->author && $comment->author->isPembimbing())
                        <span class="badge" style="background:#dcfce7; color:#15803d;">Pembimbing</span>
                    @endif
                    <span class="text-sm" style="color:var(--text-faint);">
                        {{ $comment->created_at?->diffForHumans() }}
                    </span>
                    @if ($comment->user_id === $__authId || auth()->user()->isPortalMentor())
                        <button type="button" wire:click="deleteComment({{ $comment->id }})"
                                wire:confirm="Hapus komentar ini?"
                                class="text-sm" style="color:var(--text-muted); text-decoration:underline; margin-left:auto;">
                            hapus
                        </button>
                    @endif
                </div>
                <p class="text-sm" style="color:var(--text-body); white-space:pre-line; margin-top:0.15rem;">{{ $comment->body }}</p>
            </div>
        </div>
    @endforeach

    <div style="display:flex; gap:0.5rem; margin-top:0.6rem;">
        <textarea wire:model="commentDrafts.{{ $model->id }}" rows="1" class="form-input"
                  style="flex:1; min-height:2.4rem; resize:vertical;"
                  placeholder="Tulis komentar..."></textarea>
        <button type="button" wire:click="addComment('{{ $type }}', {{ $model->id }})"
                class="btn-primary" style="flex-shrink:0; padding:0.5rem 0.85rem;">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
    @error('commentDrafts.' . $model->id)
        <p class="text-sm" style="color:#dc2626; margin-top:0.35rem;">{{ $message }}</p>
    @enderror
</div>
