# Plan — Identidad y Autenticación

## Tablas (convenciones generales en `memory/constitution.md`)

### `identidades`
- `tipo` VARCHAR(20) NOT NULL, `CHECK (tipo IN ('MARKETPLACE','SISTEMA'))`.
- `email` VARCHAR(255) NULL UNIQUE, `telefono` VARCHAR(30) NULL.
- `estado` VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', `CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))`.
- Soft delete (`deleted_at`).

### `usuarios_marketplace`
- `identidad_id` FK -> `identidades.id`, UNIQUE.
- `nombre` VARCHAR(255) NOT NULL, `avatar_url` TEXT NULL, `locale` VARCHAR(10) NULL.
- `email_verified_at`, `ultimo_acceso_at` TIMESTAMPTZ NULL. `activo` BOOLEAN DEFAULT TRUE. Soft delete.

### `identidades_oauth`
- `usuario_marketplace_id` FK NOT NULL. `provider` VARCHAR(30) NOT NULL, `CHECK (provider IN ('GOOGLE'))`.
- `provider_subject` VARCHAR(255) NOT NULL. `email`, `nombre`, `avatar_url`, `email_verified_at`.
- `UNIQUE (provider, provider_subject)`, `UNIQUE (usuario_marketplace_id, provider)`.

### `usuarios_sistema`
- `identidad_id` FK -> `identidades.id`, UNIQUE. `username` VARCHAR(50) NOT NULL UNIQUE.
- `nombre` VARCHAR(100) NOT NULL, `apellido` VARCHAR(100) NULL, `ultimo_acceso_at` TIMESTAMPTZ NULL.
- `activo` BOOLEAN DEFAULT TRUE. Soft delete.

### `credenciales_sistema`
- `usuario_sistema_id` FK -> `usuarios_sistema.id`, UNIQUE. `password_hash` TEXT NOT NULL.
- `debe_cambiar_password` BOOLEAN DEFAULT TRUE. `password_changed_at` TIMESTAMPTZ NULL.
- `intentos_fallidos` SMALLINT DEFAULT 0. `bloqueado_hasta` TIMESTAMPTZ NULL.
- `password_expires_at` TIMESTAMPTZ NOT NULL DEFAULT `NOW() + INTERVAL '90 days'`.
- Nunca auditar `password_hash` (ver constitution §7).

### `historial_passwords`
- `usuario_sistema_id` FK NOT NULL. `password_hash` TEXT NOT NULL (hash de la contraseña anterior).
- `created_at` TIMESTAMPTZ NOT NULL DEFAULT NOW() (append-only, sin update ni delete).
- Últimas 5 filas por `usuario_sistema_id` se verifican al cambiar la contraseña para evitar reutilización.

## Guards y providers (Laravel nativo)

```php
// config/auth.php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'usuarios_marketplace'],
    'sistema' => ['driver' => 'session', 'provider' => 'usuarios_sistema'],
],
'providers' => [
    'usuarios_marketplace' => ['driver' => 'eloquent', 'model' => UsuarioMarketplace::class],
    'usuarios_sistema' => ['driver' => 'eloquent', 'model' => UsuarioSistema::class],
],
```

- Middleware `auth:sistema` + `throttle:5,1` en todas las rutas `/erp` y `/admin`.
- Middleware `auth:web` en rutas del marketplace autenticado.
- Middleware `throttle:3,1` en la ruta de cambio de contraseña.

## Paquetes (detalle en `Baterias.md` original, resumen en constitution)

- `laravel/socialite`: flujo OAuth Google, obtiene `provider_subject`, `email`, `nombre`, `avatar`.
- `laravel/breeze --dev` (stack `blade`): scaffolding del marketplace; se elimina registro con contraseña, se reemplaza `AuthenticatedSessionController` para redirigir a Google.
- Filament maneja su propia autenticación para guard `sistema` (paneles `/admin` y `/erp`), sin Breeze/Fortify.

## Modelos

- `Identidad`, `UsuarioMarketplace`, `IdentidadOauth`, `UsuarioSistema`, `CredencialSistema`.
- `UsuarioSistema` implementa `Illuminate\Contracts\Auth\Authenticatable` vía Eloquent estándar; el hash de password vive en `CredencialSistema` (relación 1:1), no en el propio modelo.
- Observer o listener de evento `Illuminate\Auth\Events\Failed` incrementa `intentos_fallidos`; evento `Login` lo reinicia.
- Listener de `Illuminate\Auth\Events\PasswordReset` inserta en `historial_passwords` y mantiene solo últimas 5 filas por usuario.
- Validación de contraseña en Form Request: `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` nativo de Laravel.
- Comprobación de expiración en login: si `NOW() > password_expires_at`, setear `debe_cambiar_password = true` y redirigir a cambio forzado.

## Sesión (config/auth.php y config/session.php)

```php
// config/session.php — guard 'sistema'
'lifetime' => 30,  // minutos de inactividad
'expire_on_close' => false,

// config/session.php — guard 'web' (marketplace)
'lifetime' => 10080,  // 7 días (60*24*7)
```

- Middleware personalizado `App\Http\Middleware\CheckSessionExpiration` que redirige a login con flash message "Sesión expirada por inactividad" si la sesión expiró.

## Endpoints / flujos

- `GET /auth/google/redirect`, `GET /auth/google/callback` (marketplace).
- `POST /auth/logout` (marketplace) — cierra sesión del guard `web`, invalida sesión, redirige a `/`.
- `POST /erp/login` (vía Filament panel `sistema`), con `throttle:5,1`.
- Pantalla forzada de cambio de contraseña si `debe_cambiar_password = true` o `NOW() > password_expires_at` (bloquea navegación).
- `POST /erp/password/change` — cambio de contraseña, con `throttle:3,1`, validación de última 5 historial.

## OWASP — mitigaciones adicionales

- `composer audit` en CI (GitHub Actions o similar) para A06.
- Validación de `redirect` URL en OAuth contra lista blanca de dominios (A10).
- `APP_DEBUG=false` verificado en producción (A05).
- Signed routes para enlaces de desbloqueo de usuario, cambio de propietario (A08).
