<?php

namespace App\Livewire\Pembimbing;

use App\Livewire\Concerns\HasCommentThread;
use App\Models\Intern;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pemberian & daftar tugas untuk peserta magang — untuk peran pembimbing (dan admin).
 * Pembimbing bisa langsung memberi tugas lewat web (source = 'web') dan memantau
 * status tugas yang dicatat sendiri oleh intern (source = 'verbal').
 */
#[Layout('components.layouts.app')]
class Tasks extends Component
{
    use HasCommentThread, WithPagination;

    public string $internId = '';

    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    // ===== Form pemberian tugas =====
    public bool $showForm = false;

    public string $formInternId = '';

    public string $title = '';

    public string $description = '';

    public string $dueDate = '';

    public string $dueTime = '';

    // ===== Form edit tugas =====
    public ?int $editingTaskId = null;

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editDueDate = '';

    public string $editDueTime = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['internId', 'status', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function resetDateFilter(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
        $this->resetPage();
    }

    protected function resolveCommentable(string $type, int $id): ?Model
    {
        if ($type !== 'task' || ! $this->allowed()) {
            return null;
        }

        return $this->manageableTasksQuery()->whereKey($id)->first();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    /**
     * Id intern yang boleh DILIHAT user yang sedang login — dipakai untuk daftar/filter
     * tugas (lihat User::visibleInterns(): Mentor sengaja melihat semua intern di sini).
     */
    protected function visibleInternIds(): array
    {
        return auth()->user()->visibleInterns()->pluck('id')->all();
    }

    /**
     * Id intern yang boleh DIKELOLA user yang sedang login — dipakai untuk pemberian
     * tugas baru dan setiap aksi yang mengubah tugas (edit/hapus/tandai selesai/komentar).
     * SELALU sempit ke intern yang memang dibimbing/dimentori (lihat User::manageableInterns()),
     * beda dari visibleInternIds() yang melebar untuk Mentor.
     */
    protected function manageableInternIds(): array
    {
        return auth()->user()->manageableInterns()->pluck('id')->all();
    }

    /**
     * Tugas yang boleh DIKELOLA (edit/hapus/tandai selesai/komentar) user yang sedang login:
     * milik intern yang memang dibimbing/dimentori, ATAU tugas yang dia SENDIRI berikan
     * (bisa lintas pembimbing, ke intern bukan mentee-nya) — orang yang membuat tugas
     * tetap boleh mengurus tugas buatannya sendiri.
     */
    protected function manageableTasksQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $manageableInternIds = $this->manageableInternIds();
        $myId = auth()->id();

        return Task::query()->where(
            fn ($q) => $q->whereIn('intern_id', $manageableInternIds)->orWhere('assigned_by', $myId)
        );
    }

    public function openForm(): void
    {
        $this->reset(['formInternId', 'title', 'description', 'dueDate', 'dueTime']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    protected function rules(): array
    {
        return [
            // Sengaja TIDAK dibatasi ke manageableInternIds() — pemberian tugas boleh lintas
            // pembimbing, ke intern siapa pun di sistem (lihat allInterns() di render()).
            'formInternId' => ['required', 'integer', Rule::exists('interns', 'id')],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'dueDate' => ['nullable', 'date'],
            'dueTime' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'formInternId' => 'peserta',
            'title' => 'judul tugas',
            'description' => 'keterangan',
            'dueDate' => 'tanggal tenggat',
            'dueTime' => 'jam tenggat',
        ];
    }

    /**
     * Info pembimbing & mentor ASLI dari peserta yang sedang dipilih di form "Beri Tugas" —
     * dipakai untuk kotak keterangan di antara field Peserta dan Judul Tugas, supaya kalau
     * yang memberi tugas BUKAN pembimbing/mentor asli peserta itu (fitur lintas pembimbing),
     * tetap jelas siapa pembimbing/mentor sebenarnya.
     */
    public function getSelectedInternInfoProperty(): ?array
    {
        if ($this->formInternId === '') {
            return null;
        }

        $intern = Intern::with(['pembimbing', 'mentor'])->find($this->formInternId);

        if (! $intern) {
            return null;
        }

        $myId = auth()->id();

        return [
            'pembimbing' => $intern->pembimbing?->name,
            'mentor' => $intern->mentor?->name,
            'isMine' => $intern->pembimbing_id === $myId || $intern->mentor_id === $myId,
        ];
    }

    /** Gabungkan input tanggal + jam terpisah jadi satu nilai datetime (atau null kalau tanggal kosong). */
    protected function combineDueDateTime(string $date, string $time): ?string
    {
        if ($date === '') {
            return null;
        }

        return $date . ' ' . ($time !== '' ? $time : '00:00');
    }

    public function assignTask(): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->validate();

        $task = Task::create([
            'intern_id' => $this->formInternId,
            'assigned_by' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'source' => 'web',
            'status' => 'pending',
            'due_date' => $this->combineDueDateTime($this->dueDate, $this->dueTime),
        ]);

        $task->intern->user?->notify(new TaskAssigned($task));

        $this->closeForm();
        $this->dispatch('task-assigned');
    }

    /** Buka modal edit tugas — dipakai a.l. saat intern menolak tugas dan pembimbing mau menyesuaikannya. */
    public function openEditTask(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $task = $this->manageableTasksQuery()->whereKey($taskId)->first();

        if (! $task) {
            return;
        }

        $this->editingTaskId = $taskId;
        $this->editTitle = $task->title;
        $this->editDescription = $task->description ?? '';
        $this->editDueDate = $task->due_date?->format('Y-m-d') ?? '';
        $this->editDueTime = $task->due_date?->format('H:i') ?? '';
        $this->resetValidation();
    }

    public function closeEditTask(): void
    {
        $this->reset(['editingTaskId', 'editTitle', 'editDescription', 'editDueDate', 'editDueTime']);
        $this->resetValidation();
    }

    public function confirmEditTask(): void
    {
        if (! $this->allowed() || ! $this->editingTaskId) {
            return;
        }

        $task = $this->manageableTasksQuery()->whereKey($this->editingTaskId)->first();

        if (! $task) {
            return;
        }

        $this->validate([
            'editTitle' => ['required', 'string', 'min:3', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
            'editDueDate' => ['nullable', 'date'],
            'editDueTime' => ['nullable', 'date_format:H:i'],
        ], [], [
            'editTitle' => 'judul tugas',
            'editDescription' => 'keterangan',
            'editDueDate' => 'tanggal tenggat',
            'editDueTime' => 'jam tenggat',
        ]);

        $task->update([
            'title' => $this->editTitle,
            'description' => $this->editDescription !== '' ? $this->editDescription : null,
            'due_date' => $this->combineDueDateTime($this->editDueDate, $this->editDueTime),
        ]);

        $this->closeEditTask();
    }

    public function markDone(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)
            ->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function reopen(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)
            ->update(['status' => 'pending', 'completed_at' => null]);
    }

    public function delete(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)->delete();
    }

    public function render()
    {
        $internIds = $this->visibleInternIds();

        $base = Task::query()
            ->whereIn('intern_id', $internIds)
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        $tasks = (clone $base)
            ->with(['intern.unit', 'assignedBy', 'comments.author', 'completionPhotos'])
            ->orderByRaw("field(status, 'pending', 'in_progress', 'done')")
            ->orderByDesc('created_at')
            ->paginate(10);

        $manageableInternIds = $this->manageableInternIds();

        return view('livewire.pembimbing.tasks', [
            'tasks' => $tasks,
            'interns' => Intern::whereIn('id', $internIds)->orderBy('nama')->get(['id', 'nama']),
            // Pilihan peserta di form "Beri Tugas" — SEMUA intern di sistem, lintas pembimbing
            // (bukan cuma mentee sendiri).
            'allInterns' => Intern::with('unit')->orderBy('nama')->get(['id', 'nama', 'unit_id']),
            // Dipakai buat sembunyikan tombol kelola (tandai selesai/edit/hapus/komentar) di
            // tugas milik intern yang cuma boleh DILIHAT (Mentor) bukan mentee sendiri — kecuali
            // tugas itu memang dia sendiri yang berikan (lihat manageableTasksQuery()).
            'manageableInternIds' => $manageableInternIds,
            'totalTasks' => (clone $base)->count(),
            'pendingTasks' => (clone $base)->where('status', '!=', 'done')->count(),
            'doneTasks' => (clone $base)->where('status', 'done')->count(),
        ]);
    }
}
