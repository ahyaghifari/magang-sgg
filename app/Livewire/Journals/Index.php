<?php

namespace App\Livewire\Journals;

use App\Livewire\Concerns\HasCommentThread;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use HasCommentThread, WithPagination;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[On('journal-saved')]
    public function onJournalSaved(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetDateFilter(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
        $this->resetPage();
    }

    protected function resolveCommentable(string $type, int $id): ?Model
    {
        if ($type !== 'journal') {
            return null;
        }

        return auth()->user()->intern?->journals()->whereKey($id)->first();
    }

    public function render()
    {
        $intern = auth()->user()->intern;

        $journals = $intern
            ? $intern->journals()
                ->with(['attachments', 'comments.author'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
                ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(10)
            : null;

        return view('livewire.journals.index', [
            'intern' => $intern,
            'journals' => $journals,
        ]);
    }
}
