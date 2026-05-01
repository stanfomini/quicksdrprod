<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
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
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->path('internal')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
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
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => request()->boolean('embed') ? self::embedChromeOverrides() : '',
            );
    }

    /**
     * CSS injected when a Filament panel page is loaded with ?embed=1 — hides
     * the panel's own sidebar/topbar/breadcrumbs so the page can be iframed
     * inside the main app's Flux sidebar without two layers of chrome. Also
     * tells the embedded form to post a 'survey.saved' message up to the parent
     * window after a successful save so the wrapper can show a toast.
     */
    protected static function embedChromeOverrides(): string
    {
        return <<<'HTML'
            <style>
                .fi-sidebar,
                .fi-topbar,
                .fi-page-header-breadcrumbs { display: none !important; }
                .fi-main, .fi-main-ctn {
                    margin: 0 !important;
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                    max-width: none !important;
                }
                body { background: transparent !important; }
            </style>
            <script>
                (function () {
                    document.addEventListener('livewire:initialized', function () {
                        if (! window.Livewire) return;
                        window.Livewire.on('saved', function () {
                            try { window.parent.postMessage({ type: 'survey.saved' }, '*'); } catch (e) {}
                        });
                    });
                })();
            </script>
            HTML;
    }
}
