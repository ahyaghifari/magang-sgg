# Design System — Portal Karyawan (Syifa Global Group)

Reference doc for the employee-facing portal UI (`resources/views/livewire/**`, `resources/views/components/**`, `resources/css/app.css`). Keep this updated when new shared patterns are introduced. Does **not** cover the Filament admin panel (`/admin`), which uses Filament's own theming.

## Stack

- Tailwind CSS v4 (`@import 'tailwindcss'` in `app.css`) + `@tailwindcss/forms`.
- Font: `Plus Jakarta Sans` (`body.site`). `--font-sans` in `@theme` is Instrument Sans but is not what the portal actually renders with — don't rely on it.
- Icons: Font Awesome 6 (`fa-solid`, `fa-regular`), loaded via CDN link in layout/login.
- Compiled via Vite. **The user runs `npm run watch`/`npm run build` themselves** — never run it yourself. If a style change "doesn't show up," the most likely cause is the compiled bundle (`public/build/assets/app-*.css`) is stale, not a code bug. Check file mtimes before assuming a bug.

## Hard rule: inline styles over new Tailwind utility classes

This codebase has been bitten by this repeatedly: newly-introduced Tailwind utility classes (`gap-x-*`, `mr-1.5`, arbitrary bracket values like `text-[19px]`, `shrink-0`, `lg:col-span-2`, etc.) do **not** reliably show up until a rebuild happens, and often the rebuild lags behind edits. Plain hand-written `style="..."` attributes always render immediately because they don't depend on Tailwind's content-scanning/JIT step.

**Convention for this project:** for anything beyond basic, already-proven-safe utility classes (`flex`, `items-center`, `justify-between`, `text-sm`, `text-slate-500`, `mb-6`, `p-5`, `rounded-xl`, `border`, `border-gray-200` — all confirmed working elsewhere), prefer inline `style="..."` for spacing, gaps, grids, and colors. This is why most component blade files in this project look inline-style-heavy rather than utility-class-heavy — it's deliberate, not sloppy.

Reusable, named visual identities (card families, badges, buttons) belong in `resources/css/app.css` as real CSS classes (see below) — those compile reliably since they're plain CSS, not Tailwind-generated utilities.

## Color palette

The three brand colors come straight from the **Syifa Global Group logo** (navy / green / magenta).

**Logo asset resolution** — `App\Support\Brand::logoUrl()` returns the first of `public/images/syifa-logo.{png,jpg,jpeg,webp,svg}` that exists (else the `.png` path regardless). Current file: `syifa-logo.png`, the **transparent** (background-removed) lockup, 644×388. Swapping the logo = drop a new `syifa-logo.*`, no code change. Consumers:
- `<x-app-logo>` (anonymous Blade component, `resources/views/components/app-logo.blade.php`) — pass classes through: `<x-app-logo class="auth-logo" />` (login + register, 210px) and `<x-app-logo class="portal-brand-logo" />` (sidebar header, 150px, above the nav, with a `.portal-brand-tagline` "PORTAL JURNAL MAGANG" under it). Because the logo art is navy/green/magenta, it's invisible on a dark surface — so **dark mode only** wraps it in a white chip (`html.dark .auth-logo` / `.portal-brand-logo` → `background:#fff;padding;border-radius`). Light surfaces are already white, so no chip there (transparent PNG sits directly on them).
- Filament `->brandLogo(fn () => \App\Support\Brand::logoUrl())` (`brandLogoHeight('2.75rem')`) and `->favicon(fn () => \App\Support\Brand::logoUrl())`; the portal `<link rel="icon">` uses the same. One file drives every logo + favicon slot.

