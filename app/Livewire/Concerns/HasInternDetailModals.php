<?php

namespace App\Livewire\Concerns;

use App\Models\Intern;
use App\Models\Journal;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar kartu peserta magang + modal jurnal/tugas lengkap per peserta (read-only).
 * Dipakai halaman Intern mentor/pembimbing dan Dashboard pimpinan. Komponen yang
 * memakai trait ini wajib mengimplementasikan canViewIntern() untuk membatasi
 * peserta yang detailnya boleh dibuka oleh user yang sedang login.
 */
trait HasInternDetailModals
{
    /** Id peserta yang jurnal/tugas lengkapnya sedang dibuka lewat modal (null = tertutup). */
    public ?int $viewingJournalsFor = null;

    public ?int $viewingTasksFor = null;

    abstract protected function canViewIntern(int $internId): bool;

    public function openJournals(int $internId): void
    {
        if ($this->canViewIntern($internId)) {
            $this->viewingJournalsFor = $internId;
        }
    }

    public function closeJournals(): void
    {
        $this->viewingJournalsFor = null;
    }

    public function openTasks(int $internId): void
    {
        if ($this->canViewIntern($internId)) {
            $this->viewingTasksFor = $internId;
        }
    }

    public function closeTasks(): void
    {
        $this->viewingTasksFor = null;
    }

    /** Query peserta dengan relasi & rekap hitungan yang dibutuhkan partial intern-card. */
    protected function internCardsQuery(string $search): Builder
    {
        return Intern::query()
            ->with(['institusi', 'unit'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhereHas('institusi', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
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
            ->orderBy('nama');
    }

    /** Data untuk partial intern-detail-modals. */
    protected function internDetailModalData(): array
    {
        $data = [
            'viewingJournalsIntern' => null,
            'journalsList' => null,
            'viewingTasksIntern' => null,
            'tasksList' => null,
        ];

        if ($this->viewingJournalsFor && $this->canViewIntern($this->viewingJournalsFor)) {
            $data['viewingJournalsIntern'] = Intern::find($this->viewingJournalsFor);
            $data['journalsList'] = Journal::where('intern_id', $this->viewingJournalsFor)
                ->with('attachments')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();
        }

        if ($this->viewingTasksFor && $this->canViewIntern($this->viewingTasksFor)) {
            $data['viewingTasksIntern'] = Intern::find($this->viewingTasksFor);
            $data['tasksList'] = Task::where('intern_id', $this->viewingTasksFor)
                ->with(['assignedBy', 'completionPhotos'])
                ->orderByDesc('created_at')
                ->get();
        }

        return $data;
    }
}
