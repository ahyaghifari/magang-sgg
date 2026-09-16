<style>
    /* ==========================================================================
       Desain panel admin — menyamakan identitas visual /admin dengan portal
       peserta (lihat DESIGN.md): navy #042c6c, hijau #1c8a4d, aksen magenta
       #c74ba0, radius besar (20px kartu / ±10-12px input-tombol), font Plus
       Jakarta Sans. Warna primary/success & font sudah diatur dari
       AdminPanelProvider (->colors(), ->font()); di sini menyamakan BENTUK
       (sidebar, topbar, kartu/widget, tabel, tombol, modal) lewat satu render
       hook (PanelsRenderHook::STYLES_AFTER) — tanpa build tema Filament
       terpisah, semua kelas di bawah adalah kelas publik Filament v5
       (vendor/filament/*/resources/css) yang di-override lewat urutan cascade
       (rule ini dimuat setelah stylesheet Filament, jadi menang di specificity
       yang sama). Efeknya tampil langsung setelah refresh, tidak perlu
       `npm run build`.
       ==========================================================================
    */

    /* ---- 1. Halaman sign in ------------------------------------------------- */
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

    /* ---- 2. Sidebar --------------------------------------------------------- */
    .fi-sidebar {
        border-inline-end: 1px solid #e2e8f0;
    }
    .dark .fi-sidebar {
        border-inline-end-color: #1e293b;
    }
    /* Strip aksen navy → hijau → magenta di puncak sidebar, echo dari logo — sama seperti .portal-sidebar::before. */
    .fi-sidebar::before {
        content: "";
        position: absolute;
        inset-inline: 0;
        top: 0;
        height: 3px;
        background: linear-gradient(90deg, #042c6c 0%, #1c8a4d 55%, #c74ba0 100%);
        z-index: 1;
    }
    .fi-sidebar-header-ctn {
        border-block-end: 1px solid #e2e8f0;
        margin-block-end: 0.5rem;
    }
    .dark .fi-sidebar-header-ctn {
        border-block-end-color: #1e293b;
    }

    /* Item nav aktif — pill solid navy + teks putih, seperti .portal-nav-link.active. */
    .fi-sidebar-item-btn {
        border-radius: 12px;
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: #0b1739;
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon,
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label {
        color: #fff;
    }
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: #1d4ed8;
    }

    /* ---- 3. Topbar ------------------------------------------------------------ */
    .fi-topbar {
        border-block-end: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .dark .fi-topbar {
        border-block-end-color: #1e293b;
    }

    /* ---- 4. Kartu / section / widget ------------------------------------------ */
    .fi-section:not(.fi-section-not-contained):not(.fi-aside) {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .dark .fi-section:not(.fi-section-not-contained):not(.fi-aside) {
        border-color: #1e293b;
    }

    /* ---- 5. Tabel --------------------------------------------------------------- */
    .fi-ta-ctn {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .dark .fi-ta-ctn {
        border-color: #1e293b;
    }
    /* Header tabel — uppercase, abu-abu muted, mirip .data-table thead th portal. */
    .fi-ta-header-cell {
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        font-weight: 700;
        color: #64748b;
        background-color: #f8fafc;
    }
    .dark .fi-ta-header-cell {
        color: #94a3b8;
        background-color: rgba(255, 255, 255, 0.02);
    }
    /* Tint baris saat hover — navy sangat tipis, bukan abu-abu polos. */
    .fi-ta-row.fi-clickable:hover {
        background-color: rgba(4, 44, 108, 0.04);
    }
    .dark .fi-ta-row.fi-clickable:hover {
        background-color: rgba(110, 168, 224, 0.08);
    }

    /* ---- 6. Tombol & dropdown ---------------------------------------------------- */
    .fi-btn,
    .fi-icon-btn {
        border-radius: 10px;
    }
    .fi-dropdown-panel {
        border-radius: 12px;
    }

    /* ---- 7. Modal ------------------------------------------------------------------ */
    /* Selector matches Filament's own chain (incl. .fi-modal-window-ctn) so specificity
       ties and this rule wins on cascade order — a shorter selector here would lose. */
    .fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen) > .fi-modal-window-ctn > .fi-modal-window {
        border-radius: 20px;
    }

    /* ---- 8. Widget dashboard (stat cards) ------------------------------------------- */
    .fi-wi-stats-overview-stat {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .dark .fi-wi-stats-overview-stat {
        border-color: #1e293b;
    }
    /* Aksen warna kiri per stat — dipasang lewat Stat::make(...)->extraAttributes(['class' => '...']). */
    .stat-accent-navy {
        border-inline-start: 4px solid #042c6c;
    }
    .stat-accent-green {
        border-inline-start: 4px solid #1c8a4d;
    }
    .stat-accent-sky {
        border-inline-start: 4px solid #0284c7;
    }
    .stat-accent-amber {
        border-inline-start: 4px solid #d97706;
    }
</style>
