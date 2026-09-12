<?php

namespace App\Livewire\Leaves;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar pengajuan izin/sakit milik intern yang sedang login,
 * beserta status konfirmasi dari pembimbing.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function mount()
    {
        $user = auth()->user();

        if ($user->isPortalMentor()) {
            return $this->redirect(route('pembimbing.leaves'), navigate: true);
        }
    }

    #[On('leave-saved')]
    public function onLeaveSaved(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $intern = auth()->user()->intern;

        $leaves = $intern
            ? $intern->leaveRequests()
                ->with('reviewer')
                ->orderByDesc('created_at')
                ->paginate(10)
            : null;

        return view('livewire.leaves.index', [
            'intern' => $intern,
            'leaves' => $leaves,
        ]);
    }
}