| Role | Hex | Usage |
|---|---|---|
| Primary / brand navy | `#042c6c` | Nav, primary buttons, links, focus rings, "current step" accents. Logo navy is ~`#0a2e6e` — near-identical, so `#042c6c` is kept as the token (avoids re-sweeping every literal in `app.css`). |
| Primary hover | `#032356` | Hover state of navy buttons/links |
| Success / brand green | `#1c8a4d` | `--brand-success` — success buttons, "done/approved" states, `.badge-approved`. (Was `#047C54`; retuned to the logo green 2026-09-08.) Dark mode keeps `#34d399`. |
| Accent magenta | `#c74ba0` | `--brand-accent` — **decorative only**: the navy→green→magenta strip on `.portal-sidebar::before` and `.auth-card::before` that echoes the logo. Never a primary UI color. (Was `#B9308C`.) |
| Slate neutrals | `#1e293b` (headings), `#334155`/`#374151` (body/values), `#64748b` (secondary/meta — **minimum** for any readable text), `#94a3b8` (decorative icons / native placeholders **only**, see contrast rule below) |
| Page background | `#f1f5f9` (`body.site`) |

The Filament admin panel (`/admin`) is now **visually unified with the portal** (2026-09-08, was a violet "differentiator"): `->colors(['primary' => Color::hex('#042c6c'), 'success' => '#1c8a4d'])` + brand logo + favicon. The sign-in page in particular is matched to the portal login via a small injected stylesheet, `resources/views/filament/portal-match.blade.php`, registered on `PanelsRenderHook::STYLES_AFTER` — it gives `.fi-simple-main` the `.auth-card` treatment (20px radius, border, shadow, navy→green→magenta `::before` strip), sets the page bg to `#f1f5f9`, rounds inputs/buttons to 12px, and forces the sign-in button to solid navy `#042c6c` + white text (Filament v5's light-mode default for that action is a pale fill + dark text). Font is already shared — `->font('Plus Jakarta Sans')` loads it via bunny.net. No Filament theme build involved; colors + hook render at runtime.

### Contrast rule (do not regress this)

`#94a3b8` / Tailwind `text-slate-400` has ~2.8:1 contrast on white/light backgrounds — **fails WCAG AA**. This was audited and fixed across the whole app (2026-07-05). The rule going forward:

- Any text meant to be **read** (labels, captions, dates, table headers, empty-state messages, hints) must be `#64748b` (`text-slate-500`) or darker — never `#94a3b8`/`text-slate-400`.
- `#94a3b8` is only acceptable for genuinely decorative icon glyphs or native `input::placeholder` styling — not for actual text content.
- Identity/subtitle text that's more important than ambient meta (e.g. a job title under a person's name) should go one step darker still — `#475569`/`text-slate-600`.
- `.data-table thead th` in `app.css` is the single most load-bearing fix here — it controls every table header in the app.

## Dark mode (shipped 2026-07-11)

Portal-wide dark mode is live. **Does not cover `/admin` or `/candidate`** (separate Filament theming, untouched).

- **Toggle:** manual, `localStorage` key `theme`, defaults to `prefers-color-scheme` on first visit. `<x-theme-toggle variant="icon">` sits in `<x-nav>` (desktop). `<x-theme-toggle variant="bar">` sits in `<x-pwa-app-bar>` next to the notification bell (mobile).
- **Mechanism:** class `dark` on `<html>`. One inline script in `layout.blade.php` `<head>` (before `@vite`) holds it all: `applyTheme()` reads `localStorage`/system pref and sets the class before paint (anti-FOUC), `window.toggleTheme()` flips + persists, and — **important** — `applyTheme` is also bound to `document`'s `livewire:navigated` event. Without that re-apply, every `wire:navigate` morphs `<html>` back to the server markup (which has no `dark` class) and the theme visibly resets until the toggle is pressed. Any new page reachable via `wire:navigate` inherits the fix for free; don't re-add a separate FOUC script.
- **Token system:** every theme-aware color is a CSS custom property defined in `app.css` — `:root { ... }` holds the light values (unchanged from before dark mode existed, zero risk), `html.dark { ... }` overrides them. Core tokens: `--surface`, `--surface-alt`, `--surface-page`, `--border`, `--border-soft`, `--text-heading`, `--text-body`, `--text-muted`, `--text-faint`, `--brand`, `--brand-success`, `--shadow-card`, `--danger-text`, `--danger-soft-*`, `--info-soft-*`, `--warn-soft-*`, `--neuro-bg`/`--neuro-shadow-*`.

