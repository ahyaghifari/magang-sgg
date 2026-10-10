{{--
    Lightbox foto global — dengar event `open-lightbox` (window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { src, alt } })))
    dari mana pun di halaman, lalu tampil sebagai overlay tanpa pindah tab. Dipasang sekali di layout portal
    (components/layouts/app.blade.php).

    Kontrol (pakai Pointer Events, jadi sama untuk mouse, sentuhan HP/tablet, dan pena):
    - HP: cubit dua jari = zoom (berpusat di antara jari), ketuk dua kali = zoom ke titik itu / kembali,
      geser satu jari = pindah posisi saat diperbesar.
    - Laptop: scroll = zoom ke arah kursor, klik-tahan = geser, klik dua kali = zoom / kembali.
    - Tombol + / − / reset di layar untuk semua perangkat. Ketuk area gelap = tutup.
    touch-action:none di overlay sengaja mematikan zoom bawaan browser (yang akan memperbesar seluruh
    halaman) — gantinya zoom foto ditangani di sini.
--}}
<div
    x-data="{
        open: false,
        src: null,
        alt: '',
        scale: 1,
        tx: 0,
        ty: 0,
        animate: true,
        pointers: new Map(),
        pinch: null,
        pan: null,
        moved: false,
        lastTap: 0,
        MIN: 1,
        MAX: 5,

        openWith(detail) {
            this.src = detail.src;
            this.alt = detail.alt || '';
            this.reset();
            this.open = true;
        },
        close() {
            this.open = false;
            this.pointers.clear();
            this.pinch = null;
            this.pan = null;
        },
        reset() {
            this.animate = true;
            this.scale = 1;
            this.tx = 0;
            this.ty = 0;
        },

        // Pusat foto di layar TANPA efek translate (skala berpusat di tengah elemen).
        center() {
            const r = this.$refs.img.getBoundingClientRect();
            return { x: r.left + r.width / 2 - this.tx, y: r.top + r.height / 2 - this.ty };
        },
        // Batasi geser supaya foto tidak lari keluar layar.
        clamp() {
            const img = this.$refs.img;
            const w = img.offsetWidth;
            const h = img.offsetHeight;
            const maxX = Math.max(0, (w * this.scale - window.innerWidth) / 2 + 40, (w * (this.scale - 1)) / 2);
            const maxY = Math.max(0, (h * this.scale - window.innerHeight) / 2 + 40, (h * (this.scale - 1)) / 2);
            this.tx = Math.min(maxX, Math.max(-maxX, this.tx));
            this.ty = Math.min(maxY, Math.max(-maxY, this.ty));
            if (this.scale <= 1.001) { this.scale = 1; this.tx = 0; this.ty = 0; }
        },
        // Zoom ke skala baru dengan titik (px, py) di layar tetap di tempatnya.
        zoomAt(newScale, px, py) {
            newScale = Math.min(this.MAX, Math.max(this.MIN, newScale));
            const c = this.center();
            const k = newScale / this.scale;
            this.tx = (px - c.x) - (px - c.x - this.tx) * k;
            this.ty = (py - c.y) - (py - c.y - this.ty) * k;
            this.scale = newScale;
            this.clamp();
        },
        zoomBy(factor) {
            this.animate = true;
            const c = this.center();
            this.zoomAt(this.scale * factor, c.x + this.tx, c.y + this.ty);
        },
        wheel(e) {
            this.animate = false;
            this.zoomAt(this.scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15), e.clientX, e.clientY);
        },
        toggleAt(x, y) {
            this.animate = true;
            if (this.scale > 1) { this.reset(); } else { this.zoomAt(2.5, x, y); }
        },

        down(e) {
            this.$refs.img.setPointerCapture?.(e.pointerId);
            this.pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            this.animate = false;
            this.moved = false;
            if (this.pointers.size === 2) {
                const [a, b] = [...this.pointers.values()];
                this.pinch = { dist: Math.hypot(a.x - b.x, a.y - b.y), scale: this.scale };
                this.pan = null;
            } else if (this.pointers.size === 1) {
                this.pan = { x: e.clientX - this.tx, y: e.clientY - this.ty };
            }
        },
        move(e) {
            if (! this.pointers.has(e.pointerId)) return;
            const prev = this.pointers.get(e.pointerId);
            if (Math.abs(prev.x - e.clientX) + Math.abs(prev.y - e.clientY) > 2) this.moved = true;
            this.pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (this.pinch && this.pointers.size >= 2) {
                const [a, b] = [...this.pointers.values()];
                const dist = Math.hypot(a.x - b.x, a.y - b.y);
                this.zoomAt(this.pinch.scale * (dist / this.pinch.dist), (a.x + b.x) / 2, (a.y + b.y) / 2);
            } else if (this.pan && this.scale > 1) {
                this.tx = e.clientX - this.pan.x;
                this.ty = e.clientY - this.pan.y;
                this.clamp();
            }
        },
        up(e) {
            this.pointers.delete(e.pointerId);
            if (this.pointers.size < 2) this.pinch = null;
            if (this.pointers.size === 1) {
                // Satu jari dilepas saat cubit → lanjut geser dengan jari yang tersisa.
                const p = [...this.pointers.values()][0];
                this.pan = { x: p.x - this.tx, y: p.y - this.ty };
                return;
            }
            this.pan = null;
            // Ketuk dua kali (≤ 300 ms, tanpa geser) = zoom ke titik ketuk / kembali.
            if (! this.moved && e.type === 'pointerup') {
                const now = Date.now();
                if (now - this.lastTap < 300) {
                    this.toggleAt(e.clientX, e.clientY);
                    this.lastTap = 0;
                } else {
                    this.lastTap = now;
                }
            }
        },
    }"
    x-on:open-lightbox.window="openWith($event.detail)"
    x-show="open"
    x-cloak
    x-transition.opacity
    x-on:click.self="close()"
    x-on:wheel.prevent="wheel($event)"
    @keydown.escape.window="open && close()"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    class="overlay-center"
    style="position:fixed; inset:0; z-index:70; background:rgba(2,6,23,0.88); touch-action:none; overflow:hidden;"
