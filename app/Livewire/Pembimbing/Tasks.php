<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\Task;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
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
    use WithPagination;

    public string $internId = '';

    public string $status = '';

    // ===== Form pemberian tugas =====
    public bool $showForm = false;

    public string $formInternId = '';

    public string $title = '';

    public string $description = '';

    public string $dueDate = '';

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

    public function openForm(): void
    {
        $this->reset(['formInternId', 'title', 'description', 'dueDate']);
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
            'formInternId' => ['required', Rule::exists('interns', 'id')],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'formInternId' => 'peserta',
            'title' => 'judul tugas',
            'description' => 'keterangan',
            'dueDate' => 'tenggat',
        ];
    }

    public function assignTask(): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->validate();

        Task::create([
            'intern_id' => $this->formInternId,
            'assigned_by' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'source' => 'web',
            'status' => 'pending',
            'due_date' => $this->dueDate !== '' ? $this->dueDate : null,
        ]);

        $this->closeForm();
        $this->dispatch('task-assigned');
    }

    public function markDone(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        Task::whereKey($taskId)->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function reopen(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        Task::whereKey($taskId)->update(['status' => 'pending', 'completed_at' => null]);
    }

    public function delete(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        Task::whereKey($taskId)->delete();
    }

    public function render()
    {
        $tasks = Task::query()
            ->with(['intern.unit', 'assignedBy'])
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByRaw("field(status, 'pending', 'in_progress', 'done')")
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.pembimbing.tasks', [
            'tasks' => $tasks,
            'interns' => Intern::orderBy('nama')->get(['id', 'nama']),
            'totalTasks' => Task::count(),
            'pendingTasks' => Task::where('status', '!=', 'done')->count(),
            'doneTasks' => Task::where('status', 'done')->count(),
        ]);
    }
}
