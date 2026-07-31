# Tasks — Seguridad y Hardening de Producción

CSP (Report-Only, sin pasar a bloqueante) y umbral de fuerza bruta (solo log, sin notificación externa) ya están decididos — ver `spec.md`. **Solo la sección E (Backups) sigue bloqueada** hasta que se decida dónde se guardan (local/S3/paquete vs. script); todo lo demás está implementado y verificado (592/592 tests verdes, incluidos los 24 nuevos de esta feature).

## A. Rate limiting

- [x] `RateLimiter::for('publico-lectura' | 'publico-escritura' | 'busqueda-api' | 'marketplace-escritura' | 'notificaciones', ...)` en `AppServiceProvider::boot()`.
- [x] Aplicar `throttle:publico-lectura`/`throttle:publico-escritura` a las 4 rutas de `solicitudes-taller` y a `talleres.buscar`/`talleres.show` en `routes/web.php`.
- [x] Aplicar `throttle:notificaciones` a `POST /notificaciones/{id}/marcar-leida`.
- [x] Migrar `routes/api.php` de `throttle:30,1`/`throttle:10,1` literales a `throttle:busqueda-api`/`throttle:marketplace-escritura` (mismos valores, ahora centralizados).
- [x] Confirmado: el rate limiting nativo de login de Filament (5/60s) sigue intacto, sin duplicación — no se tocó.
- [x] **Verificado con tráfico real** (`php artisan serve` + `curl` con sesión/CSRF reales, no solo tests): 5 requests a `POST /solicitudes-taller` pasan (302, validación falla por body vacío — 0 filas creadas en BD), la 6ª y siguientes devuelven `429`.

## B. Límites de tamaño y recursos

- [x] `FileUpload::make('logo_url')` en `TallerResource.php`: agregado `->maxSize(2048)` + `->acceptedFileTypes([...])`.
- [x] Auditadas todas las tablas Filament — ninguna declara `->paginated([...])` explícito, todas usan el default seguro de Filament (`[5, 10, 25, 50]`, sin opción "all").
- [x] **Hallazgo real no anticipado en el plan original**: `GET /api/talleres/search` no tenía ningún límite — `$query->get()` sin `limit()` devolvía TODOS los talleres visibles en cada request. Corregido con un tope de 100 (`TallerBusquedaApiController::MAX_RESULTADOS`).
- [x] Runbook de despliegue documentado en `plan.md §B` (`request_terminate_timeout`, `proxy_read_timeout`, `ALTER ROLE ... SET statement_timeout` — este último además ejecutado y verificado de verdad, ver sección D).
- [x] Runbook de despliegue documentado en `plan.md §B` (directiva Nginx para no ejecutar `.php` en `public/storage/`).

## C. Cabeceras de seguridad y transporte

- [x] Creado `App\Http\Middleware\SecurityHeaders` (nosniff, frame-options, referrer-policy, permissions-policy, HSTS solo en producción, CSP `Report-Only`).
- [x] Registrado en el middleware global (`bootstrap/app.php`), no solo el grupo `web` — cubre también `routes/api.php`.
- [x] `URL::forceScheme('https')` condicionado a `app()->environment('production')`, en `AppServiceProvider::boot()`.
- [x] `config/session.php`: `'secure' => env('SESSION_SECURE_COOKIE') ?? env('APP_ENV') === 'production'` — **nota**: se usa `env('APP_ENV')`, no `app()->environment()`, ver bug real corregido abajo.
- [x] `.env.example`: `SESSION_LIFETIME` de `10080` a `480`, con comentario explicando el cambio.
- [x] **Bug real propio encontrado y corregido**: la primera versión usaba `app()->environment('production')` dentro de `config/session.php` — los archivos de config se cargan antes de que el contenedor registre el binding `env`, y esto rompía el boot completo de la aplicación (`Target class [env] does not exist`, los 568 tests fallaban idénticamente). Detectado corriendo la suite completa, no por lectura de código.

## D. Base de datos: mínimo privilegio

- [x] `.env.example`: `DB_USERNAME=taller_app` en vez de `root`, con comentario.
- [x] Documentado en `plan.md` el `CREATE ROLE`/`GRANT`/`ALTER ROLE ... statement_timeout` exacto.
- [x] **Rol `taller_app` creado de verdad** en el Postgres local (PostgreSQL 18.3/PostGIS 3.6.2) con `NOSUPERUSER NOCREATEDB NOCREATEROLE` + `statement_timeout = 30s`, verificado con `psql` (conexión real, `ST_MakePoint` funciona, timeout activo).
- [x] **Hallazgo real confirmado empíricamente, no solo documentado**: `CREATE EXTENSION postgis` desde cero **requiere superusuario** en esta instalación (no es una extensión "trusted") — probado creando una BD nueva owned por `taller_app` e intentando la extensión, falla con "Debe ser superusuario". Confirma que la secuencia documentada en `plan.md` (extensión una vez como superusuario, todo lo demás con el rol de aplicación) es un requisito real, no cautela excesiva.
- [x] `taller_test` reconstruida con `taller_app` como dueño (tras crear la extensión una vez como superusuario) — **suite completa (592/592) corrida y verde contra el rol de mínimo privilegio**, `.env.testing` se deja apuntando a `taller_app` de forma permanente (no se revirtió: es la configuración correcta a futuro, no solo una prueba puntual).

