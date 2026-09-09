<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Kegiatan Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Semua catatan kegiatan harian dari seluruh peserta magang
        </p>
    </div>

    {{-- ===== Ringkasan ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.1rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Peserta</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalInterns }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Jurnal</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalJournals }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Jurnal Bulan Ini</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $journalsThisMonth }}</p>
        </div>
    </div>

    {{-- ===== Filter ===== --}}
    <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:1rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0.75rem;">
        <div>
            <label for="f-search" class="form-label">Cari</label>
            <input id="f-search" type="text" wire:model.live.debounce.400ms="search" class="form-input"
                   placeholder="Nama peserta atau isi kegiatan...">
        </div>
        <div>
            <label for="f-intern" class="form-label">Peserta</label>
            <select id="f-intern" wire:model.live="internId" class="form-input">
                <option value="">Semua peserta</option>
                @foreach ($interns as $intern)
                    <option value="{{ $intern->id }}">{{ $intern->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- ===== Daftar kegiatan (dikelompokkan per hari) ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.6rem;">
        @php($lastGroup = null)
        @forelse ($journals as $journal)
            @php($d = \Illuminate\Support\Carbon::parse($journal->date))
            @if ($d->toDateString() !== $lastGroup)
                @php($lastGroup = $d->toDateString())
                @php($groupTitle = $d->isToday() ? 'Hari Ini' : ($d->isYesterday() ? 'Kemarin' : $d->translatedFormat('l')))
                <div class="flex" style="align-items:baseline; gap:0.55rem; margin-top:{{ $loop->first ? '0' : '0.9rem' }}; padding-bottom:0.15rem; border-bottom:1px solid var(--border-soft);">
                    <span style="font-size:0.95rem; font-weight:800; color:var(--text-heading);">{{ $groupTitle }}</span>
                    <span class="text-sm" style="color:var(--text-muted);">{{ $d->translatedFormat('d F Y') }}</span>
                </div>
            @endif
            <article class="surface-card" style="padding:1.1rem 1.15rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap;">
                    <div class="flex items-center" style="gap:0.55rem; flex-wrap:wrap;">
                        <span style="font-weight:700; color:var(--text-heading);">
                            {{ $journal->intern->nama ?? 'Peserta dihapus' }}
                        </span>
                        @if ($journal->intern?->institusi)
                            <span class="badge badge-neutral">
                                <i class="fa-solid fa-building"></i> {{ $journal->intern->institusi->name }}
                            </span>
                        @endif
                        @if ($journal->intern?->unit)
                            <span class="badge badge-neutral">
                                <i class="fa-solid fa-people-group"></i> {{ $journal->intern->unit->name }}
                            </span>
                        @endif
                    </div>
                    @if ($journal->attachments->isNotEmpty())
                        <span class="badge badge-neutral" style="flex-shrink:0;">
                            <i class="fa-solid fa-paperclip"></i> {{ $journal->attachments->count() }}
                        </span>
                    @endif
                </div>

                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-body); white-space:pre-line;">{{ $journal->activity }}</p>

                @if ($journal->attachments->isNotEmpty())
                    <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-top:0.85rem;">
                        @foreach ($journal->attachments as $att)
                            @if ($att->type === 'link')
                                <a href="{{ $att->url }}" target="_blank" rel="noopener"
                                   class="badge badge-neutral" style="text-decoration:none;">
                                    <i class="fa-solid fa-link"></i>
                                    {{ \Illuminate\Support\Str::limit($att->label ?: $att->url, 40) }}
                                </a>
                            @elseif ($att->type === 'document')
                                <a href="{{ url('storage/' . $att->path) }}" target="_blank" rel="noopener"
                                   class="badge badge-neutral" style="text-decoration:none;">
                                    <i class="fa-regular fa-file-pdf" style="color:#dc2626;"></i>
                                    {{ \Illuminate\Support\Str::limit($att->label ?: 'Dokumen PDF', 40) }}
                                </a>
                            @else
                                <a href="{{ url('storage/' . $att->path) }}" target="_blank" rel="noopener"
                                   style="display:block; border-radius:10px; overflow:hidden; border:1px solid var(--border);">
                                    <img src="{{ url('storage/' . $att->path) }}" alt="{{ $att->label }}"
                                         style="width:84px; height:84px; object-fit:cover; display:block;">
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- ===== Penilaian pembimbing (bintang 1..5, tampil rata-rata) ===== --}}
                @php($avg = $journal->reviews_avg_rating)
                @php($cnt = $journal->reviews_count)
                <div style="margin-top:0.9rem; padding-top:0.75rem; border-top:1px solid var(--border-soft);">
                    <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                        <span class="text-sm" style="font-weight:600; color:var(--text-muted);">Penilaian:</span>
                        @if ($cnt)
                            <span style="color:#f59e0b; letter-spacing:1px;">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-{{ $i <= round($avg) ? 'solid' : 'regular' }} fa-star"></i>
                                @endfor
                            </span>
                            <span class="text-sm" style="font-weight:700; color:var(--text-heading);">{{ number_format($avg, 1) }}</span>
                            <span class="text-sm" style="color:var(--text-muted);">
                                (rata-rata dari {{ $cnt }} pembimbing)
                            </span>
                        @else
                            <span class="text-sm" style="color:var(--text-faint);">Belum dinilai</span>
                        @endif
                    </div>

                    @if ($cnt)
                        <div class="flex" style="flex-wrap:wrap; gap:0.4rem 0.8rem; margin-top:0.4rem;">
                            @foreach ($journal->reviews as $rv)
                                <span class="text-sm" style="color:var(--text-muted);">
                                    {{ $rv->reviewer->name ?? '—' }}:
                                    <span style="color:#f59e0b;">{{ str_repeat('★', $rv->rating) }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($canReview)
                        @php($mine = $myReviews[$journal->id] ?? null)
                        <div class="flex items-center" style="gap:0.35rem; margin-top:0.55rem;" wire:key="rate-{{ $journal->id }}">
                            <span class="text-sm" style="color:var(--text-muted); margin-right:0.15rem;">Penilaianmu:</span>
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="rate({{ $journal->id }}, {{ $i }})"
                                        aria-label="{{ $i }} bintang"
                                        style="background:none; border:0; padding:0.1rem; cursor:pointer; font-size:1.05rem; line-height:1; color:{{ $mine && $i <= $mine->rating ? '#f59e0b' : 'var(--text-faint)' }};">
                                    <i class="fa-{{ $mine && $i <= $mine->rating ? 'solid' : 'regular' }} fa-star"></i>
                                </button>
                            @endfor
                            @if ($mine)
                                <button type="button" wire:click="clearRating({{ $journal->id }})" class="text-sm"
                                        style="color:var(--text-muted); margin-left:0.4rem; text-decoration:underline;">
                                    hapus
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center;">
                <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                    Belum ada kegiatan yang cocok dengan filter.
                </p>
            </div>
        @endforelse
    </div>

    @if ($journals->hasPages())
        <div class="flex items-center justify-between" style="margin-top:1.25rem;">
            <button wire:click="previousPage" class="btn-ghost" @disabled($journals->onFirstPage())>
                <i class="fa-solid fa-chevron-left"></i> Sebelumnya
            </button>
            <span style="font-size:0.8rem; color:var(--text-muted);">
                Halaman {{ $journals->currentPage() }} dari {{ $journals->lastPage() }}
            </span>
            <button wire:click="nextPage" class="btn-ghost" @disabled(! $journals->hasMorePages())>
                Berikutnya <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    @endif
</div>
