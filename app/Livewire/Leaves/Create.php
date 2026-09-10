<?php

namespace App\Livewire\Leaves;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form pengajuan izin/sakit — dipakai sebagai modal di halaman Izin milik intern.
 * Buka lewat event Livewire: $dispatch('open-leave-modal').
 */
class Create extends Component
{
    use WithFileUploads;

    public bool $show = false;

    public bool $hasIntern = true;

    public string $type = 'izin';

    public string $startDate = '';

    public string $endDate = '';

    public string $reason = '';

    public $attachment = null;

    public function mount(): void
    {
        $this->hasIntern = (bool) auth()->user()->intern;
    }

    #[On('open-leave-modal')]
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
        $this->reset(['reason', 'attachment']);
        $this->resetValidation();
        $this->type = 'izin';
        $this->startDate = Carbon::today()->toDateString();
        $this->endDate = Carbon::today()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['izin', 'sakit'])],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'type' => 'jenis izin',
            'startDate' => 'tanggal mulai',
            'endDate' => 'tanggal selesai',
            'reason' => 'alasan',
            'attachment' => 'lampiran',
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

        $path = $this->attachment ? $this->attachment->store('leave-attachments', 'public') : null;

        $intern->leaveRequests()->create([
            'type' => $this->type,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'reason' => $this->reason,
            'attachment_path' => $path,
            'status' => 'pending',
        ]);

        $this->close();
        $this->dispatch('leave-saved');
    }

    public function render()
    {
        return view('livewire.leaves.create');
    }
}
