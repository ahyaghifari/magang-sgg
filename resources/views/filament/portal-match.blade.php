<style>
    /* Samakan tampilan panel admin — terutama halaman sign in — dengan portal peserta.
       Font ('Plus Jakarta Sans') & warna primary (navy) sudah diatur dari AdminPanelProvider;
       di sini tinggal menyamakan bentuk kartu, background, dan sudut input/tombol. */

    .fi-simple-layout {
        background-color: #f1f5f9;
    }
    .dark .fi-simple-layout {
        background-color: #020617;
    }

    /* Kartu login — tiru .auth-card portal (radius besar + strip gradient di atas). */
    .fi-simple-main {
        position: relative;
        overflow: hidden;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 18px 50px -18px rgba(4, 44, 108, 0.25);
    }
    .dark .fi-simple-main {
        border-color: #1e293b;
        box-shadow: 0 18px 50px -18px rgba(0, 0, 0, 0.6);
    }
    .fi-simple-main::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, #042c6c 0%, #1c8a4d 55%, #c74ba0 100%);
    }

    /* Sudut input & tombol dibulatkan seperti .form-input / .btn-primary portal (±12px). */
    .fi-simple-main :is(.fi-input-wrp, .fi-btn) {
        border-radius: 0.75rem;
    }

    /* Tombol "Sign in": solid navy + teks putih, persis seperti .btn-primary portal
       (default Filament v5 di light mode = biru muda + teks gelap). */
    .fi-simple-main .fi-btn.fi-color-primary,
    .fi-simple-main .fi-btn.fi-color-primary:hover {
        color: #fff;
    }
    .fi-simple-main .fi-btn.fi-color-primary {
        background-color: #042c6c;
    }
    .fi-simple-main .fi-btn.fi-color-primary:hover {
        background-color: #032356;
    }
</style>
