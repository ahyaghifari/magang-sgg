<?php

namespace App\Livewire\Pembimbing;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sertifikat PKL intern yang dibimbing/dimentori user yang sedang login (lihat
 * User::visibleInterns()) — bisa lihat/download sertifikat, dan mengedit data
 * yang dipakai di sertifikat itu (nama, nama panggilan, tanggal mulai/selesai
 * magang). Field lain (institusi, unit, NIP, penugasan pembimbing/mentor) tetap
 * admin-only lewat panel /admin.
 */
#[Layout('components.layouts.app')]
class Certificates extends Component
{
    use WithPagination;

    public bool $showEdit = false;

    public ?int $editingInternId = null;

    public string $nama = '';

    public string $namaPanggilan = '';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    /** Intern yang boleh dilihat/dikelola user yang sedang login. */
    protected function visibleInterns()
    {
        return auth()->user()->visibleInterns();
    }

    public function edit(int $internId): void
    {
        $intern = $this->visibleInterns()->whereKey($internId)->first();

        if (! $intern) {
            return;
        }

        $this->editingInternId = $intern->id;
        $this->nama = $intern->nama;
        $this->namaPanggilan = $intern->nama_panggilan ?? '';
        $this->tanggalMulai = $intern->tanggal_mulai?->toDateString() ?? '';
        $this->tanggalSelesai = $intern->tanggal_selesai?->toDateString() ?? '';
        $this->resetValidation();
        $this->showEdit = true;
    }

    public function closeEdit(): void
    {
        $this->showEdit = false;
        $this->reset(['editingInternId', 'nama', 'namaPanggilan', 'tanggalMulai', 'tanggalSelesai']);
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'namaPanggilan' => ['nullable', 'string', 'max:255'],
            'tanggalMulai' => ['nullable', 'date'],
            'tanggalSelesai' => ['nullable', 'date', 'after_or_equal:tanggalMulai'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama',
            'namaPanggilan' => 'nama panggilan',
            'tanggalMulai' => 'tanggal mulai magang',
            'tanggalSelesai' => 'tanggal selesai magang',
        ];
    }

    public function save(): void
    {
        if (! $this->editingInternId) {
            return;
        }

        // Cek ulang saat submit, bukan cuma saat buka modal — mencegah edit intern
        // yang tidak/tidak lagi ditugaskan ke user ini.
        $intern = $this->visibleInterns()->whereKey($this->editingInternId)->first();

        if (! $intern) {
            $this->closeEdit();

            return;
        }

        $this->validate();

        $intern->update([
            'nama' => $this->nama,
            'nama_panggilan' => $this->namaPanggilan !== '' ? $this->namaPanggilan : null,
            'tanggal_mulai' => $this->tanggalMulai !== '' ? $this->tanggalMulai : null,
            'tanggal_selesai' => $this->tanggalSelesai !== '' ? $this->tanggalSelesai : null,
        ]);

        $this->closeEdit();
    }

    public function render()
    {
        return view('livewire.pembimbing.certificates', [
            'interns' => $this->visibleInterns()
                ->with('unit.company')
                ->orderBy('nama')
                ->paginate(15),
        ]);
    }
}
