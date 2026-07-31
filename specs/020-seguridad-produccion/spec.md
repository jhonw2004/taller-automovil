---
id: 020-seguridad-produccion
status: draft
depends_on: [001-identidad-autenticacion, 002-roles-permisos, 015-auditoria]
resumen: "Hardening de seguridad integral pre-producción: rate limiting exhaustivo y anti-DoS, cabeceras HTTP, sesión/cookies, validación de entrada, subida de archivos, configuración de producción, dependencias, aislamiento multi-tenant verificado, backups y continuidad."
---

# Seguridad y Hardening de Producción

## Propósito

Cerrar, antes de exponer el sistema en producción, toda superficie de ataque que hoy no está cubierta por ninguna feature existente: abuso capaz de degradar o **tumbar la infraestructura** (spam de formularios públicos, subida de archivos sin límite, queries sin timeout, ausencia de cabeceras de seguridad), fuga de información en configuración de despliegue, y falta de verificación sistemática de que el aislamiento multi-tenant (`BelongsToTaller`, `017`) resiste un intento deliberado de acceso cruzado (IDOR). No reemplaza ninguna regla ya definida en `memory/constitution.md` §7 (bloqueo por intentos fallidos, no loggear `password_hash`) — las extiende y las hace verificables con tests y un checklist ejecutable antes de cada despliegue.

Este spec es, como `017-infraestructura-sistema` y `016-ui-design-system`, **transversal**: toca configuración global (`bootstrap/app.php`, `config/*`, `.env.example`) y agrega un middleware/tests que se aplican a rutas ya definidas por otras features, sin cambiar su lógica de negocio. Por eso su `depends_on` solo lista las specs cuyos mecanismos extiende directamente (identidad/sesión, autorización, auditoría) — no lista las 17 features operativas cuyas rutas toca, siguiendo el mismo criterio que `AGENTS.md` ya aplica a `016`/`017`.

## Alcance auditado (evidencia real en el código actual, no hipotética)

Antes de definir criterios se auditó el estado real del proyecto. Gaps confirmados que este spec cierra:

1. **`routes/web.php` no tiene ningún `throttle` en rutas públicas anónimas**: `POST /solicitudes-taller` (crear solicitud), `POST /solicitudes-taller/{token}/cancelar`, `GET /talleres/buscar`, `GET /talleres/{slug}` — todas sin límite de tasa. Solo `routes/api.php` tiene `throttle:30,1` (búsqueda) y `throttle:10,1` (reseñas/favoritos autenticados). Un script puede enviar miles de `POST /solicitudes-taller` por minuto sin ser frenado.
2. **`bootstrap/app.php` no registra ningún middleware de cabeceras de seguridad** (`->withMiddleware(function (Middleware $middleware) { // vacío })`). No hay HSTS, CSP, `X-Frame-Options`, `X-Content-Type-Options` ni `Referrer-Policy` en ninguna respuesta.
3. **`config/session.php`**: `'secure' => env('SESSION_SECURE_COOKIE')` sin default — en producción, si el operador olvida setear la variable, la cookie de sesión viaja sin flag `Secure`. `SESSION_LIFETIME=10080` en `.env.example` (7 días) para una sesión que además maneja el guard `sistema` del ERP.
4. **`app/Filament/Erp/Resources/TallerResource.php:88`**: `FileUpload::make('logo_url')->image()` sin `->maxSize()` ni `->acceptedFileTypes()` explícito — el único límite real es `upload_max_filesize`/`post_max_size` de PHP a nivel de servidor, no de aplicación.
5. **`.env.example`**: `DB_USERNAME=root` — plantilla que invita a correr la aplicación con un rol de base de datos con privilegios de superusuario en vez de uno de mínimo privilegio.
6. **No existe ningún `RateLimiter::for(...)` centralizado** (`AppServiceProvider::boot()` no define ninguno) — cada ruta usa un literal `throttle:N,1` ad hoc, sin política única ni forma de ajustar todos los límites desde un solo sitio.
7. **No existe estrategia de backup de base de datos** documentada ni automatizada en ningún `spec.md`/`plan.md` del proyecto.
8. **No existe ningún test que verifique IDOR de forma sistemática** (acceso cruzado entre talleres por manipulación directa de ID) como suite dedicada — la cobertura existente es por feature, no consolidada como garantía de seguridad.
9. **No hay proceso documentado de auditoría de dependencias** (`composer audit` / `npm audit`) antes de un release.

