<div x-data="{ toast: null, toastType: 'success', timer: null }"
     x-on:shift-toast.window="toast = $event.detail.message; toastType = $event.detail.type; clearTimeout(timer); timer = setTimeout(() => toast = null, 6000)">
    <div style="margin-bottom:1.1rem;">
        <h1 class="portal-title">Jadwal Shift</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Jadwal shift kamu diisi oleh mentor. Bila berhalangan, ketuk tanggal (hari ini s.d. {{ $windowDays }} hari ke depan)
            atau ketuk nama hari / tombol di kiri baris untuk memilih sekaligus, lalu <b>Ajukan Perubahan</b> — jadwal berubah setelah disetujui mentor.
        </p>
    </div>

    {{-- Pesan sukses / gagal --}}
    <div x-show="toast" x-cloak x-transition role="status"
         class="surface-card flex items-center"
         :style="`gap:0.6rem; padding:0.75rem 1rem; margin-bottom:0.75rem; border-color:${toastType === 'error' ? '#fecaca' : '#a7f3d0'};`">
        <i class="fa-solid" :class="toastType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'"
           :style="`color:${toastType === 'error' ? '#dc2626' : 'var(--brand-success)'};`" aria-hidden="true"></i>
        <span class="text-sm" style="color:var(--text-body);" x-text="toast"></span>
    </div>

    @if (! $hasCompany)
        <div class="callout-warning" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
            <p class="text-sm callout-warning-title" style="font-weight:700;">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Data unit kamu belum lengkap, silakan hubungi admin.
            </p>
        </div>
    @elseif (! $hasMentor)
        <div class="callout-warning" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
            <p class="text-sm callout-warning-title" style="font-weight:700;">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Kamu belum punya mentor.
            </p>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">Pengajuan perubahan shift belum bisa dikirim sampai kamu punya mentor. Silakan hubungi pembimbing.</p>
        </div>
    @endif

    <x-shift-month-header :month-label="$monthLabel" :is-current-month="$isCurrentMonth" :entries="$entries"
                          :days-in-month="$daysInMonth" :shifts="$shifts" :editable="false" style="margin-bottom:0.75rem;" />

    <x-shift-legend :shifts="$shifts" style="margin-bottom:0.6rem;" />

    <x-shift-calendar :weeks="$weeks" :entries="$entries" :selected="$selected" :today="$today"
                      :can-pick="$canPick" :pending="$pendingDates" />

    <div style="margin-top:0.65rem;">
        <x-shift-last-change :change="$lastChange" />
    </div>

    {{-- Pengajuan perubahan milik intern --}}
    @if ($requests->isNotEmpty())
        <div class="surface-card" style="padding:0.9rem 1rem; margin-top:0.9rem;">
            <p style="font-weight:800; color:var(--text-heading); margin-bottom:0.6rem;">
                <i class="fa-solid fa-arrows-rotate" style="color:var(--brand);" aria-hidden="true"></i> Pengajuan perubahan
            </p>
            <div class="shift-req-list">
                @foreach ($requests as $req)
                    <div class="shift-req" wire:key="req-{{ $req->id }}">
                        <div class="shift-req-main">
                            <p class="shift-req-title">
                                {{ $req->date->locale('id')->translatedFormat('D, j M Y') }} · {{ $req->changeLabel() }}
                            </p>
                            <p class="shift-req-meta">Alasan: {{ $req->reason }}</p>
                            @if ($req->decider && ! $req->isPending())
                                <p class="shift-req-meta">
                                    {{ $req->statusLabel() }} oleh {{ $req->decider->name }}{{ $req->decision_note ? ' — ' . $req->decision_note : '' }}
                                </p>
                            @endif
                        </div>
                        <div class="shift-req-actions">
                            <span class="shift-req-status is-{{ $req->status }}">{{ $req->statusLabel() }}</span>
                            @if ($req->isPending())
                                <button type="button" class="shift-btn-sm" wire:click="cancelRequest({{ $req->id }})"
                                        wire:confirm="Batalkan pengajuan perubahan tanggal ini?" wire:loading.attr="disabled">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Batalkan
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Ruang kosong supaya baris kalender terakhir tidak tertutup action bar --}}
    <div x-data="{ tall: false }" x-on:shift-bar-mode.window="tall = $event.detail.mode === 'perDate'"
         x-show="$wire.selected.length > 0" :style="{ height: tall ? '75vh' : '6rem' }" style="display:none; height:6rem;" wire:ignore.self aria-hidden="true"></div>
    <x-shift-request-bar :options="$requestOptions" :entries="$entries" />
</div>
