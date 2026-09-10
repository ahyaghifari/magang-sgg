<?php

namespace App\Livewire\Tasks;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Form catat tugas — dipakai sebagai modal di halaman Tugas milik intern.
 * Dipakai untuk mencatat tugas yang disampaikan pembimbing secara lisan
 * (source = 'verbal'). Buka lewat event Livewire: $dispatch('open-task-modal').
 */
class Create extends Component
{
    public bool $show = false;

    public bool $hasIntern = true;

    public string $title = '';

    public string $description = '';

    public string $assignedBy = '';

    public string $dueDate = '';

    public function mount(): void
    {
        $this->hasIntern = (bool) auth()->user()->intern;
    }

    #[On('open-task-modal')]
    public function open(): void
    {
        $this->resetForm();
        $this->hasIntern = (bool) auth()->user()->intern;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['title', 'description', 'assignedBy', 'dueDate']);
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assignedBy' => ['nullable', 'exists:users,id'],
            'dueDate' => ['nullable', 'date'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'judul tugas',
            'description' => 'keterangan',
            'assignedBy' => 'pembimbing pemberi tugas',
            'dueDate' => 'tenggat',
        ];
    }

    public function save()
    {
        $intern = auth()->user()->intern;

        if (! $intern) {
            $this->hasIntern = false;

            return;
        }

        $this->validate();

        $intern->tasks()->create([
            'assigned_by' => $this->assignedBy !== '' ? $this->assignedBy : null,
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'source' => 'verbal',
            'status' => 'pending',
            'due_date' => $this->dueDate !== '' ? $this->dueDate : null,
        ]);

        $this->close();
        $this->dispatch('task-saved');
    }

    public function render()
    {
        return view('livewire.tasks.create', [
            'pembimbings' => User::where('role', UserRole::Pembimbing)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
