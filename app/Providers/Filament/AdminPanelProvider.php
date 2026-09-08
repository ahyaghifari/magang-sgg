<?php

namespace App\Providers\Filament;

use App\Http\Middleware\Authenticate;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
            // Tombol kembali di bawah form login admin.
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => '<a href="'.e(url('/')).'" class="portal-back-link">'
                    .'<span aria-hidden="true">&larr;</span> Kembali</a>',
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                // Subclass Filament\...\Authenticate: non-admin yang sudah login
                // diarahkan ke portal, bukan kena 403.
                Authenticate::class,
            ]);
    }
}
