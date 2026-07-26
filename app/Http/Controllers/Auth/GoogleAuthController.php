<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Identidad\LoginOrRegisterMarketplaceUserAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(LoginOrRegisterMarketplaceUserAction $action): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $usuario = $action->execute($googleUser);

        // Sin "remember me": la persistencia de 7 días la da SESSION_LIFETIME, no un remember_token
        // (UsuarioMarketplace lo tiene deshabilitado, ver el modelo).
        auth('web')->login($usuario);

        request()->session()->regenerate();

        return redirect()->intended('/');
    }
}
