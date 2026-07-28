# Tasks — Identidad y Autenticación

## Base de datos y modelos
- [x] Migración `identidades` (tipo, email UNIQUE, telefono, estado, soft delete). CHECK constraints vía `DB::statement()` (Laravel no tiene `$table->check()` nativo, ver constitution.md §2).
- [x] Migración `usuarios_marketplace` (FK identidad_id UNIQUE, nombre, avatar_url, locale, activo, soft delete).
- [x] Migración `identidades_oauth` (UNIQUE provider+provider_subject, UNIQUE usuario_marketplace_id+provider).
- [x] Migración `usuarios_sistema` (FK identidad_id UNIQUE, username UNIQUE, activo, soft delete).
- [x] Migración `credenciales_sistema` (FK usuario_sistema_id UNIQUE, password_hash, debe_cambiar_password, password_expires_at con default `NOW() + INTERVAL '90 days'`, intentos_fallidos, bloqueado_hasta).
- [x] Migración `historial_passwords` (FK usuario_sistema_id, password_hash, append-only — `const UPDATED_AT = null` en el modelo).
- [x] Modelos Eloquent + relaciones. **Corrección sobre el plan:** la relación `hasMany HistorialPassword` va en `UsuarioSistema`, no en `CredencialSistema` — la FK real de `historial_passwords` es `usuario_sistema_id` (así está en el propio esquema de `plan.md`, la lista de relaciones tenía una inconsistencia). `UsuarioMarketplace`/`UsuarioSistema` extienden `Illuminate\Foundation\Auth\User` (Authenticatable) con `$rememberTokenName = ''` (sin remember-me, ver criterios de sesión). `UsuarioSistema::getAuthPassword()` delega a `credencialSistema->password_hash`. Se eliminó la tabla/modelo `users` default de Laravel (no forma parte de esta arquitectura). **Verificado end-to-end contra la BD real** (no solo `class_exists`): creación de las 6 filas encadenadas, todas las relaciones inversas, `getAuthPassword()` en ambos guards, y que `password_hash` queda oculto en `toArray()`/`toJson()`.
- [x] Se quitaron `users`, `password_reset_tokens` (tabla y modelo `User` del skeleton de Laravel) por no ser parte de esta arquitectura; `DatabaseSeeder` limpiado.

## Autenticación marketplace (OAuth Google)
- [x] Configurar guards `web`/`sistema` y providers en `config/auth.php`. Se vació `'passwords' => []` (no hay broker de recuperación por email, fuera de alcance del MVP, y la tabla `password_reset_tokens` fue eliminada). `SESSION_LIFETIME=10080` (7 días) global — la ventana estricta de 30 min de `sistema` la impone `App\Http\Middleware\CheckSessionExpiration` (no `config/session.php`, que es un único valor global compartido entre ambos guards). Middleware registrado en `authMiddleware()` de ambos PanelProviders (`admin`/`erp`), verificado que la app boota y las rutas `filament.erp.auth.login`/`filament.admin.auth.login` responden. **`throttle:5,1` en `/erp/login` ya lo cubre Filament v5 nativamente** (trait `WithRateLimiting` de su página `Login`, `rateLimit(5)` con decay de 60s por IP) — no hace falta middleware de ruta propio, y de hecho no funcionaría bien ahí porque el submit real va por el endpoint de Livewire, no por una ruta `POST` tradicional.
- [x] Configurar `laravel/socialite` (driver Google) en `config/services.php` (credenciales ya estaban en `.env`: `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`/`GOOGLE_REDIRECT_URI`) + rutas `GET /auth/google/redirect` y `GET /auth/google/callback` en `routes/web.php` (`App\Http\Controllers\Auth\GoogleAuthController`). Verificado que el driver resuelve (`Laravel\Socialite\Two\GoogleProvider`) con las credenciales cargadas.
- [x] Action `App\Actions\Identidad\LoginOrRegisterMarketplaceUserAction` (idempotente por provider+provider_subject) en transacción. **Verificado end-to-end contra la BD real**: primer login crea `identidad`+`usuario_marketplace`+`identidad_oauth`; segundo login con el mismo `provider_subject` reutiliza el mismo usuario sin duplicar ninguna fila.
- [x] Ruta `POST /auth/logout` — cierra sesión guard `web`, invalida sesión, redirige a `/` (`App\Http\Controllers\Auth\LogoutController`).
- [ ] ~~Instalar `laravel/breeze --dev` y adaptar~~ — **se decidió no correrlo.** Breeze genera scaffolding de registro/login con contraseña atado a una tabla `users` que ya no existe en esta arquitectura (se eliminó, ver sección "Base de datos y modelos"); habría que revertir la mayor parte de lo que genera. Tailwind v4 ya viene configurado en el skeleton de Laravel 13 sin Breeze. Se implementó el flujo OAuth directamente a mano (controllers + action arriba) en su lugar. El paquete queda instalado en `composer.json` por si se necesita alguna vista de referencia, pero no se ejecutó `breeze:install`.

