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

        return Task::whereKey($id)->whereIn('intern_id', $this->manageableInternIds())->first();
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
            'formInternId' => ['required', Rule::in($this->manageableInternIds())],
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

        $task = Task::whereKey($taskId)->whereIn('intern_id', $this->manageableInternIds())->first();

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

        $task = Task::whereKey($this->editingTaskId)->whereIn('intern_id', $this->manageableInternIds())->first();

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

        Task::whereKey($taskId)->whereIn('intern_id', $this->manageableInternIds())
            ->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function reopen(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        Task::whereKey($taskId)->whereIn('intern_id', $this->manageableInternIds())
            ->update(['status' => 'pending', 'completed_at' => null]);
    }

    public function delete(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        Task::whereKey($taskId)->whereIn('intern_id', $this->manageableInternIds())->delete();
    }

    public function render()
    {
        $internIds = $this->visibleInternIds();

        $tasks = Task::query()
            ->whereIn('intern_id', $internIds)
            ->with(['intern.unit', 'assignedBy', 'comments.author', 'completionPhotos'])
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByRaw("field(status, 'pending', 'in_progress', 'done')")
            ->orderByDesc('created_at')
            ->paginate(10);

        $manageableInternIds = $this->manageableInternIds();

        return view('livewire.pembimbing.tasks', [
            'tasks' => $tasks,
            'interns' => Intern::whereIn('id', $internIds)->orderBy('nama')->get(['id', 'nama']),
            // Khusus untuk pilihan peserta di form "Beri Tugas" — sengaja tetap sempit
            // (manageableInternIds()) meski daftar/filter tugas di atas sudah melebar untuk
            // Mentor, karena pemberian tugas baru cuma boleh ke intern yang dibimbing/dimentori.
            'manageableInterns' => Intern::whereIn('id', $manageableInternIds)->orderBy('nama')->get(['id', 'nama']),
            // Dipakai buat sembunyikan tombol kelola (tandai selesai/edit/hapus/komentar) di
            // tugas milik intern yang cuma boleh DILIHAT (Mentor) bukan mentee sendiri.
            'manageableInternIds' => $manageableInternIds,
            'totalTasks' => Task::whereIn('intern_id', $internIds)->count(),
            'pendingTasks' => Task::whereIn('intern_id', $internIds)->where('status', '!=', 'done')->count(),
            'doneTasks' => Task::whereIn('intern_id', $internIds)->where('status', 'done')->count(),
        ]);
    }
}
