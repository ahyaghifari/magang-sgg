<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestReviewed;
use Illuminate\Support\Facades\Storage;
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

        return $user && $user->canReviewLeaveRequests();
    }

    /** Id intern yang boleh dilihat/dikelola user yang sedang login (lihat User::visibleInterns()). */
    protected function visibleInternIds(): array
    {
        return auth()->user()->visibleInterns()->pluck('id')->all();
    }

    public function approve(int $leaveId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $leave = LeaveRequest::whereKey($leaveId)->whereIn('intern_id', $this->visibleInternIds())->first();

        if (! $leave) {
            return;
        }

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        $leave->intern?->user?->notify(new LeaveRequestReviewed($leave));

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

        $leave = LeaveRequest::whereKey($leaveId)->whereIn('intern_id', $this->visibleInternIds())->first();

        if (! $leave) {
            return;
        }

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->reviewNote !== '' ? $this->reviewNote : null,
        ]);

        $leave->intern?->user?->notify(new LeaveRequestReviewed($leave));

        $this->rejecting = null;
        $this->reviewNote = '';
    }

    /** Hapus pengajuan izin yang sudah dikonfirmasi (disetujui/ditolak) dan tidak diperlukan lagi. */
    public function delete(int $leaveId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $leave = LeaveRequest::whereKey($leaveId)
            ->whereIn('intern_id', $this->visibleInternIds())
            ->where('status', '!=', 'pending')
            ->first();

        if (! $leave) {
            return;
        }

        if ($leave->attachment_path) {
            Storage::disk('public')->delete($leave->attachment_path);
        }

        $leave->delete();
    }

    public function render()
    {
        $internIds = $this->visibleInternIds();

        $leaves = LeaveRequest::query()
            ->whereIn('intern_id', $internIds)
            ->with(['intern.unit', 'reviewer'])
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByRaw("field(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.pembimbing.leaves', [
            'leaves' => $leaves,
            'interns' => Intern::whereIn('id', $internIds)->orderBy('nama')->get(['id', 'nama']),
            'pendingCount' => LeaveRequest::whereIn('intern_id', $internIds)->where('status', 'pending')->count(),
        ]);
    }
}