## Autenticación sistema (ERP)
- [x] Action `App\Actions\Identidad\GenerarCredencialInicialAction` (Super Admin crea credencial del admin del taller: genera password temporal segura con `Str::password(16, symbols: true)`, `debe_cambiar_password = true`). La asignación del rol `OWNER` queda con una nota explícita en el código, pendiente hasta que exista el catálogo de `002-roles-permisos`.
- [x] Action `App\Actions\Identidad\CrearUsuarioSistemaAction` (admin de taller crea empleados) — reutiliza `GenerarCredencialInicialAction` por composición en vez de duplicar la lógica de creación. La asignación a un `taller_id` vía rol queda con la misma nota pendiente.
- [x] `App\Auth\UsuarioSistemaProvider` (custom `EloquentUserProvider`, registrado en `AppServiceProvider`) rechaza login si `activo = false` o `bloqueado_hasta` en el futuro — un provider `eloquent` estándar no puede validar esto porque esos datos viven en `CredencialSistema`, no en `UsuarioSistema`.
- [x] Listener `App\Listeners\Identidad\RegistrarIntentoFallidoListener` (evento `Failed`) y `App\Listeners\Identidad\ReiniciarIntentosFallidosListener` (evento `Login`) — auto-descubiertos por Laravel (`php artisan event:list` los confirma), sin registro manual.
- [x] Form Request `App\Http\Requests\Sistema\CambiarPasswordRequest` con `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()`.
- [x] Comprobación de expiración: `ReiniciarIntentosFallidosListener` fuerza `debe_cambiar_password = true` en cada login si `password_expires_at` ya pasó.
- [x] Reutilización de contraseña + historial: implementado en `App\Actions\Identidad\CambiarPasswordAction`, **no** vía listener de `Illuminate\Auth\Events\PasswordReset` como sugería el plan original — ese evento pertenece al flujo de recuperación por email de Laravel, que no existe en este proyecto (sin tabla `password_reset_tokens`, fuera de alcance del MVP). El cambio de contraseña es siempre iniciado por el propio usuario autenticado, así que se resolvió directamente en la Action. Recorta el historial a las últimas 5 filas por usuario.
- [x] `throttle:5,1` en login: **ya lo cubre Filament v5 nativamente** (ver sección anterior), no hace falta middleware de ruta. `throttle:3,1` en cambio de contraseña: **resuelto en la quinta sesión** — rate limit propio (`WithRateLimiting`, `3/1min`) en las páginas `CambiarPassword` de ambos paneles (ver ítem de abajo).
- [x] Middleware `App\Http\Middleware\CheckSessionExpiration` con redirección (`Filament::getPanel('erp')->getLoginUrl()`) y flash message "Sesión expirada por inactividad". Registrado en `authMiddleware()` de ambos PanelProviders.
- [x] Separación de guards: no requiere middleware/gate adicional — `web` y `sistema` son guards de sesión completamente independientes (providers distintos, sin tabla compartida); un usuario autenticado en uno es sencillamente anónimo para el otro. Verificado con las rutas ya wireadas (`auth:web` en marketplace, `authGuard('sistema')` en paneles Filament).
- [x] Pantalla/flujo de cambio de contraseña obligatorio (primer login + expirada) — **resuelto en la quinta sesión** (`017-infraestructura-sistema`): `App\Http\Middleware\ForzarCambioPasswordMiddleware` + páginas `CambiarPassword` en ambos paneles, reutilizando `CambiarPasswordAction`.

**Todo lo de esta sección y la anterior fue verificado funcionalmente contra la base de datos real** (transacciones con rollback), no solo revisado a ojo: idempotencia de login OAuth, bloqueo tras 5 intentos fallidos y su reset en login exitoso, rechazo de contraseña repetida/reciente con recorte de historial a 5, y generación de contraseña temporal que cumple la política.

