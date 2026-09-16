{{--
    Lightbox foto global — dengar event `open-lightbox` (window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { src, alt } })))
    dari mana pun di halaman, lalu tampil sebagai overlay dengan zoom (scroll mouse) + geser (klik-tahan) tanpa
    pindah tab. Dipasang sekali di layout portal (components/layouts/app.blade.php).
--}}
<div
    x-data="{
        open: false,
        src: null,
        alt: '',
        scale: 1,
        tx: 0,
        ty: 0,
        dragging: false,
        startX: 0,
        startY: 0,
        openWith(detail) {
            this.src = detail.src;
            this.alt = detail.alt || '';
            this.scale = 1;
            this.tx = 0;
            this.ty = 0;
            this.open = true;
        },
        close() {
            this.open = false;
        },
        zoom(e) {
            var delta = e.deltaY < 0 ? 0.2 : -0.2;
            this.scale = Math.min(5, Math.max(1, this.scale + delta));
            if (this.scale === 1) { this.tx = 0; this.ty = 0; }
        },
        toggleZoom() {
            if (this.scale > 1) {
                this.scale = 1; this.tx = 0; this.ty = 0;
            } else {
                this.scale = 2;
            }
        },
        pointerAt(e) {
            var p = e.touches ? e.touches[0] : e;
            return { x: p.clientX, y: p.clientY };
        },
        startDrag(e) {
            if (this.scale <= 1) return;
            this.dragging = true;
            var p = this.pointerAt(e);
            this.startX = p.x - this.tx;
            this.startY = p.y - this.ty;
        },
        onDrag(e) {
            if (! this.dragging) return;
            var p = this.pointerAt(e);
            this.tx = p.x - this.startX;
            this.ty = p.y - this.startY;
        },
        stopDrag() {
            this.dragging = false;
        },
    }"
    x-on:open-lightbox.window="openWith($event.detail)"
    x-show="open"
    x-cloak
    x-transition.opacity
    x-on:click="close()"
    x-on:wheel.prevent="zoom($event)"
    @keydown.escape.window="open && close()"
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
    style="position:fixed; inset:0; z-index:70; display:flex; align-items:center; justify-content:center; background:rgba(2,6,23,0.85); touch-action:none;"
>
    <button type="button" x-on:click.stop="close()" class="theme-toggle"
            style="position:absolute; top:1rem; right:1rem; z-index:2; background:rgba(255,255,255,0.14); color:#fff; border-color:rgba(255,255,255,0.25);"
            aria-label="Tutup">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <p style="position:absolute; bottom:1.1rem; left:50%; transform:translateX(-50%); font-size:0.75rem; color:rgba(255,255,255,0.65); text-align:center; z-index:2; padding:0 1rem;">
        Scroll untuk zoom &middot; klik-tahan untuk geser &middot; klik dua kali untuk reset
    </p>

    <img
        x-show="open"
        :src="src"
        :alt="alt"
        x-on:click.stop
        x-on:dblclick.stop="toggleZoom()"
        x-on:mousedown="startDrag($event)"
        x-on:mousemove.window="onDrag($event)"
        x-on:mouseup.window="stopDrag()"
        x-on:touchstart="startDrag($event)"
        x-on:touchmove.window.passive="onDrag($event)"
        x-on:touchend.window="stopDrag()"
        :style="`max-width:92vw; max-height:88vh; cursor:${scale > 1 ? (dragging ? 'grabbing' : 'grab') : 'zoom-in'}; transform:translate(${tx}px, ${ty}px) scale(${scale}); transition:${dragging ? 'none' : 'transform 0.15s ease'}; user-select:none; border-radius:8px;`"
        draggable="false"
    >
</div>
