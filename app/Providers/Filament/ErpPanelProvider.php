<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Filament\Erp\Widgets\TenantSwitcher;
use App\Http\Middleware\AuditarAccesoDenegadoFilament;
use App\Http\Middleware\CheckSessionExpiration;
use App\Http\Middleware\ForzarCambioPasswordMiddleware;
use App\Http\Middleware\SetTallerActivo;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ErpPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('erp')
            ->path('erp')
            ->viteTheme('resources/css/filament/erp/theme.css')
            // 014-notificaciones: campana del topbar (App\Livewire\NotificacionesBell, registrada
            // en AppServiceProvider). Mismo patrón que la documentación oficial de Filament v5
            // para integrar un componente Livewire de terceros vía render hook.
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('@livewire(\'notificaciones-bell\')'),
            )
            ->login(Login::class)
            ->authGuard('sistema')
            ->brandLogo(asset('logoapp.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('logoapp.svg'))
            ->font('DM Sans')
            // Paleta Awesomic — misma paleta que AdminPanelProvider, ver notas ahí.
            ->colors([
                'primary' => '#09090b',    // obsidian
                'secondary' => '#18181b',  // graphite
                'danger' => '#ff5a00',     // ember
                'warning' => '#ff5a00',
                'success' => '#22c55e',
                'info' => '#52525b',       // steel
                // Array literal, no closure: ver nota en AdminPanelProvider.
                'gray' => [
                    50 => '#f4f4f5',  // paper
                    100 => '#ececee', // cloud
                    200 => '#d4d4d8', // mist
                    300 => '#a1a1aa', // ash
                    400 => '#71717a', // fog
                    500 => '#52525b', // steel
                    600 => '#3f3f46', // iron
                    700 => '#27272a', // slate
                    800 => '#18181b', // graphite
                    900 => '#09090b', // obsidian
                ],
            ])
            ->discoverResources(in: app_path('Filament/Erp/Resources'), for: 'App\Filament\Erp\Resources')
            ->discoverPages(in: app_path('Filament/Erp/Pages'), for: 'App\Filament\Erp\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Erp/Widgets'), for: 'App\Filament\Erp\Widgets')
            ->widgets([
                AccountWidget::class,
                TenantSwitcher::class,
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
                // 015-auditoria: ver AdminPanelProvider.
                AuditarAccesoDenegadoFilament::class,
                CheckSessionExpiration::class,
                // NO se usa ->tenant() nativo de Filament (decisión 2026-07-26): ya existe
                // BelongsToTaller + sesión funcionando y probado; usar ambos duplicaría la
                // fuente de verdad del tenant activo. SetTallerActivo resuelve la sesión.
                SetTallerActivo::class,
                ForzarCambioPasswordMiddleware::class.':erp',
            ]);
    }
}
