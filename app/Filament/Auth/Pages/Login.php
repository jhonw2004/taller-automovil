<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return Filament::getCurrentPanel()?->getId() === 'erp'
            ? 'Panel de tu taller'
            : 'Super administración';
    }

    public function getSubheading(): ?string
    {
        return Filament::getCurrentPanel()?->getId() === 'erp'
            ? 'Ingresa con tu usuario del sistema para gestionar tu taller.'
            : 'Acceso exclusivo para el equipo de TallerPro.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Usuario')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
