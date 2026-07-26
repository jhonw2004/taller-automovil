# Tasks — Identidad y Autenticación

## Base de datos y modelos
- [ ] Migración `identidades` (tipo, email UNIQUE, telefono, estado, soft delete).
- [ ] Migración `usuarios_marketplace` (FK identidad_id UNIQUE, nombre, avatar_url, locale, activo, soft delete).
- [ ] Migración `identidades_oauth` (UNIQUE provider+provider_subject, UNIQUE usuario_marketplace_id+provider).
- [ ] Migración `usuarios_sistema` (FK identidad_id UNIQUE, username UNIQUE, activo, soft delete).
- [ ] Migración `credenciales_sistema` (FK usuario_sistema_id UNIQUE, password_hash, debe_cambiar_password, password_expires_at, intentos_fallidos, bloqueado_hasta).
- [ ] Migración `historial_passwords` (FK usuario_sistema_id, password_hash, append-only).
- [ ] Modelos Eloquent + relaciones (`Identidad hasOne UsuarioMarketplace|UsuarioSistema`, `UsuarioSistema hasOne CredencialSistema`, `CredencialSistema hasMany HistorialPassword`).

## Autenticación marketplace (OAuth Google)
- [ ] Configurar guards `web`/`sistema` y providers en `config/auth.php`.
- [ ] Instalar y configurar `laravel/socialite` (driver Google) + rutas de redirect/callback.
- [ ] Action `LoginOrRegisterMarketplaceUser` (idempotente por provider+provider_subject) en transacción.
- [ ] Ruta `POST /auth/logout` — cierra sesión guard `web`, invalida sesión, redirige a `/`.
- [ ] Instalar `laravel/breeze --dev` (stack blade) y adaptar: quitar registro/login con password, redirigir a Google.

## Autenticación sistema (ERP)
- [ ] Action `GenerarCredencialInicial` (Super Admin crea credencial del admin del taller: genera password temporal segura, la muestra una sola vez, `debe_cambiar_password = true`).
- [ ] Action `CrearUsuarioSistema` (admin de taller crea empleados con acceso del mismo taller).
- [ ] Listener de `Failed`/`Login` para `intentos_fallidos` y bloqueo de 15 min tras 5 fallos.
- [ ] Validación de contraseña con `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` en Form Request.
- [ ] Comprobación de expiración en login: si `NOW() > password_expires_at`, forzar cambio.
- [ ] Listener de `PasswordReset` que inserta en `historial_passwords` y limpia a últimas 5.
- [ ] Middleware `throttle:5,1` en `POST /erp/login` y `throttle:3,1` en cambio de contraseña.
- [ ] Middleware `CheckSessionExpiration` con redirección y flash message.
- [ ] Middleware/gate que impide a guard `web` acceder a rutas `sistema` y viceversa.
- [ ] Pantalla/flujo de cambio de contraseña obligatorio (primer login + expirada).

## OWASP / Seguridad
- [ ] `APP_DEBUG=false` forzado en producción (script de deploy o CI check).
- [ ] `composer audit` configurado en CI.
- [ ] Validación de `redirect` URL en Socialite contra lista blanca (dominios conocidos).
- [ ] Signed routes para enlaces de desbloqueo y acciones críticas.

## Tests Pest
- [ ] Login Google idempotente (mismo provider+provider_subject no duplica).
- [ ] Separación estricta de guards (web no accede a sistema y viceversa).
- [ ] Bloqueo por 5 intentos fallidos consecutivos + desbloqueo manual.
- [ ] Rate limiting: más de 5 intentos por minuto es rechazado.
- [ ] Contraseña débil (<12 chars, sin mayúscula, sin símbolo, comprometida) es rechazada.
- [ ] Contraseña expirada forza cambio en login.
- [ ] Reutilización de una de las últimas 5 contraseñas es rechazada.
- [ ] Sesión del guard `sistema` expira tras 30 min de inactividad.
- [ ] Hash de password nunca expuesto en logs/auditoría.