## E. Backups

- [ ] **Esperar decisión del usuario** (local/S3, paquete vs. script) antes de esta subsección.
- [ ] Si se confirma `spatie/laravel-backup`: instalar, publicar config, limitar a solo BD (no `storage/app` completo), programar `backup:clean`/`backup:run`/`backup:monitor` en `routes/console.php`.
- [ ] Si se confirma script manual: cron + `pg_dump -Fc` + retención de 14 días documentados.
- [ ] Ejecutar al menos una restauración de prueba contra una BD limpia y documentar el resultado en `plan.md`.

## F. Dependencias

- [x] Documentado `composer audit` + `npm audit --audit-level=high` en `README.md` como paso obligatorio pre-release.
- [x] Corridos ambos comandos contra el estado actual del repo: **sin vulnerabilidades conocidas** (PHP y JS).

## G. Comando de verificación pre-despliegue

- [x] Creado `app/Console/Commands/SecurityCheckProduccionCommand.php` (`security:check-produccion`) con los 6 checks de `plan.md §G`.
- [x] Probado en ambos sentidos: falla (exit 1) con config local, pasa (exit 0) con config de producción simulada vía variables de entorno.
- [ ] Agregar el comando al runbook de despliegue real como último paso antes de recibir tráfico (pendiente de que exista un pipeline de despliegue — no hay CI/CD configurado todavía en este repo).

## H. Tests (`tests/Feature/Seguridad/`)

- [x] `RateLimitingTest.php` — 429 en `publico-escritura` (HTTP real dentro de test), límites de `publico-lectura`/`busqueda-api`/`marketplace-escritura`/`notificaciones` verificados resolviendo el `Limit` registrado (61/31 requests HTTP por test habría sido lento sin aportar garantía distinta). Incluye regresión del bug de `{token}` no-UUID (ver abajo).
- [x] `CabecerasSeguridadTest.php` — cabeceras presentes en rutas `web` y `api`, HSTS solo en producción (simulada con `detectEnvironment()`), CSP en modo `Report-Only`.
- [x] `MassAssignmentTest.php` — recorre por reflexión todos los modelos reales de `app/Models/`, ninguno con `$guarded = []`.
- [x] `SubidaArchivosTest.php` — verifica `getMaxSize()`/`getAcceptedFileTypes()` directamente sobre el `Schema` del formulario (no vía `Livewire::test()->fillForm()`: ese Resource tiene una particularidad propia del harness de testing de Livewire en este entorno, confirmada reproduciéndola también contra el código sin modificar — no es un efecto de este cambio).
- [x] `AislamientoMultiTenantTest.php` — IDOR cross-taller 404 real vía HTTP en 8 Resources operativos (clientes, vehículos, empleados, servicios, repuestos, proveedores, órdenes, notas). Pagos no tiene Resource propio con página de edición (vive en un `RelationManager` dentro de `NotaVentaResource`) — su aislamiento depende del de la nota padre, ya cubierto.
- [x] `SecurityCheckProduccionCommandTest.php` — `FAILURE`/`SUCCESS` según config.
- [x] **Suite completa corrida y verde: 592/592 tests** (568 previos + 24 nuevos), contra el rol `taller_app` de mínimo privilegio.
- [x] Smoke test manual de concurrencia real ejecutado (ver §A) — `429` real bajo ráfaga contra el servidor corriendo de verdad, no solo en el entorno de test.
- [x] **Bug real encontrado por los tests, no hipotético, corregido**: `token_publico` es una columna `uuid` nativa de Postgres, pero las rutas `/solicitudes-taller/{token}`/`/solicitudes-taller/{token}/cancelar` no restringían el formato del parámetro — un token mal formado producía `PDOException` sin capturar (500 crudo) en vez de 404. Corregido con `->whereUuid('token')` en `routes/web.php`.

## Cierre de la feature

- [x] Actualizado `memory/constitution.md §7` (Seguridad) para referenciar este spec como la fuente de verdad de rate limiting/cabeceras/backups/mínimo privilegio.
- [ ] `spec.md` → `status: implemented` solo cuando la decisión de backups esté resuelta (única sección pendiente) y su implementación tenga tests verdes.
