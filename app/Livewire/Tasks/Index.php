<?php

namespace App\Livewire\Tasks;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Daftar tugas milik intern yang sedang login — tugas yang dicatat sendiri
 * (disampaikan pembimbing secara lisan) maupun yang diberikan pembimbing lewat web.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $status = '';

    /** id tugas yang sedang ditandai selesai (menampilkan modal unggah foto bukti). */
    public ?int $completingTaskId = null;

    public $completionPhoto = null;

    /** Kalau dicentang, tugas yang selesai ini juga dicatat sebagai kegiatan di jurnal harian hari ini. */
    public bool $addToJournal = true;

    public function mount()
    {
        // Bukan intern → arahkan ke portal masing-masing, jangan lempar 403.
        $user = auth()->user();

        if ($user->isPembimbing() || $user->isAdmin()) {
            return $this->redirect(route('pembimbing.tasks'), navigate: true);
        }
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[On('task-saved')]
    public function onTaskSaved(): void
    {
        $this->resetPage();
    }

    public function markStatus(int $taskId, string $status): void
    {
        $intern = auth()->user()->intern;

        if (! $intern || ! in_array($status, ['pending', 'in_progress'], true)) {
            return;
        }

        $task = $intern->tasks()->whereKey($taskId)->first();

        if (! $task) {
            return;
        }

        $task->update([
            'status' => $status,
            'completed_at' => null,
        ]);
    }

    /** Buka modal unggah foto bukti sebelum tugas ditandai selesai. */
    public function openComplete(int $taskId): void
    {
        $intern = auth()->user()->intern;

        if (! $intern || ! $intern->tasks()->whereKey($taskId)->exists()) {
            return;
        }

        $this->completingTaskId = $taskId;
        $this->completionPhoto = null;
        $this->addToJournal = true;
        $this->resetValidation();
    }

    public function closeComplete(): void
    {
        $this->completingTaskId = null;
        $this->completionPhoto = null;
        $this->addToJournal = true;
        $this->resetValidation();
    }

    public function confirmComplete(): void
    {
        $intern = auth()->user()->intern;

        if (! $intern || ! $this->completingTaskId) {
            return;
        }

        $task = $intern->tasks()->whereKey($this->completingTaskId)->first();

        if (! $task) {
            return;
        }

        $this->validate([
            'completionPhoto' => ['required', 'image', 'max:5120'],
        ], [], [
            'completionPhoto' => 'foto bukti',
        ]);

        $path = $this->completionPhoto->store('task-completions', 'public');

        $task->update([
            'status' => 'done',
            'completed_at' => now(),
            'completion_photo_path' => $path,
        ]);

        if ($this->addToJournal) {
            $activity = "Menyelesaikan tugas: {$task->title}";

            if ($task->description) {
                $activity .= "\n\n{$task->description}";
            }

            $journal = $intern->journals()->create([
                'date' => now()->toDateString(),
                'activity' => $activity,
            ]);

            $journal->attachments()->create([
                'type' => 'photo',
                'path' => $path,
                'url' => null,
                'label' => 'Bukti penyelesaian tugas: ' . $task->title,
            ]);

            $this->dispatch('journal-saved');
        }

        $this->closeComplete();
    }

    public function render()
    {
        $intern = auth()->user()->intern;

        $tasks = $intern
            ? $intern->tasks()
                ->with('assignedBy')
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->orderByRaw("field(status, 'pending', 'in_progress', 'done')")
                ->orderByDesc('created_at')
                ->paginate(10)
            : null;

        return view('livewire.tasks.index', [
            'intern' => $intern,
            'tasks' => $tasks,
            'pendingCount' => $intern ? $intern->tasks()->where('status', '!=', 'done')->count() : 0,
        ]);
    }
}
