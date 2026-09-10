<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\LeaveRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Konfirmasi pengajuan izin/sakit peserta magang — untuk peran pembimbing (dan admin).
 */
#[Layout('components.layouts.app')]
class Leaves extends Component
{
    use WithPagination;

    public string $internId = '';

    public string $status = '';

    /** id pengajuan yang sedang ditolak (untuk menampilkan input catatan). */
    public ?int $rejecting = null;

    public string $reviewNote = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['internId', 'status'], true)) {
            $this->resetPage();
        }
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPembimbing() || $user->isAdmin());
    }

    public function approve(int $leaveId): void
    {
        if (! $this->allowed()) {
            return;
        }

        LeaveRequest::whereKey($leaveId)->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        $this->rejecting = null;
    }

    public function startReject(int $leaveId): void
    {
        $this->rejecting = $leaveId;
        $this->reviewNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejecting = null;
        $this->reviewNote = '';
    }

    public function reject(int $leaveId): void
    {
        if (! $this->allowed()) {
            return;
        }

        LeaveRequest::whereKey($leaveId)->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->reviewNote !== '' ? $this->reviewNote : null,
        ]);

        $this->rejecting = null;
        $this->reviewNote = '';
    }

    public function render()
    {
        $leaves = LeaveRequest::query()
            ->with(['intern.unit', 'reviewer'])
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByRaw("field(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.pembimbing.leaves', [
            'leaves' => $leaves,
            'interns' => Intern::orderBy('nama')->get(['id', 'nama']),
            'pendingCount' => LeaveRequest::where('status', 'pending')->count(),
        ]);
    }
}
