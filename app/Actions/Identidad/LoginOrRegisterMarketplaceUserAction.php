<?php

namespace App\Actions\Identidad;

use App\Models\Identidad;
use App\Models\IdentidadOauth;
use App\Models\UsuarioMarketplace;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Idempotente por `provider + provider_subject` (constitution.md regla 11): un mismo
 * usuario de Google nunca duplica identidad/usuario marketplace, sin importar cuántas
 * veces inicie sesión.
 */
class LoginOrRegisterMarketplaceUserAction
{
    public function execute(SocialiteUser $googleUser): UsuarioMarketplace
    {
        return DB::transaction(function () use ($googleUser) {
            $oauth = IdentidadOauth::where('provider', 'GOOGLE')
                ->where('provider_subject', $googleUser->getId())
                ->first();

            if ($oauth) {
                $usuario = $oauth->usuarioMarketplace;

                $oauth->update([
                    'email' => $googleUser->getEmail(),
                    'nombre' => $googleUser->getName(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);

                $usuario->update(['ultimo_acceso_at' => now()]);

                return $usuario;
            }

            $identidad = Identidad::create([
                'tipo' => 'MARKETPLACE',
                'email' => $googleUser->getEmail(),
                'estado' => 'ACTIVO',
            ]);

            $usuario = UsuarioMarketplace::create([
                'identidad_id' => $identidad->id,
                'nombre' => $googleUser->getName(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'ultimo_acceso_at' => now(),
                'activo' => true,
            ]);

            IdentidadOauth::create([
                'usuario_marketplace_id' => $usuario->id,
                'provider' => 'GOOGLE',
                'provider_subject' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'nombre' => $googleUser->getName(),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);

            return $usuario;
        });
    }
}