## Actores

- **Visitante anónimo / adversario no autenticado**: cualquier tráfico HTTP hacia rutas públicas, incluido tráfico automatizado hostil (scraping, spam, fuerza bruta, flooding).
- Usuario marketplace (guard `web`), usuario sistema (guard `sistema`), Super Admin — mismos actores del resto del proyecto, ahora bajo el supuesto de que alguno de ellos puede ser malicioso o su cuenta estar comprometida (insider threat / cuenta robada).
- Operador de infraestructura (quien despliega a producción y corre el checklist de esta spec).

## Criterios de aceptación

### A. Rate limiting exhaustivo (ninguna ruta pública sin límite)

- Dado cualquier ruta de `routes/web.php` o `routes/api.php` que no requiera sesión activa, cuando se define, entonces tiene un `throttle` explícito tomado de un `RateLimiter::for(...)` nombrado (no un literal `throttle:N,1` disperso).
- Dado `POST /solicitudes-taller` y `POST /solicitudes-taller/{token}/cancelar`, cuando una misma IP realiza más de 5 solicitudes por minuto, entonces recibe `429` sin llegar a `CrearSolicitudTallerAction`.
- Dado `GET /talleres/buscar` y `GET /talleres/{slug}`, cuando una misma IP realiza más de 60 requests por minuto, entonces recibe `429`.
- Dado el login de Filament (`/admin/login`, `/erp/login`), cuando se exceden los intentos nativos de Filament (5/60s por IP) **y además** el usuario acumula 5 fallos según `RegistrarIntentoFallidoListener` (`001`), entonces ambos mecanismos siguen aplicando sin conflicto (no se duplica ni se reemplaza el bloqueo por usuario ya implementado en `001`).
- Dado que se excede cualquier límite, cuando el cliente recibe `429`, entonces la respuesta sigue el contrato de errores de `constitution.md §4` (JSON `{error, code}` si `expectsJson()`, redirect con `->with('error', ...)` en Blade) — nunca un 500.
- Los límites concretos (RPM por endpoint) quedan documentados en `plan.md` §A como única fuente de verdad, no repetidos como literales en cada archivo de rutas.

### B. Límites de tamaño y agotamiento de recursos (anti resource-exhaustion)

- Dado `FileUpload::make('logo_url')` (`TallerResource`), cuando se sube un archivo, entonces se rechaza si supera 2 MB o si el mime type no está en la whitelist (`image/jpeg`, `image/png`, `image/webp`) — verificado con test que sube un archivo de 3 MB y uno con extensión `.php` renombrada a `.jpg`.
- Dado cualquier tabla Filament o endpoint JSON paginado, cuando el cliente pide un tamaño de página, entonces el sistema nunca devuelve más de un máximo fijo por endpoint (ninguna tabla permite `per_page` arbitrario que fuerce cargar toda la tabla).
- Dado que la aplicación corre en producción, entonces `max_execution_time` de PHP-FPM y `statement_timeout` de la conexión Postgres del rol de aplicación están configurados con un tope (documentado en `plan.md §D`, aplicado a nivel de rol de BD, no de código) para que ninguna query o request individual pueda monopolizar un worker o una conexión indefinidamente.
- Dado que un archivo se sube a `Storage::putFile()` (disco `public`), cuando se guarda, entonces el nombre se genera aleatoriamente (comportamiento ya nativo de `putFile()`, se agrega test que lo confirma) y el disco de destino no permite ejecución de scripts (verificado que `public/storage` no tiene `.php` interpretable, vía `.htaccess`/config del servidor documentado en `plan.md`).

### C. Cabeceras de seguridad HTTP y transporte

