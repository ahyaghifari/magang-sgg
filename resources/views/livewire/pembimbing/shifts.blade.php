@php
    // Yang berhak mengoreksi (Mentor dampingannya) boleh memilih tanggal mana
    // saja, termasuk lampau; selain itu kalender hanya menampilkan.
    $readonly = ! $canCorrect;
    $knownIds = $interns->pluck('id')->map(fn ($id) => (string) $id)->values();
@endphp

<div x-data="{
        toast: null, toastType: 'success', timer: null,
        recent: [],
        known: @js($knownIds),
        init() {
            try { this.recent = JSON.parse(localStorage.getItem('shift-recent-interns') || '[]').map(String) } catch (e) { this.recent = [] }
            @if ($intern) this.remember('{{ $intern->id }}') @endif
        },
        remember(id) {
            id = String(id);
            this.recent = [id, ...this.recent.filter(r => r !== id)].slice(0, 5);
            try { localStorage.setItem('shift-recent-interns', JSON.stringify(this.recent)) } catch (e) {}
        },
        hasRecent() { return this.recent.some(id => this.known.includes(id)) },
     }"
     x-on:shift-toast.window="toast = $event.detail.message; toastType = $event.detail.type; clearTimeout(timer); timer = setTimeout(() => toast = null, 5000)"
     x-on:shift-intern-picked.window="remember($event.detail.id)">

    @unless ($embedded)
        <div style="margin-bottom:1.1rem;">
            <h1 class="portal-title">Jadwal Shift Intern</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                @if (auth()->user()->isMentor())
                    Isi & koreksi jadwal shift peserta yang kamu dampingi, serta tinjau pengajuan perubahan dari mereka.
                @else
                    Pantau jadwal shift peserta — jadwal diisi oleh mentor masing-masing peserta.
                @endif
            </p>
        </div>
    @endunless

    {{-- Pesan sukses / gagal --}}
    <div x-show="toast" x-cloak x-transition role="status"
         class="surface-card flex items-center"
         :style="`gap:0.6rem; padding:0.75rem 1rem; margin-bottom:0.75rem; border-color:${toastType === 'error' ? '#fecaca' : '#a7f3d0'};`">
        <i class="fa-solid" :class="toastType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'"
           :style="`color:${toastType === 'error' ? '#dc2626' : 'var(--brand-success)'};`" aria-hidden="true"></i>
        <span class="text-sm" style="color:var(--text-body);" x-text="toast"></span>
    </div>

    {{-- Pengajuan perubahan shift dari intern dampingan (khusus Mentor intern tsb) --}}
    @if ($pendingRequests->isNotEmpty())
        <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
            <p style="font-weight:800; color:var(--text-heading); margin-bottom:0.6rem;">
                <i class="fa-solid fa-hourglass-half" style="color:#d97706;" aria-hidden="true"></i>
                Pengajuan perubahan shift <span class="shift-req-status is-pending" style="margin-left:0.3rem;">{{ $pendingRequests->count() }} menunggu</span>
            </p>
            <div class="shift-req-list">
                @foreach ($pendingRequests as $req)
                    <div class="shift-req" wire:key="pending-{{ $req->id }}">
                        <div class="shift-req-main">
                            <p class="shift-req-title">
                                {{ $req->intern->nama }} · {{ $req->date->locale('id')->translatedFormat('D, j M Y') }}
                            </p>
                            <p class="shift-req-meta"><b>{{ $req->changeLabel() }}</b> — Alasan: {{ $req->reason }}</p>
                            <input type="text" wire:model="decisionNotes.{{ $req->id }}" maxlength="500" class="form-input"
                                   style="width:100%; margin-top:0.45rem;" placeholder="Catatan untuk intern (opsional)"
                                   aria-label="Catatan keputusan untuk {{ $req->intern->nama }}">
                        </div>
                        <div class="shift-req-actions">
                            <button type="button" class="shift-action-btn shift-tone-lain" style="--st-bg:#d1fae5; --st-fg:#065f46; --st-bd:#a7f3d0;"
                                    wire:click="decide({{ $req->id }}, true)" wire:loading.attr="disabled" wire:target="decide">
                                <i class="fa-solid fa-check" aria-hidden="true"></i> Setujui
                            </button>
                            <button type="button" class="shift-action-btn is-neutral"
                                    wire:click="decide({{ $req->id }}, false)" wire:loading.attr="disabled" wire:target="decide"
                                    wire:confirm="Tolak pengajuan ini?">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Tolak
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pilih peserta --}}
    <div class="surface-card" style="padding:0.85rem 1rem; margin-bottom:0.75rem;">
        <x-shift-intern-picker :interns="$interns" :selected="$intern" />
    </div>

    @if (! $intern)
        {{-- Empty state --}}
        <div class="surface-card shift-empty">
            <div class="shift-empty-art" aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4.5" width="18" height="16" rx="3"/>
                    <path d="M3 9.5h18M8 3v3M16 3v3"/>
                    <rect x="6.5" y="12.5" width="3" height="2.5" rx="0.6" fill="currentColor" stroke="none" opacity="0.35"/>
                    <rect x="10.5" y="12.5" width="3" height="2.5" rx="0.6" fill="currentColor" stroke="none" opacity="0.6"/>
                    <rect x="14.5" y="12.5" width="3" height="2.5" rx="0.6" fill="currentColor" stroke="none"/>
                    <rect x="6.5" y="16.2" width="3" height="2.5" rx="0.6" fill="currentColor" stroke="none" opacity="0.6"/>
                </svg>
            </div>
            <p style="font-weight:800; font-size:1.05rem; color:var(--text-heading); margin-top:0.9rem;">Pilih peserta dulu</p>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.25rem; max-width:26rem; margin-inline:auto;">
                Pilih peserta di atas untuk melihat kalender shift-nya bulan ini.
            </p>

            {{-- Baru dilihat (disimpan di perangkat ini saja) --}}
            <div x-show="hasRecent()" x-cloak style="margin-top:1.25rem;">
                <p class="shift-picker-group" style="text-align:center;">Baru dilihat</p>
                <div class="flex" style="flex-wrap:wrap; justify-content:center; gap:0.45rem; margin-top:0.3rem;">
                    @foreach ($interns as $i)
                        <button type="button" class="shift-recent-btn"
                                x-show="recent.includes('{{ $i->id }}')" x-cloak
                                :style="'order:' + recent.indexOf('{{ $i->id }}')"
                                @click="remember('{{ $i->id }}'); $wire.set('internId', '{{ $i->id }}')"
                                aria-label="Buka jadwal {{ $i->nama }}">
                            <x-shift-avatar :intern="$i" />
                            {{ $i->nama }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <x-shift-intern-card :intern="$intern" style="margin-bottom:0.75rem;">
            @if ($readonly)
                <span class="shift-readonly-badge" style="flex-shrink:0;"><i class="fa-solid fa-eye" aria-hidden="true"></i> Hanya lihat</span>
            @endif
        </x-shift-intern-card>

        @if (! $hasCompany)
            <div class="callout-warning" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
                <p class="text-sm callout-warning-title" style="font-weight:700;">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Data unit peserta ini belum lengkap, silakan hubungi admin.
                </p>
            </div>
        @elseif ($canCorrect && $shifts->isEmpty())
            <div class="callout-warning" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
                <p class="text-sm callout-warning-title" style="font-weight:700;">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Perusahaan peserta ini belum punya master shift.
                </p>
                <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">Minta admin menambahkannya. Tanggal tetap bisa ditandai Libur.</p>
            </div>
        @endif

        <x-shift-month-header :month-label="$monthLabel" :is-current-month="$isCurrentMonth" :entries="$entries"
                              :days-in-month="$daysInMonth" :shifts="$shifts" :editable="$canCorrect" style="margin-bottom:0.75rem;" />

        <x-shift-legend :shifts="$shifts" style="margin-bottom:0.6rem;" />

        <x-shift-calendar :weeks="$weeks" :entries="$entries" :selected="$selected" :today="$today" :readonly="$readonly" :pending="$pendingDates" />

        <div style="margin-top:0.65rem;">
            <x-shift-last-change :change="$lastChange" />
        </div>

        @if ($canCorrect)
            {{-- Ruang kosong supaya baris kalender terakhir tidak tertutup action bar --}}
            <div x-data="{ tall: false }" x-on:shift-bar-mode.window="tall = $event.detail.mode === 'perDate'"
                 x-show="$wire.selected.length > 0" :style="{ height: tall ? '70vh' : '10rem' }" style="display:none; height:10rem;" wire:ignore.self aria-hidden="true"></div>
            <x-shift-action-bar :shifts="$shifts" :entries="$entries" />
        @endif
    @endif
</div>
