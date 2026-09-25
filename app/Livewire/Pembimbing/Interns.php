<?php

namespace App\Livewire\Pembimbing;

use App\Livewire\Concerns\HasInternDetailModals;
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
    use HasInternDetailModals;
    use WithPagination;

    #[Url]
    public string $search = '';

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

    protected function canViewIntern(int $internId): bool
    {
        return in_array($internId, $this->visibleInternIds(), true);
    }

    public function render()
    {
        $internIds = $this->visibleInternIds();

        $interns = $this->internCardsQuery($this->search)
            ->whereIn('id', $internIds)
            ->paginate(12);

        return view('livewire.pembimbing.interns', [
            'interns' => $interns,
            'totalInterns' => count($internIds),
            ...$this->internDetailModalData(),
        ]);
    }
}
