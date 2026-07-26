<?php

namespace App\Filament\Erp\Pages;

use App\Filament\Concerns\InteractsWithCambioPassword;
use Filament\Pages\Page;

/**
 * Página forzada por `App\Http\Middleware\ForzarCambioPasswordMiddleware` cuando
 * `debe_cambiar_password=true` o la contraseña expiró. No aparece en el sidebar.
 */
class CambiarPassword extends Page
{
    use InteractsWithCambioPassword;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Cambiar contraseña';
}
