<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Magang Syifa Global Group · Admin')
            ->brandLogo(fn (): string => \App\Support\Brand::logoUrl())
            ->brandLogoHeight('2.75rem')
            ->favicon(fn (): string => \App\Support\Brand::logoUrl())
            ->font('Plus Jakarta Sans')
            ->colors([
                // Disamakan dengan portal peserta: navy sebagai warna utama, hijau brand, netral slate.
                'primary' => Color::hex('#042c6c'), // brand navy (logo Syifa Global Group)
                'success' => Color::hex('#1c8a4d'), // brand green
                'gray' => Color::Slate,             // slate neutrals (sesuai DESIGN.md)
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Samakan tampilan panel (kartu sign in, background, sudut) dengan portal peserta.
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.portal-match'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => '<div style="text-align:center;padding:1rem;font-size:0.75rem;color:var(--gray-400);">&copy; '
                    . date('Y') . ' M.Nasywa Labib &middot; Seluruh hak cipta dilindungi.</div>',
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                // Subclass Filament\...\Authenticate: non-admin yang sudah login
                // diarahkan ke portal, bukan kena 403.
                Authenticate::class,
            ]);
    }
}
