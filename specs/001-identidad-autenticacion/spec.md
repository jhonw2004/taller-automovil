---
id: 001-identidad-autenticacion
status: implemented
depends_on: []
resumen: "Separación estricta entre identidad marketplace (login solo Google) e identidad sistema (usuario/contraseña con política de seguridad: expiración, rate limiting, bloqueo, sesión por inactividad)."
---

# Identidad y Autenticación

## Propósito

Definir dos identidades no intercambiables: **usuario marketplace** (conductores, login solo con Google) y **usuario sistema** (personal de taller y super admin, login con username/contraseña). Ninguna identidad puede ser ambas a la vez.

## Actores

- Visitante público (sin sesión).
- Usuario marketplace (autenticado con Google).
- Usuario sistema (autenticado con username/contraseña; incluye super admin, owners, empleados con acceso).
- Super admin (genera la credencial inicial del admin del taller; luego el admin del taller gestiona sus propios usuarios).

## Criterios de aceptación

### Identidad marketplace

- Dado un visitante que completa el flujo OAuth de Google, cuando el callback recibe `provider_subject` nuevo, entonces el sistema crea `identidades` (tipo `MARKETPLACE`) y `usuarios_marketplace` en una única operación y lo autentica.
- Dado un `provider_subject` ya registrado, cuando el usuario repite login con Google, entonces el sistema reutiliza la identidad existente (idempotente por `provider + provider_subject`) sin duplicar registros.
- Dado un usuario marketplace autenticado, cuando intenta acceder a cualquier ruta del ERP (guard `sistema`), entonces el acceso se deniega.
- Un usuario marketplace no tiene contraseña ni puede definirla.

### Identidad sistema — flujo de creación por ámbitos

- El **Super Admin** genera la credencial inicial del admin del taller (único usuario que crea directamente). Esa credencial se crea con `debe_cambiar_password = true` y una contraseña temporal segura generada por el sistema.
- El **admin del taller** (owner/shop admin), una vez activo, gestiona todos los usuarios de su taller: crea empleados con acceso, genera credenciales, restablece contraseñas. El Super Admin no interviene en la gestión diaria de usuarios del taller.
- Dado un Super Admin que genera la credencial inicial, cuando se crea el usuario sistema del admin del taller, entonces en una sola transacción se crean identidad, usuario sistema y credencial con `debe_cambiar_password = true`, `password_hash` de una contraseña temporal generada por el sistema, y se asigna el rol `OWNER` para ese taller.
- Dado un admin de taller, cuando crea un usuario sistema para un empleado de su taller, entonces en una sola transacción se crean identidad, usuario sistema y credencial con `debe_cambiar_password = true`.
- `username` es obligatorio y único globalmente en todos los casos.

### Identidad sistema — reglas de seguridad

- Dado un usuario sistema recién creado con `debe_cambiar_password = true`, cuando inicia sesión por primera vez, entonces el sistema bloquea la navegación hasta que cambie la contraseña.
- Dado un usuario sistema autenticado, cuando intenta acceder a rutas del marketplace autenticado (guard `web`), entonces el acceso se deniega.
- Dado un usuario sistema con 5 intentos fallidos consecutivos, cuando ocurre el quinto intento fallido, entonces el sistema lo bloquea 15 minutos o hasta que un admin lo desbloquee manualmente.
- Dado un usuario sistema bloqueado, cuando realiza un login exitoso tras el desbloqueo, entonces `intentos_fallidos` se reinicia a 0.

### Política de contraseñas (identidad sistema)

- Longitud mínima de 12 caracteres.
- Debe contener al menos una mayúscula, una minúscula, un número y un símbolo.
- Se valida contra una lista de contraseñas comunes (rockyou top 10000 o similar) — si coincide, se rechaza.
- La contraseña expira cada 90 días. Al expirar, el sistema forza el cambio en el siguiente inicio de sesión exitoso.
- Las últimas 5 contraseñas se almacenan (hash) para evitar reutilización inmediata.
- La contraseña se almacena únicamente como hash (bcrypt/argon2id). Nunca se loggea, audita ni expone.
- Dado un admin de taller que genera una credencial para un empleado, cuando el sistema crea la contraseña temporal, entonces esta se genera automáticamente cumpliendo la política de seguridad, se muestra una sola vez al admin (con advertencia de que no podrá recuperarla después), y el empleado debe cambiarla en el primer inicio de sesión.

### Rate limiting

- La ruta `POST /erp/login` está protegida con el middleware `throttle:5,1` (5 intentos por minuto por IP + username).
- Esto es complementario al bloqueo por intentos fallidos a nivel de BD (5 intentos consecutivos → bloqueo de 15 min). Ambos mecanismos coexisten.
- El rate limiting aplica también a la ruta de cambio de contraseña (`throttle:3,1`).

### Sesión e inactividad

- La sesión del guard `sistema` expira tras 30 minutos de inactividad.
- Al expirar, el usuario es redirigido al login con mensaje "Sesión expirada por inactividad".
- La sesión del guard `web` (marketplace) expira tras 7 días (sesión persistente, típico de OAuth).
- Dado un usuario sistema con sesión expirada, cuando intenta acceder a cualquier ruta protegida, entonces el sistema redirige al login sin perder datos de formularios no enviados (sesión regenerada).

### OWASP Top 10 — mitigaciones aplicadas

| OWASP A# | Riesgo | Mitigación en el proyecto |
|---|---|---|
| A01 | Broken Access Control | Guards separados + Policies + `auth:sistema`/`auth:web` middleware + spatie permissions |
| A02 | Cryptographic Failures | bcrypt/argon2id + APP_KEY encryption + HTTPS forzado en producción |
| A03 | Injection | Eloquent ORM (PDO binding) + raw queries con binding explícito |
| A05 | Security Misconfiguration | `APP_DEBUG=false` en prod + `APP_KEY` requerido + CORS configurado |
| A07 | ID & Auth Failures | Rate limiting (`throttle`) + bloqueo por intentos + política de contraseñas + expiración + reutilización |
| A08 | Software Integrity | Signed routes para enlaces críticos + `composer audit` en CI |
| A09 | Logging & Monitoring | `spatie/laravel-activitylog` + auditoría de eventos + logs estructurados |
| A10 | SSRF | Validación de URLs externas en integraciones OAuth (provider validation) |

Nota: A04 (Insecure Design) se cubre a nivel arquitectónico en `memory/constitution.md`. A06 (Vulnerable Components) se mitiga con `composer audit` periódico.

### Separación estricta

- Una identidad tiene `tipo = MARKETPLACE` o `tipo = SISTEMA`, nunca ambos; no existe flujo en el MVP para convertir una identidad de un tipo a otro.
- Si la misma persona necesita ambos accesos, debe crear identidades separadas con correos distintos (fuera de alcance de este spec: no hay vínculo automático entre ellas).

## Fuera de alcance (MVP)

- Múltiples proveedores OAuth simultáneos (solo Google; el modelo `identidades_oauth` ya soporta agregar más).
- Recuperación de contraseña por email para usuario sistema.
- Autenticación multifactor (2FA) para el ERP.
- Notificación automática al usuario sobre expiración próxima de contraseña.
