<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Http\Middleware\AuditarAccesoDenegadoFilament;
use App\Http\Middleware\CheckSessionExpiration;
use App\Http\Middleware\ForzarCambioPasswordMiddleware;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->authGuard('sistema')
            ->viteTheme('resources/css/filament/admin/theme.css')
            // 014-notificaciones: campana del topbar — el super admin también recibe
            // solicitud.aprobada/rechazada (destinatario: quien procesó la solicitud).
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('@livewire(\'notificaciones-bell\')'),
            )
            ->font('DM Sans')
            // Paleta Awesomic (specs/016-ui-design-system/plan.md). El mapeo semántico de
            // `->colors()` complementa el theme.css (que trae la escala `gray`/`primary` completa
            // vía @theme); se mantiene aquí para los colores planos que Filament usa fuera de CSS.
            ->colors([
                'primary' => '#09090b',    // obsidian
                'secondary' => '#18181b',  // graphite
                'danger' => '#ff5a00',     // ember
                'warning' => '#ff5a00',
                'success' => '#22c55e',
                'info' => '#52525b',       // steel
                // Array literal, no closure: ColorManager::getColors() solo evalúa como closure
                // el `$colors` completo pasado a `->colors()`, no valores individuales por color.
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
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
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
                // 015-auditoria: reemplaza Filament\Http\Middleware\Authenticate por una variante
                // que audita "acceso denegado" (auditoria_accesos) antes del abort(403) — ver esa
                // clase.
                AuditarAccesoDenegadoFilament::class,
                CheckSessionExpiration::class,
                // SetTallerActivo NO va aquí (decisión 2026-07-26): el Super Admin no opera
                // "dentro" de un taller, su autorización ya se resuelve con esSuperAdmin() en
                // canAccessPanel(). Ver ErpPanelProvider.
                ForzarCambioPasswordMiddleware::class.':admin',
            ]);
    }
}