- Dado cualquier respuesta HTTP del sistema, cuando se inspeccionan sus cabeceras, entonces incluye `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin` y `Permissions-Policy` restrictiva (sin geolocalización/cámara/micrófono salvo lo que el mapa Leaflet necesite).
- Dado `APP_ENV=production`, cuando se sirve cualquier respuesta, entonces incluye `Strict-Transport-Security: max-age=31536000; includeSubDomains`. En `local`/`testing` esta cabecera no se agrega (evita romper desarrollo sin HTTPS).
- Dado `APP_ENV=production`, cuando se recibe una request por `http://`, entonces se redirige a `https://` antes de procesar cualquier ruta.
- Dado que existe una Content-Security-Policy, cuando se define, entonces arranca en modo `Content-Security-Policy-Report-Only` (no bloqueante) documentando los orígenes reales que usa el proyecto (Leaflet/OSM tiles, fuentes `@fontsource`, Alpine/Livewire inline), con un plan explícito de pasar a bloqueante una vez verificado que no rompe el mapa ni Filament — **no se activa en modo bloqueante en este spec sin confirmación explícita** (ver "Decisiones pendientes").
- Dado `SESSION_SECURE_COOKIE`, cuando `APP_ENV=production` y la variable no está seteada explícitamente, entonces el sistema la asume `true` por default (no `null`) — un despliegue nunca queda con cookies de sesión sin `Secure` por omisión.

### D. Sesión, autenticación y validación de entrada

- Dado el guard `sistema` (ERP/Admin), cuando el usuario inicia sesión, entonces el ID de sesión se regenera (comportamiento nativo de Laravel en login, se agrega test que lo confirma explícitamente para ambos paneles).
- Dado cualquier modelo Eloquent del proyecto, cuando se audita su clase, entonces ninguno usa `protected $guarded = []` (mass assignment sin restricción) — todos declaran `$fillable` explícito. Verificado con un test que recorre `app/Models/*.php` por reflexión.
- Dado cualquier vista Blade del proyecto, cuando se audita, entonces no existe ningún `{!! $variable !!}` que imprima contenido derivado de entrada de usuario sin sanitizar explícitamente (grep de CI/test); las únicas excepciones documentadas (si las hay) imprimen HTML generado por el propio backend, nunca texto libre de un formulario.
- Dado cualquier query que use `DB::raw()`/`DB::statement()` (helpers PostGIS de `017`), cuando recibe un valor dinámico, entonces siempre usa binding parametrizado (`?`), nunca interpolación de string — ya es el patrón actual en `HasGeolocation`, este spec lo formaliza con un test de regresión que falla si aparece interpolación directa de variable en un `DB::raw`.

### E. Configuración segura de producción y gestión de secretos

- Existe un comando `php artisan security:check-produccion` que verifica, y falla con código de salida distinto de cero si no se cumple: `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` con esquema `https://`, `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL` distinto de `debug`, `DB_USERNAME` distinto de `root`/`postgres`. Este comando es el gate antes de cualquier despliegue a producción.
- Dado `.env.example`, cuando se audita, entonces documenta explícitamente que en producción el rol de PostgreSQL de la aplicación debe ser de mínimo privilegio (sin `SUPERUSER`/`CREATEDB`/`CREATEROLE`, sin permiso para `CREATE EXTENSION` fuera del setup inicial de PostGIS), con el `GRANT` exacto necesario documentado en `plan.md`.
- Dado que `password_hash`/tokens OAuth nunca se auditan ni loggean (regla ya vigente de `constitution.md §7`), entonces se agrega un test de regresión sobre los logs de la suite (`Log::` fake) que confirma que ninguna excepción no controlada serializa un modelo con esos campos en texto plano.

### F. Dependencias y vulnerabilidades conocidas

- Existe un comando/script documentado (`composer audit` + `npm audit --audit-level=high`) que se corre antes de cada release; el README documenta el proceso y qué hacer si aparece un CVE (actualizar, o documentar mitigación si no hay parche disponible).

### G. Aislamiento multi-tenant verificado contra IDOR (consolidado, no solo por feature)

