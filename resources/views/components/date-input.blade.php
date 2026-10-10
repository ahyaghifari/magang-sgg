{{--
    Input tanggal berformat DD/MM/YYYY (pengganti <input type="date"> yang formatnya mengikuti bahasa
    browser/HP, mis. "hh/bb/tttt"). Nilai ke Livewire tetap Y-m-d, jadi logika tidak berubah.

    - Ketik angka saja: garis miring ditambahkan otomatis (12102026 → 12/10/2026). Nilai baru dikirim
      setelah tanggal lengkap & valid, atau saat dikosongkan.
    - Tombol kalender membuka pemilih tanggal bawaan browser/HP (input date transparan di atas tombol).

    Props:
      model  nama properti Livewire (mis. 'dateFrom')
      live   true = kirim langsung saat berubah (seperti wire:model.live), false = seperti wire:model
      id     id kolom teks (untuk <label for>)
    Atribut lain (class, aria-label) diteruskan ke kolom teks; style (mis. max-width) ke pembungkus.
--}}
@props(['model', 'live' => false, 'id' => null])

<div {{ $attributes->only('style')->merge(['class' => 'date-input']) }}
     x-data="{
        iso: $wire.$entangle(@js($model), @js((bool) $live)),
        text: '',
        invalid: false,
        init() {
            this.text = this.toText(this.iso);
            this.$watch('iso', v => { this.text = this.toText(v); this.invalid = false });
        },
        toText(v) {
            const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(v || '');
            return m ? `${m[3]}/${m[2]}/${m[1]}` : '';
        },
        mask(e) {
            const d = e.target.value.replace(/\D/g, '').slice(0, 8);
            this.text = [d.slice(0, 2), d.slice(2, 4), d.slice(4, 8)].filter(Boolean).join('/');
            if (this.text.length === 10 || this.text === '') this.commit();
        },
        commit() {
            if (this.text === '') { this.invalid = false; if (this.iso) this.iso = ''; return; }
            const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(this.text);
            const dt = m && new Date(+m[3], +m[2] - 1, +m[1]);
            const ok = dt && dt.getFullYear() === +m[3] && dt.getMonth() === +m[2] - 1 && dt.getDate() === +m[1];
            this.invalid = ! ok;
            if (ok) {
                const iso = `${m[3]}-${m[2]}-${m[1]}`;
                if (iso !== this.iso) this.iso = iso;
            }
        },
     }">
    <input type="text" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="DD/MM/YYYY"
           @if ($id) id="{{ $id }}" @endif
           x-model="text" @input="mask($event)" @blur="commit()"
           :class="invalid && 'is-invalid'" :aria-invalid="invalid ? 'true' : 'false'"
           {{ $attributes->except('style')->merge(['class' => 'form-input date-input-text']) }}>
    <span class="date-input-btn" aria-hidden="true">
        <i class="fa-regular fa-calendar"></i>
        {{-- Pemilih tanggal bawaan (transparan, menutupi tombol) — klik/ketuk langsung membukanya. --}}
        <input type="date" tabindex="-1" :value="iso || ''"
               @click="$el.showPicker && $el.showPicker()"
               @change="iso = $event.target.value || ''">
    </span>
    <p class="date-input-error" x-show="invalid" x-cloak>Tanggal tidak valid. Gunakan format DD/MM/YYYY.</p>
</div>
