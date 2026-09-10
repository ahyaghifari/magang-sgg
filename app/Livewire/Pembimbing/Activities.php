<?php

namespace App\Livewire\Pembimbing;

use App\Livewire\Concerns\HasCommentThread;
use App\Models\Intern;
use App\Models\Journal;
use App\Models\JournalReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Feed kegiatan (jurnal) SEMUA peserta magang — untuk peran pembimbing (dan admin).
 * Pembimbing dapat memberi penilaian bintang (1..5) per jurnal; nilai yang tampil
 * adalah rata-rata dari semua pembimbing.
 */
#[Layout('components.layouts.app')]
class Activities extends Component
{
    use HasCommentThread, WithPagination;

    /** Jumlah HARI kegiatan per halaman (bukan jumlah jurnal) — satu hari tak pernah terpotong. */
    private const DAYS_PER_PAGE = 7;

    public string $internId = '';

    public string $search = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount()
    {
        // Bukan pembimbing / admin → arahkan ke portal, jangan lempar 403.
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['internId', 'search', 'dateFrom', 'dateTo'], true)) {
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
        if ($type !== 'journal' || ! $this->allowed()) {
            return null;
        }

        return Journal::whereKey($id)->first();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPembimbing() || $user->isAdmin());
    }

    protected function canReview(): bool
    {
        return $this->allowed();
    }

    public function rate(int $journalId, int $stars): void
    {
        if (! $this->canReview()) {
            return;
        }

        $stars = max(1, min(5, $stars));

        if (! Journal::whereKey($journalId)->exists()) {
            return;
        }

        JournalReview::updateOrCreate(
            ['journal_id' => $journalId, 'user_id' => auth()->id()],
            ['rating' => $stars],
        );
    }

    public function clearRating(int $journalId): void
    {
        if (! $this->canReview()) {
            return;
        }

        JournalReview::where('journal_id', $journalId)
            ->where('user_id', auth()->id())
            ->delete();
    }

    /** Query dasar + filter (peserta / pencarian). Dipakai baik untuk daftar hari maupun jurnal. */
    protected function baseQuery(): Builder
    {
        return Journal::query()
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('activity', 'like', '%' . $this->search . '%')
                    ->orWhereHas('intern', fn ($q) => $q->where('nama', 'like', '%' . $this->search . '%'));
            }));
    }

    public function render()
    {
        $page = $this->getPage();

        // 1) Paginasi berdasarkan HARI — ambil tanggal-tanggal unik untuk halaman ini.
        $totalDays = (int) $this->baseQuery()->distinct()->count('date');

        $dates = $this->baseQuery()
            ->select('date')
            ->distinct()
            ->orderByDesc('date')
            ->forPage($page, self::DAYS_PER_PAGE)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        // 2) Ambil SEMUA jurnal untuk hari-hari tersebut (satu hari tidak akan terpotong).
        $items = $this->baseQuery()
            ->with(['intern.institusi', 'intern.unit', 'attachments', 'reviews.reviewer', 'comments.author'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($dates !== [], fn ($q) => $q->whereIn('date', $dates), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $journals = new LengthAwarePaginator(
            $items,
            $totalDays,
            self::DAYS_PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );

        // Penilaian milik pembimbing yang sedang login, untuk jurnal yang tampil.
        $myReviews = JournalReview::where('user_id', auth()->id())
            ->whereIn('journal_id', $items->pluck('id'))
            ->get()
            ->keyBy('journal_id');

        return view('livewire.pembimbing.activities', [
            'journals' => $journals,
            'myReviews' => $myReviews,
            'canReview' => $this->canReview(),
            'interns' => Intern::orderBy('nama')->get(['id', 'nama']),
            'totalJournals' => Journal::count(),
            'totalInterns' => Intern::count(),
            'journalsThisMonth' => Journal::whereBetween('date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
        ]);
    }
}
