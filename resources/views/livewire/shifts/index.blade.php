<div>
    <div style="margin-bottom:1.1rem;">
        <h1 class="portal-title">Jadwal Shift</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Jadwal shift kamu per tanggal. Jadwal diisi oleh pembimbing atau mentor — hubungi mereka bila ada yang perlu diubah.
        </p>
    </div>

    @if (! $hasCompany)
        <div class="callout-warning" style="padding:0.9rem 1rem; margin-bottom:0.75rem;">
            <p class="text-sm callout-warning-title" style="font-weight:700;">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Data unit kamu belum lengkap, silakan hubungi admin.
            </p>
        </div>
    @endif

    <x-shift-month-header :month-label="$monthLabel" :is-current-month="$isCurrentMonth" :entries="$entries"
                          :days-in-month="$daysInMonth" :shifts="$shifts" :editable="false" style="margin-bottom:0.75rem;" />

    <x-shift-legend :shifts="$shifts" style="margin-bottom:0.6rem;" />

    <x-shift-calendar :weeks="$weeks" :entries="$entries" :today="$today" readonly />

    <div style="margin-top:0.65rem;">
        <x-shift-last-change :change="$lastChange" />
    </div>
</div>
