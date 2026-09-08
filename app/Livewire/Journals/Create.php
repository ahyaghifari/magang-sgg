<?php

namespace App\Livewire\Journals;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Form isi jurnal — dipakai sebagai modal (embed) di Beranda & halaman Semua Jurnal.
 * Buka lewat event Livewire: $dispatch('open-journal-modal').
 */
class Create extends Component
{
    use WithFileUploads;

    public bool $show = false;

    public bool $hasIntern = true;

    /** Kecilkan (kompres) foto di sisi klien sebelum diunggah. Dibaca oleh JS lewat $wire.compressImages. */
    public bool $compressImages = true;

    public string $date = '';

    public string $activity = '';

    /**
     * Lampiran dinamis.
     *
     * @var array<int, array{type:string, file:mixed, url:string, label:string}>
     */
    public array $items = [];

    public function mount(): void
    {
        $this->date = Carbon::today()->toDateString();
        $this->hasIntern = (bool) auth()->user()->intern;
    }

    #[On('open-journal-modal')]
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
        $this->reset(['activity', 'items']);
        $this->resetValidation();
        $this->date = Carbon::today()->toDateString();
    }

    public function addItem(string $type, bool $capture = false): void
    {
        // $capture=true → input file dibuka langsung ke kamera di HP (atribut capture).
        $this->items[] = ['type' => $type, 'file' => null, 'url' => '', 'label' => '', 'capture' => $capture];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'activity' => ['required', 'string', 'min:5'],
            'items' => ['array'],
            'items.*.type' => ['required', Rule::in(['photo', 'document', 'link'])],
            'items.*.file' => ['nullable', 'file', 'max:5120'],
            'items.*.url' => ['nullable', 'url', 'max:2048'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.capture' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'date' => 'tanggal',
            'activity' => 'kegiatan',
            'items.*.file' => 'berkas',
            'items.*.url' => 'URL',
            'items.*.label' => 'keterangan',
        ];
    }

    public function save()
    {
        $intern = auth()->user()->intern;

        if (! $intern) {
            $this->hasIntern = false;

            return;
        }

        // Tanggal selalu dikunci ke hari ini — abaikan nilai apa pun dari sisi klien
        // (juga menangani kasus modal dibiarkan terbuka melewati tengah malam).
        $this->date = Carbon::today()->toDateString();

        $this->validate();

        // Lampiran wajib: minimal satu.
        if (empty($this->items)) {
            $this->addError('items', 'Lampiran wajib diisi — tambahkan minimal satu foto, PDF, atau link.');
        }

        // Validasi bersyarat per lampiran.
        foreach ($this->items as $i => $item) {
            if (in_array($item['type'], ['photo', 'document'], true) && ! $item['file']) {
                $this->addError("items.$i.file", 'Berkas wajib diunggah.');
            }

            if ($item['type'] === 'link' && blank($item['url'])) {
                $this->addError("items.$i.url", 'URL wajib diisi.');
            }

            if ($item['type'] === 'photo' && $item['file'] && ! str_starts_with((string) $item['file']->getMimeType(), 'image/')) {
                $this->addError("items.$i.file", 'Berkas harus berupa gambar.');
            }

            if ($item['type'] === 'document' && $item['file'] && $item['file']->getMimeType() !== 'application/pdf') {
                $this->addError("items.$i.file", 'Berkas harus berupa PDF.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $journal = $intern->journals()->create([
            'date' => $this->date,
            'activity' => $this->activity,
        ]);

        foreach ($this->items as $item) {
            $path = null;

            if (in_array($item['type'], ['photo', 'document'], true) && $item['file']) {
                $path = $item['file']->store('journal-attachments', 'public');
            }

            $journal->attachments()->create([
                'type' => $item['type'],
                'path' => $path,
                'url' => $item['type'] === 'link' ? $item['url'] : null,
                'label' => $item['label'] !== '' ? $item['label'] : null,
            ]);
        }

        $this->close();
        $this->dispatch('journal-saved');
    }

    public function render()
    {
        return view('livewire.journals.create');
    }
}
