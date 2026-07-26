<?php

namespace App\Filament\Concerns;

use App\Actions\Identidad\CambiarPasswordAction;
use App\Exceptions\BusinessException;
use App\Models\UsuarioSistema;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as FormComponent;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * Formulario de cambio de contraseña obligatorio, compartido por
 * `App\Filament\Admin\Pages\CambiarPassword` y `App\Filament\Erp\Pages\CambiarPassword`
 * (resuelve el pendiente de 001-identidad-autenticacion). Reutiliza `CambiarPasswordAction`
 * (validación de historial de 5 contraseñas ya implementada ahí) en vez de duplicar esa lógica.
 *
 * Rate limit propio (`throttle:3,1`, spec 001) vía `WithRateLimiting`: Filament nativo solo
 * cubre el login (5 intentos/60s), no esta página.
 */
trait InteractsWithCambioPassword
{
    use WithRateLimiting;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getUsuario(): UsuarioSistema
    {
        return Filament::auth()->user();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('password_actual')
                    ->label('Contraseña actual')
                    ->password()
                    ->revealable()
                    ->currentPassword(guard: 'sistema')
                    ->required(),
                TextInput::make('password_nueva')
                    ->label('Nueva contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(12)->mixedCase()->numbers()->symbols())
                    ->same('password_confirmacion'),
                TextInput::make('password_confirmacion')
                    ->label('Confirmar nueva contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return FormComponent::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('cambiarPassword')
            ->footer([
                SchemaActions::make([$this->getGuardarAction()])->key('form-actions'),
            ]);
    }

    protected function getGuardarAction(): Action
    {
        return Action::make('guardar')
            ->label('Cambiar contraseña')
            ->submit('cambiarPassword');
    }

    public function cambiarPassword(): void
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->danger()
                ->title("Demasiados intentos, espera {$exception->secondsUntilAvailable} segundos.")
                ->send();

            return;
        }

        $data = $this->form->getState();

        try {
            app(CambiarPasswordAction::class)->execute($this->getUsuario(), $data['password_nueva']);
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Contraseña actualizada.')->send();

        $this->redirect(Filament::getCurrentPanel()->getUrl());
    }
}