>
    <button type="button" x-on:click.stop="close()" class="theme-toggle"
            style="position:absolute; top:1rem; right:1rem; z-index:3; background:rgba(255,255,255,0.14); color:#fff; border-color:rgba(255,255,255,0.25);"
            aria-label="Tutup">
        <i class="fa-solid fa-xmark"></i>
    </button>

    {{-- Tombol zoom — terutama untuk yang tidak nyaman cubit / tidak punya mouse scroll. --}}
    <div x-on:click.stop
         style="position:absolute; bottom:3rem; left:50%; transform:translateX(-50%); z-index:3; display:flex; align-items:center; gap:0.4rem; padding:0.3rem; border-radius:9999px; background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.18);">
        <button type="button" x-on:click="zoomBy(1 / 1.5)" aria-label="Perkecil"
                style="width:2.5rem; height:2.5rem; border-radius:9999px; border:0; background:transparent; color:#fff; font-size:1.1rem; cursor:pointer;">
            <i class="fa-solid fa-magnifying-glass-minus"></i>
        </button>
        <button type="button" x-on:click="reset()" aria-label="Kembali ke ukuran awal"
                style="min-width:3.4rem; height:2.5rem; border-radius:9999px; border:0; background:rgba(255,255,255,0.12); color:#fff; font-size:0.8rem; font-weight:700; cursor:pointer;"
                x-text="Math.round(scale * 100) + '%'"></button>
        <button type="button" x-on:click="zoomBy(1.5)" aria-label="Perbesar"
                style="width:2.5rem; height:2.5rem; border-radius:9999px; border:0; background:transparent; color:#fff; font-size:1.1rem; cursor:pointer;">
            <i class="fa-solid fa-magnifying-glass-plus"></i>
        </button>
    </div>

    <p style="position:absolute; bottom:0.9rem; left:50%; transform:translateX(-50%); width:100%; font-size:0.72rem; color:rgba(255,255,255,0.6); text-align:center; z-index:2; padding:0 1rem; pointer-events:none;">
        <span class="lightbox-hint-touch">Cubit dua jari untuk zoom &middot; ketuk dua kali untuk zoom/kembali &middot; geser saat diperbesar</span>
        <span class="lightbox-hint-mouse">Scroll untuk zoom &middot; klik-tahan untuk geser &middot; klik dua kali untuk zoom/kembali</span>
    </p>

    <img
        x-ref="img"
        :src="src"
        :alt="alt"
        x-on:click.stop
        x-on:pointerdown.prevent="down($event)"
        x-on:pointermove="move($event)"
        x-on:pointerup="up($event)"
        x-on:pointercancel="up($event)"
        x-on:dragstart.prevent
        :style="`display:block; width:auto; height:auto; max-width:92vw; max-height:80vh; min-width:0; min-height:0; margin:auto; object-fit:contain; cursor:${scale > 1 ? (pan ? 'grabbing' : 'grab') : 'zoom-in'}; transform:translate(${tx}px, ${ty}px) scale(${scale}); transition:${animate ? 'transform 0.18s ease' : 'none'}; user-select:none; -webkit-user-select:none; -webkit-touch-callout:none; touch-action:none; border-radius:8px; will-change:transform;`"
        draggable="false"
    >
</div>

<style>
    /* Petunjuk sesuai perangkat: layar sentuh vs mouse. */
    .lightbox-hint-mouse { display: none; }
    @media (hover: hover) and (pointer: fine) {
        .lightbox-hint-touch { display: none; }
        .lightbox-hint-mouse { display: inline; }
    }
</style>