- Existe una suite dedicada `tests/Feature/Seguridad/AislamientoMultiTenantTest.php` que, para cada Resource operativo del ERP con `BelongsToTaller` (clientes, vehículos, empleados, servicios, repuestos, proveedores, órdenes, notas, pagos), crea dos talleres y verifica que un usuario del taller A recibe 404 (no 403 — no debe confirmar que el registro existe) al intentar ver/editar/eliminar por ID directo un registro del taller B.
- Dado cualquier ruta pública que hoy resuelve por `token_publico`/slug en vez de `id` autoincremental (`004-solicitud-alta-taller`, `005-marketplace-busqueda-perfil`), entonces ese patrón se mantiene como regla explícita de este spec: ninguna ruta pública nueva usa route-model-binding directo por `id`.
- Dado `BelongsToTaller::sinScope()` (acceso de Super Admin fuera del scope, auditado desde `015`), cuando se usa, entonces cada llamada real en el código tiene un test que confirma que queda registrada en `auditoria_eventos` con el actor correcto (regresión del bug ya corregido en `015` sobre el guard incorrecto).

### H. Backups y continuidad

- Existe backup automático diario de la base de datos de producción, con retención mínima de 14 días, y notificación (log o canal configurado) si el backup falla.
- Existe un procedimiento documentado y probado al menos una vez de restauración desde un backup contra una base de datos limpia, con el resultado (éxito/tiempo/pasos) documentado en `plan.md`.

### I. Monitoreo de eventos de seguridad

- Dado que `015-auditoria` ya registra `LOGIN`/`FAILED_LOGIN`/`PASSWORD_CHANGE` en `auditoria_accesos`, cuando una misma IP acumula más de 20 `FAILED_LOGIN` en 5 minutos (a través de cualquier usuario, no solo el bloqueo por-usuario ya existente de `001`), entonces el evento se loggea en un canal de log dedicado `security` (`config/logging.php`) para que un operador pueda detectar un ataque de fuerza bruta distribuido, incluso si cada usuario individual no llega al umbral de bloqueo de `001`.

## Fuera de alcance (MVP de este spec)

- **WAF de red / protección DDoS L3-L4**: responsabilidad del proveedor de hosting (Cloudflare, AWS Shield, etc.), no del código de la aplicación. Este spec cubre solo la capa de aplicación.
- **Pentesting profesional externo / bug bounty**: recomendado antes de un lanzamiento público real, pero es un proceso externo al repositorio, no una tarea de implementación.
- **2FA/MFA para usuarios sistema**: se documenta como recomendación futura fuerte (especialmente para Super Admin) pero no se implementa en este spec — requiere una feature propia con su propio flujo de UI.
- **CSP en modo bloqueante desde el día uno**: se implementa en modo `Report-Only` primero (ver criterio C) — pasar a bloqueante es una decisión posterior, informada por los reportes reales.
- **Certificación PCI-DSS**: no aplica, no hay pagos online en el MVP (`013-pagos` es registro de pagos ya cobrados por otro medio).
- **Rotación automática de `APP_KEY`**: se documenta el procedimiento manual, no se automatiza (requeriría re-encriptar sesiones/cookies activas).

## Decisiones confirmadas por el usuario (2026-07-31)

1. **Destino de los backups de base de datos**: sin decidir todavía (el usuario eligió "decidir después"). La sección E de `plan.md`/`tasks.md` queda **bloqueada explícitamente** — el resto del spec no depende de esto y se implementa igual. Cuando se decida (local, S3, o `spatie/laravel-backup` vs. script `pg_dump`+cron), se resuelve como una tarea aislada sin reabrir el resto de la feature.
2. **Content-Security-Policy**: se implementa **solo en modo `Report-Only`** (criterio C de este spec) y se deja así. No se pasa a bloqueante en esta feature — sin `claude-in-chrome` disponible en este entorno para verificar visualmente que Filament/Livewire/Alpine/Leaflet no se rompen con una CSP estricta, activarla bloqueante sin poder probarlo en un navegador real es un riesgo no asumible ahora. Pasar a bloqueante queda como trabajo futuro explícito, informado por los reportes reales acumulados.
3. **Umbral de fuerza bruta distribuida** (criterio I): **solo se loggea** al canal `security` — no se agrega notificación por email/Slack en este spec (no hay proveedor de notificaciones externas configurado en el proyecto todavía). El log queda accionable para revisión manual/monitoreo externo futuro.
