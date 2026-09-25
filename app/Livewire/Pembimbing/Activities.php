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
    // Hari ini + 7 hari sebelumnya muat dalam satu halaman default.
    private const DAYS_PER_PAGE = 8;

    // Rentang default: hari ini (utama, paling atas) + 1 minggu sebelumnya.
    private const DEFAULT_PAST_DAYS = 7;

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

        // Default "hari ini + 1 minggu sebelumnya" saat pertama dibuka (tanpa query string di
        // URL) — hari ini tetap paling atas. Untuk tanggal lain, pakai filter. Kalau URL sudah
        // bawa dateFrom/dateTo sendiri (mis. dari tautan yang dibagikan), itu tetap dihormati.
        if ($this->dateFrom === '' && $this->dateTo === '') {
            $this->applyDefaultRange();
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['internId', 'search', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    protected function defaultDateFrom(): string
    {
        return Carbon::today()->subDays(self::DEFAULT_PAST_DAYS)->toDateString();
    }

    protected function applyDefaultRange(): void
    {
        $this->dateFrom = $this->defaultDateFrom();
        $this->dateTo = Carbon::today()->toDateString();
    }

    /** Kembali ke tampilan default (hari ini + 1 minggu sebelumnya), bukan menghapus filter jadi "semua tanggal". */
    public function resetDateFilter(): void
    {
        $this->applyDefaultRange();
        $this->resetPage();
    }

    protected function resolveCommentable(string $type, int $id): ?Model
    {
        if ($type !== 'journal' || ! $this->allowed()) {
            return null;
        }

        // Sengaja pakai visibleInternIds() (BUKAN manageableInternIds()) — komentar di halaman
        // Kegiatan Intern boleh dikirim untuk SEMUA intern yang kelihatan (termasuk Mentor yang
        // melihat semua intern), beda dari beri bintang yang tetap dibatasi ke mentee sendiri.
        return Journal::whereKey($id)->whereIn('intern_id', $this->visibleInternIds())->first();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    protected function canReview(): bool
    {
        return $this->allowed();
    }

    /**
     * Id intern yang boleh DILIHAT user yang sedang login — dipakai untuk feed/filter
     * jurnal (lihat User::visibleInterns(): Mentor sengaja melihat semua intern di sini).
     */
    protected function visibleInternIds(): array
    {
        return auth()->user()->visibleInterns()->pluck('id')->all();
    }

    /**
     * Id intern yang boleh DIKELOLA user yang sedang login — dipakai untuk beri bintang
     * & komentar. SELALU sempit ke intern yang memang dibimbing/dimentori (lihat
     * User::manageableInterns()), beda dari visibleInternIds() yang melebar untuk Mentor.
     */
    protected function manageableInternIds(): array
    {
        return auth()->user()->manageableInterns()->pluck('id')->all();
    }

    public function rate(int $journalId, int $stars): void
    {
        if (! $this->canReview()) {
            return;
        }

        $stars = max(1, min(5, $stars));

        if (! Journal::whereKey($journalId)->whereIn('intern_id', $this->manageableInternIds())->exists()) {
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

        if (! Journal::whereKey($journalId)->whereIn('intern_id', $this->manageableInternIds())->exists()) {
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
            ->whereIn('intern_id', $this->visibleInternIds())
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
            ->with(['intern.institusi', 'intern.unit', 'attachments', 'reviews.reviewer', 'comments.author.intern'])
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

        $internIds = $this->visibleInternIds();

        return view('livewire.pembimbing.activities', [
            'journals' => $journals,
            'myReviews' => $myReviews,
            'today' => Carbon::today()->toDateString(),
            'defaultDateFrom' => $this->defaultDateFrom(),
            'canReview' => $this->canReview(),
            // Per-jurnal: bintang & komentar cuma boleh diisi untuk intern yang memang
            // dibimbing/dimentori — dipakai di view untuk menyembunyikan kontrolnya pada
            // jurnal milik intern lain (yang cuma boleh dilihat, bukan dinilai).
            'manageableInternIds' => $this->manageableInternIds(),
            'interns' => Intern::whereIn('id', $internIds)->orderBy('nama')->get(['id', 'nama']),
            'totalJournals' => Journal::whereIn('intern_id', $internIds)->count(),
            'totalInterns' => count($internIds),
            'journalsThisMonth' => Journal::whereIn('intern_id', $internIds)->whereBetween('date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->count(),
        ]);
    }
}
