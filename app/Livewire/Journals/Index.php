<?php

namespace App\Livewire\Journals;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[On('journal-saved')]
    public function onJournalSaved(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $intern = auth()->user()->intern;

        $journals = $intern
            ? $intern->journals()
                ->with('attachments')
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
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
