{{--
    Daftar "Atur Per Tanggal" (dipakai panel Mentor & panel pengajuan intern): satu baris per tanggal
    terpilih (urut naik) berisi tanggal + nama hari, badge isian saat ini, dan pilihan
    Pagi | Siang | Malam | Libur (jam di bawah label). Pilihan disimpan di Alpine sampai dikirim;
    baris yang belum dipilih dilewati.

    Props:
      options  ShiftTone::entryOptions(...)
      mode     'save'    → $wire.saveBulkShiftSchedule(entries)            (Mentor)
               'request' → $wire.submitBulkShiftRequests(entries, reason)   (intern, alasan wajib)
      presets  true = tampilkan preset rotasi (khusus Mentor)
    Isian saat ini dibaca dari elemen #shift-current-map (data-map JSON) yang dirender di luar
    komponen ini, supaya tetap segar setelah tiap update Livewire. Event window 'shift-bulk-saved'
    (dikirim server setelah berhasil) mengosongkan pilihan & alasan.
--}}
@props(['options' => [], 'mode' => 'save', 'presets' => false])

@php
    $labels = collect($options)->pluck('label', 'value')->all();
    $tones = collect(\App\Models\Shift::TYPES)->push('Libur')
        ->mapWithKeys(fn ($t) => [$t => \App\Support\ShiftTone::for($t)['class']])->all();
@endphp

<div wire:ignore
     x-data="{
        mode: @js($mode),
        labels: @js($labels),
        tones: @js($tones),
        choices: {},
        reason: '',
        sending: false,
        get dates() { return [...($wire.selected || [])].sort() },
        get chosen() { return this.dates.filter(d => this.choices[d]) },
        get count() { return this.chosen.length },
        get canSend() { return this.count > 0 && ! this.sending && (this.mode !== 'request' || this.reason.trim() !== '') },
        current() {
            try { return JSON.parse(document.getElementById('shift-current-map')?.dataset.map || '{}') } catch (e) { return {} }
        },
        dayLabel(d) {
            const dt = new Date(d + 'T00:00:00');
            return new Intl.DateTimeFormat('id-ID', { weekday: 'short' }).format(dt) + ', '
                + new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' }).format(dt);
        },
        fill(v) { this.dates.forEach(d => this.choices[d] = v) },
        rotate(seq) { this.dates.forEach((d, i) => this.choices[d] = seq[i % seq.length]) },
        reset() { this.choices = {} },
        async send() {
            if (! this.canSend) return;
            this.sending = true;
            const entries = this.chosen.map(d => ({ date: d, shift: this.choices[d] }));
            try {
                if (this.mode === 'request') await $wire.submitBulkShiftRequests(entries, this.reason);
                else await $wire.saveBulkShiftSchedule(entries);
            } finally { this.sending = false }
        },
     }"
     x-on:shift-bulk-saved.window="choices = {}; reason = ''">

    {{-- Tombol bantu --}}
    <div class="shift-pd-tools">
        <span class="shift-pd-tools-label">Isi semua dengan…</span>
        @foreach ($options as $opt)
            <button type="button" class="shift-pd-chip {{ $opt['class'] }}" @click="fill(@js($opt['value']))">
                <i class="fa-solid {{ $opt['icon'] }}" aria-hidden="true"></i> {{ $opt['label'] }}
            </button>
        @endforeach
    </div>
    <div class="shift-pd-tools">
        @if ($presets)
            <span class="shift-pd-tools-label">Rotasi</span>
            <button type="button" class="shift-pd-chip is-neutral" @click="rotate(['pagi', 'siang', 'malam'])">Pagi → Siang → Malam</button>
            <button type="button" class="shift-pd-chip is-neutral" @click="rotate(['pagi', 'pagi', 'siang', 'siang', 'libur'])">Pagi, Pagi, Siang, Siang, Libur</button>
            <button type="button" class="shift-pd-chip is-neutral" @click="rotate(['pagi', 'pagi', 'pagi', 'pagi', 'pagi', 'libur', 'libur'])">5 Pagi, 2 Libur</button>
        @endif
        <button type="button" class="shift-pd-chip is-neutral" @click="reset()" style="margin-left:auto;">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
        </button>
    </div>

    {{-- Baris per tanggal --}}
    <div class="shift-pd-list" role="list" aria-label="Pilihan shift per tanggal">
        <template x-for="d in dates" :key="d">
            <div class="shift-pd-row" role="listitem">
                <div class="shift-pd-date">
                    <span class="shift-pd-day" x-text="dayLabel(d)"></span>
                    <span class="shift-pd-now" :class="current()[d] ? (tones[current()[d]] || '') : 'is-empty'"
                          x-text="current()[d] ? 'Saat ini: ' + current()[d] : 'Saat ini: Kosong'"></span>
                </div>
                <div class="shift-pd-opts" role="radiogroup" :aria-label="'Shift untuk ' + dayLabel(d)">
                    @foreach ($options as $opt)
                        <button type="button" role="radio" class="shift-pd-opt {{ $opt['class'] }}"
                                :class="choices[d] === @js($opt['value']) && 'is-on'"
                                :aria-checked="choices[d] === @js($opt['value']) ? 'true' : 'false'"
                                @click="choices[d] = choices[d] === @js($opt['value']) ? undefined : @js($opt['value'])">
                            <span class="shift-pd-opt-label">{{ $opt['label'] }}</span>
                            <span class="shift-pd-opt-time">{{ $opt['time'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </template>
    </div>

    @if ($mode === 'request')
        <label for="shift-pd-reason" class="text-sm" style="display:block; font-weight:600; color:var(--text-body); margin:0.6rem 0 0.3rem;">
            Alasan <span style="color:#dc2626;">*</span>
            <span style="font-weight:400; color:var(--text-muted);">(satu alasan untuk semua tanggal)</span>
        </label>
        <textarea id="shift-pd-reason" x-model="reason" rows="2" maxlength="500" class="form-input"
                  style="width:100%; resize:vertical;"
                  placeholder="Mis. mohon izin tukar shift karena ada urusan keluarga"></textarea>
    @endif

    <div class="flex items-center" style="gap:0.6rem; margin-top:0.6rem; flex-wrap:wrap;">
        <p class="text-sm" style="color:var(--text-muted); flex:1 1 12rem;">
            <span x-show="count < dates.length" x-text="(dates.length - count) + ' tanggal belum dipilih akan dilewati.'"></span>
        </p>
        <button type="button" class="shift-pd-submit" :disabled="! canSend" @click="send()">
            <i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : '{{ $mode === 'request' ? 'fa-paper-plane' : 'fa-floppy-disk' }}'" aria-hidden="true"></i>
            <span x-text="'{{ $mode === 'request' ? 'Kirim Pengajuan' : 'Simpan' }} (' + count + ' tanggal)'"></span>
        </button>
    </div>
</div>
