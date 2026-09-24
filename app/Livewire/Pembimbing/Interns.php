<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\Journal;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar SEMUA peserta magang yang terlihat oleh pembimbing/mentor — sekolah/institusi
 * asal, periode magang, dan rekap ringkas jurnal/tugas/presensi/izin per peserta.
 * Halaman ini murni untuk DILIHAT (tidak ada aksi ubah data).
 */
#[Layout('components.layouts.app')]
class Interns extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /** Id peserta yang jurnal/tugas lengkapnya sedang dibuka lewat modal (null = tertutup). */
    public ?int $viewingJournalsFor = null;

    public ?int $viewingTasksFor = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    protected function visibleInternIds(): array
    {
        return auth()->user()->visibleInterns()->pluck('id')->all();
    }

    public function openJournals(int $internId): void
    {
        if (! in_array($internId, $this->visibleInternIds(), true)) {
            return;
        }

        $this->viewingJournalsFor = $internId;
    }

    public function closeJournals(): void
    {
        $this->viewingJournalsFor = null;
    }

    public function openTasks(int $internId): void
    {
        if (! in_array($internId, $this->visibleInternIds(), true)) {
            return;
        }

        $this->viewingTasksFor = $internId;
    }

    public function closeTasks(): void
    {
        $this->viewingTasksFor = null;
    }

    public function render()
    {
        $internIds = $this->visibleInternIds();

        $viewingJournalsIntern = null;
        $journalsList = null;

        if ($this->viewingJournalsFor && in_array($this->viewingJournalsFor, $internIds, true)) {
            $viewingJournalsIntern = Intern::find($this->viewingJournalsFor);
            $journalsList = Journal::where('intern_id', $this->viewingJournalsFor)
                ->with('attachments')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();
        }

        $viewingTasksIntern = null;
        $tasksList = null;

        if ($this->viewingTasksFor && in_array($this->viewingTasksFor, $internIds, true)) {
            $viewingTasksIntern = Intern::find($this->viewingTasksFor);
            $tasksList = Task::where('intern_id', $this->viewingTasksFor)
                ->with(['assignedBy', 'completionPhotos'])
                ->orderByDesc('created_at')
                ->get();
        }

        $interns = Intern::query()
            ->whereIn('id', $internIds)
            ->with(['institusi', 'unit'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhereHas('institusi', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
            }))
            ->withCount([
                'journals as jurnal_count',
                'tasks as tugas_total_count',
                'tasks as tugas_selesai_count' => fn ($q) => $q->where('status', 'done'),
                'leaveRequests as izin_total_count',
                'leaveRequests as izin_approved_count' => fn ($q) => $q->where('status', 'approved'),
                'attendanceRecords as presensi_hadir_count' => fn ($q) => $q->where('status', 'present'),
                'attendanceRecords as presensi_telat_count' => fn ($q) => $q->where('status', 'late'),
                'attendanceRecords as presensi_absen_count' => fn ($q) => $q->where('status', 'absent'),
            ])
            ->orderBy('nama')
            ->paginate(12);

        return view('livewire.pembimbing.interns', [
            'interns' => $interns,
            'totalInterns' => count($internIds),
            'viewingJournalsIntern' => $viewingJournalsIntern,
            'journalsList' => $journalsList,
            'viewingTasksIntern' => $viewingTasksIntern,
            'tasksList' => $tasksList,
        ]);
    }
}
