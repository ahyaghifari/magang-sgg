// Foto profil bulat (Beranda intern) — otomatis di-crop tengah jadi persegi lalu
// dikirim ke server sebagai data URL lewat $wire.set(), tanpa perlu geser/zoom manual.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', () => ({
        stage: 'idle', // idle | done
        preview: null,

        reset() {
            this.stage = 'idle';
            this.preview = null;
        },

        onFile(e) {
            const file = e.target.files[0];
            e.target.value = '';
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = new Image();
                img.onload = () => {
                    // Crop tengah otomatis: ambil sisi terpendek supaya jadi persegi, lalu
                    // gambar ke kanvas bulat 400x400 — tidak perlu geser/zoom manual.
                    const side = Math.min(img.naturalWidth, img.naturalHeight);
                    const sx = (img.naturalWidth - side) / 2;
                    const sy = (img.naturalHeight - side) / 2;

                    const canvas = document.createElement('canvas');
                    canvas.width = 400;
                    canvas.height = 400;
                    const ctx = canvas.getContext('2d');
                    ctx.beginPath();
                    ctx.arc(200, 200, 200, 0, Math.PI * 2);
                    ctx.closePath();
                    ctx.clip();
                    ctx.drawImage(img, sx, sy, side, side, 0, 0, 400, 400);

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    this.preview = dataUrl;
                    this.$wire.set('avatarDataUrl', dataUrl);
                    this.stage = 'done';
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        },

        changePhoto() {
            this.stage = 'idle';
            this.preview = null;
        },
    }));
});