## OWASP / Seguridad
- [ ] `APP_DEBUG=false` forzado en producción — es un check de CI/deploy, no hay pipeline configurado todavía en este proyecto.
- [ ] `composer audit` configurado en CI — ídem, no hay CI configurado todavía.
- [x] Validación de `redirect` URL en Socialite contra lista blanca — no aplica tal como está implementado: `GoogleAuthController` no acepta ningún parámetro `redirect` controlado por el usuario, la URL de callback está fija en `GOOGLE_REDIRECT_URI` (server-side, whitelisted en Google Cloud Console). No hay superficie de ataque de open-redirect que mitigar.
- [x] ~~Signed routes para enlaces de desbloqueo y acciones críticas~~ **No aplicó**: `008-empleados-usuarios-erp` (`RestablecerPasswordUsuarioAction`/`ActivarDesactivarUsuarioSistemaAction`) resolvió "desbloqueo"/gestión crítica como acciones de Filament autenticadas dentro del panel `/erp`, no como enlaces públicos por email — no hay superficie de signed routes que construir porque nunca hubo un enlace público que firmar.

## Tests Pest

**Infraestructura de tests provisionada (2026-07-26):** BD dedicada `taller_test` en PostgreSQL local, `.env.testing`, `RefreshDatabase` habilitado en `tests/Pest.php`. **`phpunit.xml` forzaba SQLite en memoria** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` en el bloque `<php>`, que tiene prioridad sobre `.env.testing`) — se quitaron esas dos líneas para que corra contra PostgreSQL real, tal como exige `constitution.md` §6 (el sistema depende de PostGIS y CHECK constraints que SQLite no soporta). Se agregaron factories (`IdentidadFactory`, `UsuarioSistemaFactory`, `CredencialSistemaFactory`, `UsuarioMarketplaceFactory`).

- [x] Login Google idempotente (`LoginGoogleTest.php`).
- [x] Separación estricta de guards (`SeparacionGuardsTest.php`).
- [x] Bloqueo por 5 intentos fallidos consecutivos + desbloqueo manual (`BloqueoIntentosFallidosTest.php`).
- [x] Rate limiting: más de 5 intentos por minuto es rechazado (`RateLimitingLoginTest.php`, vía `Livewire::test()` contra la página real de Filament).
- [x] Contraseña débil (<12 chars, sin mayúscula, sin símbolo, comprometida) es rechazada (`PoliticaPasswordTest.php`).
- [x] Contraseña expirada forza cambio en login (`PasswordExpiradaTest.php`).
- [x] Reutilización de una de las últimas 5 contraseñas es rechazada (`ReutilizacionPasswordTest.php`).
- [x] Sesión del guard `sistema` expira tras 30 min de inactividad (`SesionExpiracionTest.php`).
- [x] Hash de password nunca expuesto en logs/auditoría (`PasswordHashOcultoTest.php`).

**26/26 tests pasan** (`php artisan test --env=testing`), 55 assertions. `laravel/pint` corrido sin errores de estilo pendientes.

### Bugs reales encontrados y corregidos gracias a los tests (no solo de los tests)

Estos tres habrían llegado a producción sin la suite — quedan documentados porque no son obvios:

1. **`UsuarioSistema` no implementaba `Filament\Models\Contracts\FilamentUser`.** Sin esto, Filament v5 deniega el acceso a **todos** los paneles con 403 por defecto (comportamiento "seguro por defecto": sin el contrato, deniega en vez de permitir). Ningún usuario sistema habría podido entrar ni a `/admin` ni a `/erp`. Se agregó `canAccessPanel()` (por ahora solo exige `activo`, restricción real por rol pendiente de `002`).
2. **`UsuarioSistema` tampoco implementaba `Filament\Models\Contracts\HasName`.** Sin esto, Filament intenta leer un atributo `name` que no existe en el modelo (usa `nombre`/`apellido`) y lanza `TypeError` al renderizar cualquier página del panel. Se agregó `getFilamentName()`.
3. **Bug real en `CheckSessionExpiration`:** la condición usaba `now()->diffInMinutes($ultimaActividad) > 30`. En la versión de Carbon de este proyecto, `diffInMinutes()` devuelve un valor **con signo** (negativo si el argumento es una fecha pasada), no el absoluto que versiones anteriores de Carbon daban por defecto — la condición nunca se cumplía y la sesión jamás expiraba por inactividad, pese a que la implementación "se veía" correcta. Se corrigió comparando fechas directamente (`$ultimaActividad->lt(now()->subMinutes(30))`) en vez de restar duraciones.