**Rule going forward: any new color that sits on a themed surface (card background, body/meta text, border) must use `var(--token)`, never a literal hex.** Two things to know before you add one:

1. **Identity/gradient cards are exempt on purpose** — `.welcome-card`, `.absen-card`, `.approval-card`, `.timeline-card`, `.page-hero*`, `.pwa-action-btn*`, `.pwa-card-header-gold/violet`, badges (`.badge-*`), and small self-contained accent tiles (`.pwa-tile-icon`, `.stat-card-deco`) keep their literal hex on purpose — they're fixed-color blocks with contrast already built in (usually white text on a solid/gradient fill), not meant to blend into the page theme. Don't "fix" these by adding `var()` to them.
2. **`--brand`/`--brand-success` swap to lighter tints in dark mode** (`#042c6c` → `#6ea8e0`, `#047C54` → `#34d399`) specifically so navy/green text/numbers stay readable on a dark surface. If you pass a literal `#042c6c`/`#047C54` into a component prop instead (e.g. `leave-balance-card`'s `color="..."` prop), it goes near-invisible in dark mode — **this already happened once** (Cuti Tahunan stat number, 2026-07-11 fix). Pass `color="var(--brand)"` / `color="var(--brand-success)"` through props like this, never the raw hex.

Two more things worth knowing if you touch dark mode again:

- **Tailwind gray/slate utility classes** (`bg-white`, `text-gray-500`, `border-gray-200`, etc. — mostly in `organization-structure.blade.php` / `organization-chart-widget.blade.php` / `edit-my-employee.blade.php`) aren't converted per-occurrence. Instead `app.css` has a blanket `html.dark .bg-white { background-color: var(--surface) !important; }` (and siblings for `bg-gray-*`, `text-gray-*`, `border-gray-*`, `text-slate-*`) — cheaper than rewriting every file, and safe because `html.dark` never gets set outside the portal layout (Filament renders its own root template, no toggle/FOUC-script there).
- **Domain-color pills** (`.section-nav-btn.navy/green/rust/teal`, `.doc-type-btn`) needed explicit `html.dark` overrides of their own: the light-mode look is a very low-alpha rgba tint (`rgba(4,44,108,0.06)`) that reads as basically invisible on a dark surface. The dark variant boosts alpha and swaps to a lighter accent color per domain — see the `html.dark .section-nav-btn.*` block right after `.section-nav-btn` in `app.css`. If you add a new domain-color pill, give it the same treatment.

## Card identity system

Each "domain" of the app gets **one** recognizable dark/colored gradient or solid card treatment, reused everywhere that domain appears (Dashboard, Karyawanku, etc.) so users recognize it at a glance without reading text. Don't reuse another domain's identity color for a new feature — give it its own if it's genuinely a new domain, but don't invent one for something that already fits an existing card family.

| Class | Domain | Look |
|---|---|---|
| `.welcome-card` | Greeting / quick actions | Navy gradient `#042c6c → #0b47a1`, white text, `.w-deco-*` decorative circles |
| `.absen-card` | Presence / clock-in (`.a-deco-*`) | Green gradient `#047C54 → #024732`, white text |
| `.approval-card` | "Butuh Persetujuan Anda" (pending approvals inbox — Dashboard, MyStaff) | Solid-feeling orange gradient `#9a3412 → #ea580c → #fb923c` (darker stop anchored under the header text for contrast), white text, `.appr-deco-*` |
| `.timeline-card` | Approval status timeline (`x-approval-timeline` component) | Solid soft yellow `#FCF0D6` with `#F0DBA3` border (no gradient — deliberately flat), **dark** text (`#422006` / `#374151` / `#64748b`), `.tl-deco-*` |
| `.neuro` | Saldo cuti / stat widgets | Light neumorphic double-shadow (`6px 6px 14px #c5ccd8, -6px -6px 14px #fff`), cool gray `#e8edf6` base |
| `.stat-card` | Plain white stat cards | Flat white, subtle shadow, `.stat-card-deco` faint corner circle |

All dark/colored identity cards follow the same anatomy: `border-radius:20px`, `position:relative;overflow:hidden`, 2–3 decorative circles/rotated-square shapes at low opacity (`.{prefix}-deco-1/2/3`), content wrapped in its own `position:relative;z-index:1` div so it sits above the decorations.

**Text-color rule per card type:** dark/saturated gradient cards (navy, green, dark-orange) → white text. Light/pastel cards (soft yellow timeline) → dark warm-toned text (`#422006` family), never white — white-on-yellow fails contrast immediately.

**Soft-UI (neumorphism) note:** neumorphism is inherently a low-color technique — the "raised from one surface" illusion depends on the element and background being close in hue/lightness, with depth coming from a light/dark shadow pair, not color. If a component needs both the soft-raised feel *and* a strong identity color, the working pattern from this app is: neutral raised surface (shadows carry the depth) + a colored **border ring** + a colored **icon** — not a solid color fill. See the timeline step badges (`approval-timeline.blade.php`) for the reference implementation.

## Components

- **Badges**: `.badge` + `.badge-approved` (green) / `.badge-pending` (amber) / `.badge-rejected` (red) / `.badge-cancelled` (gray). Always pair the two classes.
- **Buttons**: `.btn-primary` (solid navy), `.btn-ghost` (outlined gray, for light-background contexts), `.qa-btn` (translucent white pill, for buttons sitting on top of dark gradient cards — e.g. inside `.welcome-card`), `.section-nav-btn` (icon + label, color-coded per domain, active = solid fill — for switching between sections within one page, see Layout conventions below).
- **Tables**: `.data-table` — uppercase `#64748b` headers, hover row tint, borderless last row. Used for every list (Riwayat Pengajuan, Data Presensi, pending approvals, etc.)
- **Forms**: `.form-label`, `.form-input`, `.toggle`, `.time-grid`/`.date-grid` for paired inputs, `.upload-zone` for file drop areas. These came from the mockup HTML in `planning/web-attendance/demo/` and should stay consistent with that source design — don't reinvent form styling per page.
- **Field-row pattern** (label left, value right, hairline divider): used in the Karyawanku profile tab (`employee-attendance-statistics.blade.php`) for scannable label/value lists. Prefer this over floating label-above-value grid cells for anything with more than ~4 fields.

## Portal shell — sidebar layout (2026-09-08)

The authenticated portal (`/home`, `/jurnal`) renders inside a **fixed left sidebar + content column** shell, built directly into `resources/views/components/layouts/app.blade.php`. It is gated by `@auth` — the login screen (`guest` middleware) falls through to `@else {{ $slot }} @endauth` and renders bare, exactly as before. Because every full-page Livewire component in this app uses `#[Layout('components.layouts.app')]`, the shell is defined once here and each page view only emits its own content (starting with `<h1 class="portal-title">`).

- **Sidebar** (`.portal-sidebar`, 264px): brand mark + nav links (`.portal-nav-link`, `.active` = solid navy `#0b1739` pill, dark-mode `#1d4ed8`) + a bottom `.portal-sidebar-foot` with the user identity block (`.portal-user` / `.portal-user-avatar`) and a **plain POST `<form action="{{ route('logout') }}">`** (`.portal-logout`) — not a Livewire action, so it works from any page without the layout being a component.
- **Active state**: `request()->routeIs('home')` / `request()->routeIs('journals.*')` in the blade. Add a route group prefix here when you add a nav item.
- **Topbar** (`.portal-topbar`, sticky): hamburger on the left (mobile only), theme toggle on the right. Page titles live in the page body (`.portal-title`), not the topbar.
- **Responsive**: all breakpoint behaviour is in `app.css` media queries (`@media (min-width: 1024px)`), not Tailwind `lg:` utilities — per the "inline over new utility classes" rule below. Under 1024px the sidebar is an off-canvas drawer (`transform: translateX(-100%)` → `.is-open`), toggled by Alpine `x-data="{ nav: false }"` on `.portal-shell`, with a `.portal-scrim` backdrop (`x-cloak`) and Escape-to-close. At/above 1024px the sidebar is static and `.portal-content` gets `margin-left: 264px`.
- All shell classes theme via the existing `var(--token)` system; `.portal-brand-mark` / `.portal-user-avatar` keep the literal navy gradient (identity blocks, exempt).

## Auth & self-registration with admin approval (2026-09-08)

- **Routes** (`guest` middleware): `login` (`/login`) and `register` (`/register`), both Livewire full-page components under `App\Livewire\Auth\*`, both on `components.layouts.app` (which renders bare for guests via its `@else` branch).
- **`users.approved_at`** (nullable timestamp) + **`users.approved_by`** (nullable FK → users) drive it. `null` = pending. The migration back-fills every existing row to `now()` so current accounts keep working. `approved_at` / `approved_by` are `$fillable` and `approved_at` is cast to `datetime`. Helpers on `User`: `isApproved()`, `scopePending()`, `approvedBy()` relation.
- **Register** collects nama, email, **institusi** (`select` from `institutions`), **jenis_kelamin** (L/P), password. In one DB transaction it creates the `User` (`approved_at = null`), assigns the `peserta` role **only if that role already exists** (Shield-managed — never auto-create it here), and creates the linked `Intern` row (`nama` = name, `institusi_id`, `jenis_kelamin`). Then flashes `status` and redirects to `login` **without logging in**. If no `institutions` exist yet the form shows a hint and `required` blocks submit — admin must seed institutions first.

### Pembimbing view — `/kegiatan` (2026-09-08)

`App\Livewire\Pembimbing\Activities` (route `pembimbing.activities`, portal layout). A feed of every `Journal` from every `Intern` — date, intern name + institusi + unit badges, activity text, attachments (same attachment rendering as `journals/index`). Filter by peserta (`select`) + free-text search (nama / isi kegiatan). **Paginated by day, not by row** (`DAYS_PER_PAGE = 7`): `render()` pages the list of `distinct('date')` values, then fetches *all* journals whose `date` is in that page's set and wraps them in a hand-built `LengthAwarePaginator` (total = distinct-day count) so `WithPagination`'s `previousPage`/`nextPage` + the blade controls still work. A day is therefore **never split across pages**. The blade then emits a day header ("Hari Ini" / "Kemarin" / weekday name + `d F Y`) whenever `date->toDateString()` changes; the per-card date line was dropped since the header carries it. Shared filter logic lives in `baseQuery()`. `mount()` **redirects** a non-(pembimbing|admin) to `route('home')` (no `abort(403)`); `rate()` / `clearRating()` silently no-op when `! canReview()` or the journal is gone (a forged or stale star-click never renders a Forbidden page). `canReview()` = pembimbing **or** admin. The sidebar nav is role-aware: pembimbing/admin see only **"Kegiatan Intern"**, interns see **Beranda + Jurnal Harian**. `Home::mount()` redirects a pembimbing straight to `/kegiatan`.

**Star reviews (`journal_reviews`)**: `journal_id` + `user_id` (pembimbing) + `rating` 1–5, `unique(journal_id, user_id)` → one rating per pembimbing per journal. `Journal hasMany reviews`. On `/kegiatan` a pembimbing clicks 1–5 stars per journal (`rate($journalId, $stars)` = `updateOrCreate`) or "hapus" (`clearRating`); `canReview()` = `isPembimbing()` so admins see the numbers but no star buttons. The card shows the **average** (`withAvg('reviews','rating')` + `withCount`) as "★★★★☆ 4.5 (rata-rata dari N pembimbing)" plus a per-pembimbing breakdown. The intern's own `/jurnal` list shows the same average, read-only.

### `users.role` enum (2026-09-08)

`App\Enums\UserRole` (string-backed, implements Filament `HasLabel` + `HasColor`): `Admin` / `User` / `Intern` / `Pembimbing`. Column `users.role` (MySQL `enum`, default `user`), cast on the model. The migration back-fills from Shield roles (`super_admin`/`admin` → `admin`, `peserta` → `intern`). `User::isAdmin()` now = `role === Admin` **or** Shield `admin`/`super_admin` (so `canAccessPanel()` still works either way); plus `isIntern()` / `isPembimbing()`. Registration sets `role = Intern`. Filament `UserResource` gained a role `Select` (`->options(UserRole::class)`), a badge column, and a `SelectFilter`. Shield is untouched — the enum is the app-level user type, Shield still drives panel permissions/policies. Pembimbing↔intern assignment and journal review are **not** built yet.

### Org structure: Company → Unit → {User, Intern} (2026-09-08)

Separate from `institutions` (an intern's origin school/campus). `companies` (name, address) `hasMany` `units` (`company_id`, name — "IT", "Humas"); a `unit` `hasMany` `users` and `hasMany` `interns`. Both `users.unit_id` and `interns.unit_id` are **nullable** FKs (`nullOnDelete`) — admins aren't tied to a unit, and a freshly-registered intern has no placement until an admin sets it. `interns` keeps `institusi_id` (origin) *and* gains `unit_id` (placement) — different things. Admin CRUD via `CompanyResource` / `UnitResource`; the Intern and User resource forms/tables gained a Unit select + column (labelled `"{company} — {unit}"` since unit names repeat across companies). Registration does **not** ask for a unit yet — admin assigns it.
- **Login** blocks sign-in when `! $user->isApproved() && ! $user->isAdmin()` — logs them straight back out and shows a validation error. Admins are exempt so a pending flag can never lock the panel out.
- **Filament** (`UserResource`): a virtual `approval_status` badge column (Disetujui / Menunggu), a `TernaryFilter` on `approved_at`, per-row **Setujui** / **Tolak** actions (visible only while pending; Tolak deletes the pending row), a **Setujui terpilih** bulk action, and a nav badge = pending count (`getNavigationBadge()`, warning color). Admin-created users default to `approved_at = now()` (pre-approved) via the form's `DateTimePicker` default.
- **Panel access:** `User implements FilamentUser`; `canAccessPanel()` → `hasAnyRole(['admin', 'super_admin'])` (the Shield roles — that's what actually grants the resource permissions; the `role` enum's `admin` case alone is **not** enough, or you'd get into the panel then hit 403 on every resource). A logged-in non-admin (peserta / pembimbing) at `/admin` is **redirected to `route('home')`, not 403** — via `App\Http\Middleware\Authenticate`, a subclass of `Filament\Http\Middleware\Authenticate` that swaps the `abort(403)` for a redirect (guests and admins behave exactly as before). Registered through the panel's `authMiddleware`. Filament hoists `authMiddleware` ahead of the panel `middleware` list, so a plain middleware appended to `->middleware([...])` can't pre-empt the 403 — subclassing is the way.
- **Portal ↔ admin share the `web` guard + session** (one logged-in user app-wide). The admin panel runs `AuthenticateSession` middleware; the portal routes don't. So `Login::login()` explicitly writes `session('password_hash_'.config('auth.defaults.guard'))` = the new user's hash after `regenerate()` — otherwise a leftover hash from a previous admin login in the same browser makes `AuthenticateSession` log the portal session out at `/admin`, and a stale "remember me" cookie for the old admin account silently signs back in (looks like the account swapped itself). A full portal "Keluar" (`session()->invalidate()` + guard logout, which also forgets the recaller cookie) clears any such stuck state.

## Journal attachment upload — client-side image compression

The "isi jurnal" modal (`livewire/journals/create.blade.php`) has a **"Kecilkan ukuran foto otomatis"** checkbox bound to `Create::$compressImages` (`wire:model.live`, default on). Photo `<input>`s don't use `wire:model`; their `x-on:change` calls `window.compressImageFile()` (a plain helper defined once in `layout.blade.php`, canvas downscale to 1600px longest edge + JPEG re-encode at 0.7) and then `$wire.upload('items.N.file', file, ...)` manually. Non-images, GIFs, and any failure fall through to the original file untouched. PDF inputs keep plain `wire:model` (no client-side compression). Server validation (`file`, `max:5120`, mime checks in `Create::save()`) is unchanged and still authoritative.

The **"Kamera"** button opens the phone camera in one tap: a single hidden `<input type="file" accept="image/*" capture="environment" x-ref="cameraInput">` sits above the attachment buttons; the button is `x-on:click="$refs.cameraInput.click()"` (synchronous, inside the tap gesture — a second tap or an expired user-activation would break it). Its `x-on:change` compresses, then `await $wire.addItem('photo', true)` and `$wire.upload('items.N.file', …)` where `N = $wire.items.length - 1`. `Create::addItem($type, $capture = false)` stores a `capture` flag on the item (badge shows "Kamera", the per-item photo `<input>` also gets `capture="environment"` for re-takes). `items.*.file` truthy → "Foto terpasang" hint.

## Layout conventions

- Page header pattern: **use `<x-page-header title="..." />`** (`resources/views/components/page-header.blade.php`) — every page's header (date + live clock + title, optional `subtitle`/`back` props, `size="lg"` for Dashboard) goes through this one component now. Don't hand-roll the date/title markup per page again; that was the pre-refactor state and it drifted (9 near-duplicate copies, some missing the live clock). If a page needs a header shape the component can't do, extend the component with a new prop rather than bypassing it.
- Section nav (when a page has multiple concerns, e.g. MyStaff's Profil / Presensi & Absensi / Riwayat Pengajuan): plain Alpine (`x-data="{ tab: '...' }"`, `x-show`) for state, but rendered as real **pill buttons** (`.section-nav` + `.section-nav-btn`), not underline tabs — underline tabs read as plain text on mobile and are easy to mis-tap. `.section-nav` is `flex-wrap`, not a fixed-column grid, so adding more buttons later just wraps to the next line instead of squeezing existing ones — no CSS changes needed. Each button gets its own domain color (`.navy`/`.green`/`.rust` modifier, matching the card identity system below) plus a Font Awesome icon, active state = solid color fill.
- Multi-card responsive grids: prefer `display:grid;grid-template-columns:repeat(auto-fit,minmax(Npx,1fr));gap:...` inline over `.card-pair`/bento-grid classes when the surrounding page isn't the Dashboard's bento grid — `.card-pair` specifically relies on `display:contents` + a parent bento grid with named `grid-column` placement classes, and does nothing on its own outside that context.
- Dense multi-field data (profile info, employment info) uses the field-row pattern above, not a raw CSS grid of stacked label/value cells — better line-by-line scanability.

## Mobile navigation — bottom nav, not a hamburger drawer (2026-07-12)

The mobile portal used to have a hamburger button in `<x-pwa-app-bar>` opening an off-canvas `<x-pwa-sidebar>` drawer. **Both are gone** — reaching the top-left corner on every navigation was flagged as bad mobile UX. Mobile nav is now `<x-pwa-bottom-nav>` (`components/pwa-bottom-nav.blade.php`), included once in `layout.blade.php` inside `<div class="md:hidden">`, right before `@livewireScripts`.

- **5 fixed slots**, left to right: Beranda (`dashboard`), Presensi (`data-diri`), a raised circular **center button**, Pengajuan Absensi (`my-attendance-requests`) — swapped for Struktur Organisasi when the user can't submit their own attendance (`canBrowseAllCompanies()`, e.g. a holding Direktur), Akun (`akun`).
- **Center button** (`.pwa-bottom-nav-center-btn`, solid navy circle floating above the bar) opens a bottom sheet (`.pwa-menu-sheet` + `.pwa-menu-sheet-backdrop`, `togglePwaMenuSheet()` in the same component) instead of navigating directly — the shortcuts inside it are role-dependent and a single user can have more than one (e.g. a supervisor who's also Finance sees both MyStaff and the Keuangan tiles), so it can't collapse to one destination.
- Sheet content is a **3-column icon grid** (`.pwa-menu-grid` / `.pwa-menu-grid-item` / `.pwa-menu-grid-icon.{navy,gold,violet,green,teal}`), one solid-color tile per shortcut — same guard variables as before (`canAccessKaryawanMenu()`, `SISTEM_KEUANGAN` permission, `canSubmitAttendance`) gating MyStaff / Komponen Gaji / Mapping Komponen Gaji / Insentif / Struktur Organisasi. These tiles are identity-color blocks (white icon on solid fill), so they're exempt from the dark-mode `var(--token)` rule same as `.pwa-action-btn`/card-identity system above — don't "fix" them.
- **Not** in the sheet or bottom nav: Ubah Password, Logout, Admin Panel. Ubah Password + Logout live only on the Akun page (`resources/views/livewire/pwa/akun.blade.php`) now — don't re-add them to the sheet, that would duplicate them. Admin Panel was dropped because Admin/Super Admin roles are redirected straight to `/admin` on login and never really live in the portal.
- `.page-content` gets extra `padding-bottom` under 768px (see the media query right after the `.page-content` rules in `app.css`) so page content doesn't render underneath the fixed bottom bar — if you add another fixed-position mobile element, check that padding still clears it.
- App bar title (`.pwa-app-bar-title`) was bumped to `18px` now that the hamburger button is gone and there's more room.

## Responsive requirement — every page must have a mobile layout, not just a shrunk desktop one

This is a hard rule, not a nice-to-have: **every portal page must be designed for both mobile and desktop**, not just wrapped in `overflow-x:auto` and called done. The breakpoint is Tailwind `md:` (768px), matching the switch between `<x-pwa-app-bar>`+`<x-pwa-bottom-nav>` (mobile) and `<x-nav>` (desktop) in `components/layout.blade.php`.

**The pattern** (see `MyAttendanceRequests`/`my-attendance-requests.blade.php`, `MyEmployees`/`my-employees.blade.php`, and `pending-approvals-card.blade.php` for reference implementations):
- Data that reads fine as a table on desktop (Filament Table, `.data-table`) becomes a **card/list layout on mobile** (`.pwa-list` + `.pwa-list-row` + `.pwa-tile-icon`, the same components the PWA redesign introduced) — not the same table squeezed into a horizontal scroll. Render both, wrap the mobile version in `<div class="md:hidden">` and the desktop version in `<div class="hidden md:block">` (or `md:grid`/`md:flex` if the desktop variant needs that display type).
- Components meant to appear in multiple contexts (`<x-approval-timeline>`, leave-balance/stat cards) should own their own responsive behavior internally (render both variants, toggle via the same `md:hidden`/`hidden md:block` pattern) rather than take a `vertical`/`compact` boolean prop that forces the caller to pick one — the caller shouldn't have to know or guess the viewport.
- When a component needs BOTH Filament's query/pagination machinery AND a separate manual query for the mobile card list (e.g. `MyAttendanceRequests`, `MyEmployees`): extract the shared query into one `baseQuery()`/protected method, call it from both the Filament `table()` method and a `getXProperty()` used by the mobile Blade — don't duplicate the scoping logic.
- Pagination: if a component already uses `Filament\Tables\Concerns\InteractsWithTable` (which internally composes `Livewire\WithPagination`, aliased to avoid collision), do **not** also add `use WithPagination;` directly — that's a trait method collision (fatal error). Plain `->paginate(n)` still works for the mobile query without it.

**Cascade-layer trap to avoid:** don't combine Tailwind's `hidden`/`md:hidden` with a custom `app.css` class that also sets `display` unconditionally on the *same element* — this project's custom CSS is unlayered (written after `@import 'tailwindcss'` with no `@layer` wrapper), so it always wins the cascade over Tailwind's layered utilities, even across a matching media query. Concretely: `<div class="md:hidden my-custom-grid-class">` where `.my-custom-grid-class { display: grid; }` is defined with no media query will **stay visible above the `md` breakpoint** because the unlayered rule beats `.md\:hidden`'s layered one. Fix: give the custom class its own `display: none` + `@media (min-width: 768px) { display: grid }` pair (see `.bento-side-stats-compact`/`.bento-side-stats` in `app.css`) instead of relying on a Tailwind visibility utility to hide it.

## Testing & safety (unrelated to visual design, but load-bearing)

- `phpunit.xml` must always stay `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:`. Never point it at a real database — see `knowledge/changes/2026-07-05-*.md` for the incident this caused.
- Don't run or propose `php artisan test` unless explicitly asked, especially during rapid visual/CSS iteration — it's noise the user didn't request in that moment.
