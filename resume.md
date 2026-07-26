# Resume — Estado del proyecto y trabajo realizado

Última actualización: 2026-07-26 (quinta sesión). Este archivo existe para que cualquier agente (o persona) pueda retomar el trabajo sin releer toda la conversación anterior.

## Qué se hizo el 2026-07-26 (quinta sesión): UI real de 017/001/002/003 (107/107 tests verdes)

El usuario pidió cerrar todo lo pendiente de UI antes de arrancar `004-solicitud-alta-taller`. Alcance confirmado explícitamente: terminar `017-infraestructura-sistema` (middleware, paneles, paleta) + construir los Resources de Filament pendientes de `001`/`002`/`003`. Plan completo en el historial de la sesión (no quedó archivo de plan en el repo, era `~/.claude/plans/`).

**Housekeeping de documentación primero** (varios documentos asumían `spatie/laravel-permission`, removido desde la sesión de `002`, o dejaban decisiones sin resolver):
- `constitution.md` §1 y §5 actualizados: roles/permisos 100% custom (ya no menciona Spatie), paleta Awesomic real en vez de la línea azul-marino/naranja obsoleta.
- `017-infraestructura-sistema/plan.md`: corregido el bloque de `SetTallerActivo` (ya no llama a `Spatie\Permission\PermissionRegistrar`, que no existe), paths de `AdminPanelProvider` a `Filament/Admin/...`, y **un bug real en el propio plan** (`->colors([... 'gray' => fn () => [...]])` — envolver el valor de `gray` en una closure hace explotar `ColorManager::getColors()` con `array_map(): Argument #2 must be of type array, Closure given`; el array debe ir plano). `017-tasks.md`, `002-plan.md`/`tasks.md`, `003-tasks.md` actualizados para reflejar el estado real.

**Decisiones de arquitectura tomadas (documentadas en el código, no solo aquí):**
1. **No se usa `->tenant()` nativo de Filament** — ya existe `BelongsToTaller` + sesión funcionando; usar ambos duplicaría la fuente de verdad del tenant activo.
2. **`SetTallerActivo` solo en `/erp`, no en `/admin`** — el Super Admin no opera "dentro" de un taller.
3. **Autorización de Resources vía `canViewAny()`/`canCreate()`/`canEdit()`/`canDelete()` estáticos** que llaman a `tienePermiso()`/`esSuperAdmin()` directamente — no Policies de Laravel (no existían, y el catálogo de permisos ya resuelve la granularidad).
4. **`AdminPanelProvider` ahora descubre en `app/Filament/Admin/...`** (antes `app/Filament/Resources` a secas, inconsistente con `Erp/...`).
5. **Paleta Awesomic: solo el array `->colors([...])` de Filament, sin los CSS theme files (`viteTheme()`)** — eso se hace junto con `005`+`016` per `AGENTS.md`.
6. **Nueva Action `CambiarEstadoTallerAction`** (no existía): transiciona `estado` ACTIVO/INACTIVO/SUSPENDIDO, solo Super Admin (único permiso del catálogo para esto es `admin.talleres.suspender`).
7. **`TallerResource` de `/erp` es de un solo registro** (el propio taller activo), no un listado — `canCreate()=false`, `canDelete()=false`.

**Código nuevo:**
- `app/Http/Middleware/SetTallerActivo.php` — resuelve `session('taller_activo_id')` desde `asignacionesVigentes()`. No maneja el caso "cero talleres": `Filament\Http\Middleware\Authenticate` ya lo bloquea con 403 antes vía `canAccessPanel()` (mismo criterio), así que ese branch habría sido código muerto — se sacó tras confirmarlo con un test.
- `app/Http/Middleware/ForzarCambioPasswordMiddleware.php` — bloquea navegación si `debe_cambiar_password` o password expirada, registrado en ambos paneles con el id de panel como parámetro de ruta (`:admin`/`:erp`).
- `app/Filament/Concerns/InteractsWithCambioPassword.php` + `App\Filament\Admin\Pages\CambiarPassword` / `App\Filament\Erp\Pages\CambiarPassword` — resuelve el pendiente de `001` ("pantalla de cambio de contraseña obligatorio"). Rate limit propio `3/1min` (`WithRateLimiting`), reusa `CambiarPasswordAction` ya existente.
- `app/Filament/Erp/Widgets/TenantSwitcher.php` — solo visible si el usuario tiene >1 taller vigente.
- `AdminPanelProvider`/`ErpPanelProvider` personalizados: paleta Awesomic, middlewares nuevos.
- **002**: `App\Filament\Admin\Resources\PermisoResource` (catálogo, solo Super Admin), `App\Filament\Admin\Resources\RolResource` (roles globales, gate `admin.roles.gestionar`), `App\Filament\Erp\Resources\RolResource` (roles del taller activo, gates `roles.ver/crear/editar/eliminar`), `App\Filament\Erp\Resources\AsignacionRolResource` (formulario de asignación que invoca `AsignarRolAction` en vez de `Eloquent::create()` directo, gate `usuarios.gestionar`).
- **003**: `app/Actions/Talleres/CambiarEstadoTallerAction.php` (nueva), `App\Filament\Erp\Resources\TallerResource` (edición del taller activo, tabs datos/ubicación/categorías/horarios, `visible_en_mapa` fuera del form — se cambia con un Action dedicado que invoca `CambiarVisibilidadTallerAction`), `App\Filament\Admin\Resources\TallerResource` (listado global, acciones suspender/activar/cambiar propietario, soft delete con advertencia estática), `App\Filament\Admin\Resources\CategoriaResource` (catálogo simple).
- **32 tests Pest nuevos**: `tests/Feature/Sistema/SetTallerActivoMiddlewareTest.php`, `ForzarCambioPasswordTest.php`, `tests/Feature/Talleres/CambiarEstadoTallerActionTest.php`, `tests/Feature/Roles/ResourcesAutorizacionTest.php`, `tests/Feature/Talleres/ResourcesAutorizacionTest.php` — cubren autorización (con/sin permiso, cruce entre talleres) y smoke tests HTTP reales de cada página nueva (index/create/edit) para los 7 Resources.

### Bugs reales encontrados durante esta sesión (no solo de los tests)

1. **`->colors([... 'gray' => fn () => [...]])` rompía ambos paneles** con un 500 real (`array_map(): Argument #2 must be of type array, Closure given`) — el bug estaba en el propio `017-plan.md`, nunca se había ejecutado antes. Corregido a array plano.
2. **`Page::getUrl()` sin `panel:` explícito resuelve contra el panel `->default()` (admin) fuera de una request ya enrutada** — `ForzarCambioPasswordMiddleware` generaba la URL de `/admin/cambiar-password` incluso cuando corría dentro de `/erp`, porque ambas páginas comparten el mismo slug relativo `cambiar-password`. Corregido pasando `panel: $panelId` explícito en el middleware (y en los tests).
3. **Confirmado (no es bug, pero no obvio):** `tienePermiso()` no da acceso automático solo por tener el rol `super-admin` — el catálogo de permisos hay que adjuntarlo al rol explícitamente (`RolSistemaSeeder` sí lo hace en producción, `Permiso::pluck('slug')->all()`). Varios tests fallaron al principio por crear un rol `super-admin` "pelado" sin permisos adjuntos.

### Qué falta (fuera de alcance explícito de esta sesión)

- Rutas `routes/api.php`/`routes/web.php` de `017` — bloqueadas por controllers de `005`/`006`, que no existen todavía. No se tocan sin romper el orden estricto de `AGENTS.md`.
- CSS/tema Tailwind completo de `016-ui-design-system` (tokens, radii, tipografía) — se hace junto con `005`.
- Listener de recálculo de `calificacion_promedio` — depende de `Resena` (`006`).
- Conteo real de "entidades hijas activas" antes de soft-delete de un taller — depende de modelos de `007+`; el modal de advertencia por ahora tiene texto estático.
- Verificación visual en navegador real: se hizo un smoke test con `php artisan serve` + `curl` contra la BD de desarrollo (confirmando 200 en `/admin/login` y `/erp/login`, y que la paleta Awesomic se aplica — aparece `oklch(...)` en el HTML, no el azul/ámbar default), pero **no hubo un click-through completo en un navegador real** (`claude-in-chrome` no está disponible en esta sesión). Los 107 tests Pest sí ejercitan un render HTTP completo (200 OK) de cada página nueva en ambos paneles.
- ~~001/002/003 pueden marcarse `status: implemented`~~ **Hecho**: el usuario lo confirmó, `spec.md` de las tres features actualizado (`status: implemented`) el mismo día, después de esta sesión.

## Qué se hizo el 2026-07-26 (cuarta sesión): 003-gestion-talleres (backend completo + 28 tests verdes, total 75/75)

Siguiendo el orden estricto de `AGENTS.md` (paso 3, después de 001/002). **Código sin commitear todavía.**

- **Migración de reemplazo de `talleres`** (`2026_07_26_060001`): el prototipo original (`2026_07_19_174015`) no tenía slug/estado/visible_en_mapa/calificación y sobraba la columna `horario`. Se resolvió la brecha estructural que ya estaba documentada aquí: `roles.taller_id` y `asignaciones_rol.taller_id` (002) tenían FK hacia la tabla vieja — la migración suelta esas dos FK antes del `DROP`, recrea `talleres` con el esquema completo de `003-plan.md` (CHECK lat/lon/estado, índices GIST/estado/visible, soft delete), y vuelve a crear las FK contra la tabla nueva. Verificado corriendo `php artisan migrate` contra la BD real (no solo en el test DB): no había filas con `taller_id` no nulo en `roles`/`asignaciones_rol` todavía, así que no hubo huérfanos.
- **3 migraciones nuevas**: `categorias` (catálogo, slug único, `activo`), `talleres_categorias` (pivote PK compuesta con `orden`), `talleres_horarios` (`UNIQUE(taller_id, dia_semana)` + CHECK `dia_semana BETWEEN 1 AND 7` + CHECK `cerrado OR (apertura/cierre NOT NULL)` + CHECK `cierre > apertura`).
- **Bug real encontrado y corregido en infraestructura de `017`** (no de `003`, pero solo se manifestaba al usarla de verdad por primera vez): `HasGeolocation::bootHasGeolocation()` asignaba `$model->geom = DB::raw(...)` directamente. Como `Taller` ahora sí tiene `'geom' => GeometryCast::class` en sus casts, Eloquent intercepta esa asignación por `isClassCastable()` y llama a `GeometryCast::set()` con el `Expression` crudo en vez de un array `lat`/`lon` — hubiera roto con `Undefined property` en cualquier guardado. Corregido: el hook ahora asigna `['lat' => .., 'lon' => ..]` y deja que el cast arme el `ST_SetSRID(...)`. Nadie lo había detectado porque hasta ahora ningún modelo combinaba el trait con el cast en la misma columna.
- **Otro hallazgo (no bug, comportamiento esperado de Postgres pero no obvio)**: tras un `create()`, Postgres solo devuelve el PK en el INSERT — las columnas con `DEFAULT` a nivel de BD (`cantidad_resenas`, `calificacion_promedio`, y `geom` vía el cast) quedan en `null`/sin resolver en la instancia en memoria hasta hacer `->fresh()`/`->refresh()`. Esto llevó a simplificar `CambiarVisibilidadTallerAction`: la validación de "necesita geolocalización" se comprobaba también contra `$taller->geom`, pero en una instancia recién creada eso siempre da `null` (el cast recibe el `Expression` sin resolver, no un hex string). Se corrigió para validar solo `lat`/`lon` — son la fuente de verdad (constitution.md §1) y son `NOT NULL`, así que `geom` es redundante como validación.
- **Modelos**: `Taller` (usa `HasGeolocation`, `HasSlug` de spatie/laravel-sluggable, `SoftDeletes`; `scopeVisibleEnMarketplace()` con las 3 condiciones del spec — `SoftDeletes` ya excluye `deleted_at` por defecto), `Categoria` (`HasSlug`), `TallerHorario`.
- **`BelongsToTallerScope` actualizado**: ahora excluye automáticamente cualquier registro cuyo `taller_id` apunte a un taller con `deleted_at NOT NULL` (criterio explícito de `003`), usando `whereNotExists` correlacionado — **no** `whereNotIn` (`NULL NOT IN (...)` evalúa `NULL` en SQL y hubiera excluido también las filas con `taller_id` NULL, como los roles/asignaciones globales de `002`). Testeado aplicando el scope directamente contra la tabla `roles` (aún no existe ningún modelo real que use el trait `BelongsToTaller`; eso llega recién en `007+`), incluyendo el caso de restauración (`taller->restore()` reactiva el acceso sin migración).
- **`GuardarHorarioTallerRequest`**: Form Request con las mismas reglas que los CHECK de BD (apertura/cierre obligatorias si no está cerrado, cierre > apertura, único por taller+día), para dar error legible antes de tocar la BD. Sin controlador/ruta todavía (no hay UI, mismo patrón que el resto del proyecto) — testeado instanciando el Request directamente y corriendo `Validator::make()` con sus `rules()`.
- **Actions `CambiarVisibilidadTallerAction`/`CambiarPropietarioTallerAction`**: auditadas vía el helper `activity()` de spatie/laravel-activitylog (la misma tabla genérica que ya usaba `BelongsToTaller::sinScope()`), **no** la tabla `auditoria_eventos` de `015-auditoria` — esa tabla no existe todavía (015 depende de 003 y se implementa después en el orden del proyecto). Documentado en el docblock de ambas Actions para revisar cuando se implemente 015. Propietario valida que el nuevo dueño ya tenga una asignación de rol `OWNER` vigente en ese taller (no la crea automáticamente — responsabilidad de `AsignarRolAction`, ya existente).
- **Factories**: `TallerFactory` reescrita para el esquema nuevo (con estados `visible()`/`suspendido()`/`inactivo()`), `CategoriaFactory`, `TallerHorarioFactory` nuevas.
- **Colateral arreglado** (rompía por el cambio de esquema, no estaba en el alcance de `003` pero quedaría roto si no se tocaba): `TalleresSeeder` (importador de GeoJSON real de OSM, no registrado en `DatabaseSeeder`) ya no asigna `horario`/`geom` explícito — `geom` ahora se deriva solo. `TallerController@index` (ruta prototipo `GET /api/talleres`, previa a los specs) ya no selecciona la columna `horario` eliminada.
- **28 tests Pest nuevos** en `tests/Feature/Talleres/` cubriendo los criterios de `003-gestion-talleres/tasks.md`: CHECK lat/lon (BD), sincronización geom↔lat/lon, slug con colisión (sufijo numérico automático), visibilidad en marketplace (estado+visible+geom, excluye soft-deleted), CHECK de horario (cierre>apertura, obligatoriedad si no cerrado, único por día) tanto a nivel BD como Form Request, defaults de calificación en 0, `BelongsToTallerScope` excluye/reactiva hijas de taller soft-deleteado/restaurado, `CambiarVisibilidadTallerAction` y `CambiarPropietarioTallerAction` (permisos, vigencia de rol OWNER). **75/75 tests verdes en total** (47 previos + 28 nuevos), `laravel/pint` sin pendientes.

### Qué falta de `003` (por qué sigue en `status: draft`)

Mismo patrón que `001`/`002`: backend completo y probado, **UI pendiente**.

- Filament Resource `TallerResource` (panel `/erp`, filtrado por permiso `taller.configurar`) y vista global en `/admin` (tabla de todos los talleres para Super Admin).
- Listener de recálculo de `calificacion_promedio`/`cantidad_resenas` sobre eventos de `Resena`: **no se puede implementar todavía** porque el modelo `Resena` no existe (`006-resenas-favoritos` va después en el orden). Las columnas y sus defaults (0) ya están listas; el listener se agrega en `006`.
- Advertencia al Super Admin antes de soft-deletear un taller con entidades hijas activas: depende de (a) la UI de Filament y (b) que existan entidades hijas reales (`007` en adelante). El soft delete en sí (no bloqueante) ya funciona — falta el aviso visual.
- Ningún modelo real usa todavía el trait `BelongsToTaller` (los primeros candidatos son `Cliente`/`Vehiculo`/`Empleado` de `007`/`008`) — el scope se testeó aislado contra `roles` como vehículo de prueba, pero su primer uso real en producción llega recién ahí.

## Qué se hizo el 2026-07-26 (tercera sesión): 002-roles-permisos (backend completo + 21 tests verdes)

**Decisión de arquitectura confirmada con el usuario:** `002-roles-permisos/plan.md` dejaba abierto cómo mapear roles/permisos (usar Spatie nativo vs. tablas custom). Se eligió **implementación 100% custom, sin `spatie/laravel-permission`**: los criterios de aceptación exigen columnas de negocio en la asignación (`vigente_desde`/`vigente_hasta`, `asignado_por_usuario_sistema_id`, `activo` por asignación) que el pivot nativo de Spatie no soporta sin duplicar lógica en una tabla paralela.

- **`spatie/laravel-permission` fue removido por completo**: rollback de sus migraciones (batch 3), `composer remove`, `config/permission.php` borrado. No queda ningún rastro del paquete en el proyecto — si en el futuro alguien ve referencias a él en un `plan.md` viejo, están obsoletas.
- **4 migraciones nuevas** (`2026_07_26_050001` a `050004`): `roles` (taller_id NULL=global, unique parcial de slug para roles globales), `permisos` (catálogo global, sin taller_id), `roles_permisos` (pivote PK compuesta), `asignaciones_rol` (con `vigente_desde`/`vigente_hasta` + CHECK de que hasta >= desde, `activo`, `asignado_por_usuario_sistema_id`, unique parcial para asignaciones globales). Todas verificadas contra Postgres real (el CHECK de vigencia se probó con un insert que lo viola).
- **Modelos nuevos**: `App\Models\Rol` (belongsToMany Permiso vía `roles_permisos`, belongsTo Taller nullable), `App\Models\Permiso`, `App\Models\AsignacionRol` (con `estaVigente()`).
- **`UsuarioSistema` extendido**: `asignacionesRol()`, `asignacionesVigentes()` (filtra por `activo` + rango de fechas), `tienePermiso($slug, $tallerId=null)`, `esSuperAdmin()` (chequea rol slug `super-admin`). **`canAccessPanel()` ya no es un stub**: `/admin` exige `esSuperAdmin()`, `/erp` exige al menos una asignación vigente con `taller_id` no nulo.
- **`App\Actions\Roles\AsignarRolAction`**: valida usuario/rol activos, `rol.taller_id === asignacion.taller_id` (cuando el rol no es global), solo super admin asigna `SUPER_ADMIN` o roles globales, un owner/admin de taller no puede asignar fuera de su propio taller, rechaza asignaciones duplicadas. `asignadoPor = null` está reservado para el bootstrap del seeder (no existe super admin todavía cuando se crea el primero).
- **Seeders** (`PermisoSeeder`, `RolSistemaSeeder`, `SuperAdminSeeder`, cableados en `DatabaseSeeder`): 64 permisos del catálogo, 9 roles de sistema (`super-admin`, `owner`, `shop-admin`, `mecanico`, `cajero`, `recepcionista`, `supervisor`, `vendedor`, `marketplace-user`) con su mapping completo de permisos (`specs/017-infraestructura-sistema/plan.md` §8), y el primer usuario Super Admin (reutiliza `GenerarCredencialInicialAction` de 001 + `AsignarRolAction`). Corrido contra la BD real: 64 permisos, 9 roles, `esSuperAdmin()`/`tienePermiso()`/`canAccessPanel()` verificados con tinker.
- **Factories nuevas**: `TallerFactory`, `RolFactory`, `PermisoFactory` (los tres modelos ahora usan `HasFactory`).
- **21 tests Pest nuevos** en `tests/Feature/Roles/` cubriendo los 8 criterios de `002-roles-permisos/spec.md`: ámbito de taller (coincide/no coincide), duplicados, usuario/rol inactivo, solo super admin asigna SUPER_ADMIN, owner no asigna fuera de su taller ni roles globales, vigencia (CHECK de BD + `tienePermiso()` ignora asignaciones fuera de rango), acceso a paneles Filament, catálogo de permisos global (sin columna `taller_id`), slug de rol global único pero repetible entre talleres distintos. **47/47 tests verdes en total** (26 de 001 + 21 de 002), `laravel/pint` sin pendientes.
- Se actualizaron los comentarios obsoletos en `GenerarCredencialInicialAction`/`CrearUsuarioSistemaAction` (de 001) que decían "pendiente hasta que exista 002" — ahora documentan que la asignación de rol se compone desde afuera con `AsignarRolAction`, a propósito (responsabilidad única).

### Qué falta de `002` (por qué sigue en `status: draft`)

Igual que `001`: backend completo y probado, **UI pendiente** — no se marca `implemented` hasta que exista también la interfaz.

- Filament Resource para que el Super Admin administre el catálogo de permisos y los roles globales (`plan.md` mencionaba `filament/spatie-laravel-permission-plugin`, descartado junto con Spatie — hay que construir un Resource propio).
- UI para que un owner/admin de taller cree roles personalizados de su taller y les asigne permisos.
- Formulario de asignación de rol filtrado por taller activo (dependía del plugin de Spatie, ahora es un Resource/Action propio sobre `AsignarRolAction`).
- No hay ninguna restricción de código que impida crear un `Permiso` fuera del seeder (el criterio "solo el super admin crea/edita el catálogo de permisos" no tiene ninguna superficie de escritura expuesta todavía, así que no aplica un enforcement real hasta que exista esa UI).

### Dos brechas estructurales detectadas (documentadas, no bloquean 002 pero hay que resolverlas cuando toque)

1. **`MARKETPLACE_USER` no se puede asignar realmente**: `asignaciones_rol.usuario_sistema_id` es FK a `usuarios_sistema`, no a `usuarios_marketplace`. El rol se seedea igual (queda en el catálogo, `spec.md` lo pide explícitamente como "rol de sistema recomendado"), pero no hay forma de asignarlo a un usuario marketplace con el esquema actual. Cuando se implemente `006-resenas-favoritos`, decidir: (a) los permisos `marketplace.*` se chequean directamente sin pasar por `asignaciones_rol` (más simple, probablemente lo correcto ya que el guard es distinto), o (b) se extiende el esquema. **Recomendación: opción (a)**, no forzar el sistema de roles del guard `sistema` sobre el guard `web`.
2. **FK de `roles`/`asignaciones_rol` hacia `talleres.id`**: apunta al prototipo actual de `talleres` (`2026_07_19_174015_create_talleres_table.php`). Cuando `003-gestion-talleres` ejecute su migración de reemplazo (`DROP TABLE talleres` + recrear, ya documentada en `017-plan.md` §7), esa `DROP` va a fallar por las FK de `roles.taller_id` y `asignaciones_rol.taller_id` apuntando a la tabla vieja. Quien implemente `003` necesita o (a) dropear/recrear esas FK como parte de la misma migración de reemplazo, o (b) usar `Schema::table('talleres')->drop()` con las FKs dependientes contempladas explícitamente. ~~**No se resolvió aquí** porque es trabajo de `003`, fuera de orden de implementación.~~ **Resuelto en la cuarta sesión** (opción (a): `2026_07_26_060001_replace_talleres_table.php` suelta ambas FK antes del DROP y las recrea después contra la tabla nueva).

## Qué se hizo el 2026-07-26 (segunda sesión): commit, correcciones post-instalación y push

- Se corrigieron `AGENTS.md`, `017-plan.md` y `017-tasks.md`:
  - **AGENTS.md**: reescrito con tabla de orden de implementación obligatorio (paso 0→7) + notas sobre specs transversales.
  - **017-plan.md**: `maatwebsite/laravel-excel:^3` → `maatwebsite/excel` (v4.x, compatible Laravel 11+; si falla, fallback `spatie/simple-excel`). Eliminado `@tailwindcss/forms` — **Tailwind v4 no lo necesita**, Preflight + utility classes manejan forms nativamente.
  - **017-tasks.md**: refleja los mismos cambios con nota de verificación.
- Se creó la rama `specs/planificacion`, se agregaron todos los specs y documentación, y se subió a `origin/specs/planificacion` (commit `7e7f074`).
- Posteriormente se commiteó y pusheó el código de implementación de `017` (infraestructura: paquetes, PostGIS, BelongsToTaller, GeometryCast, etc.) y `001` (backend + tests de identidad y autenticación) en el commit `eca323c`. **Ya no está pendiente — no reintentar.**

## Qué se hizo el 2026-07-26 (primera sesión): instalación de paquetes (`017-infraestructura-sistema`, fase 0)

Se instalaron los paquetes Composer/NPM de `017-infraestructura-sistema`. El proyecto Laravel ya no es un esqueleto vacío: corre Laravel 13.8 / PHP 8.5.4, y sobre eso se instalaron Filament v5.7.3, spatie/laravel-permission 7.4.2, laravel/socialite 5.29, spatie/laravel-sluggable 4.0.2, spatie/laravel-activitylog 5.0, spatie/simple-excel 3.10, guzzlehttp/guzzle 7, y (dev) laravel/breeze 2.4, barryvdh/laravel-debugbar 4.4. Pest v4 ya venía instalado con el skeleton.

**Todo esto se verificó con Context7 + Packagist contra el estado real de PHP 8.5.4/Laravel 13.8 antes de instalar**, porque `017-plan.md` había quedado desactualizado en varios puntos. El detalle completo (qué cambió y por qué) está documentado en `specs/017-infraestructura-sistema/plan.md` §1 y `tasks.md` — léelos antes de asumir que el plan original es fiel a lo instalado. Resumen de las decisiones que importan para retomar:

1. **Filament v5, no v3.2** (decisión confirmada con el usuario). v3 no tiene compatibilidad garantizada con Laravel 13/PHP 8.5 y plugins clave ya no la soportan.
2. **`maatwebsite/excel` no se pudo instalar** (conflicto de versión de PHP con `phpoffice/phpspreadsheet`). Se sustituyó por `spatie/simple-excel` + `pxlrbt/filament-excel` fue removido del plan. **Los exports en Resources de Filament (specs 007-015) se implementan con acciones custom sobre `SimpleExcelWriter`, no con `ExportBulkAction`.**
3. **Se detectaron y corrigieron dos APIs de Filament inventadas** en el plan original (`->tenantOwnership(Ownership::new(...))` y `->spatiePermission()` — ninguna existe en ninguna versión real de Filament). La autorización real se integra vía Laravel Model Policies; la tenancy nativa real es `->tenant(Model::class, slugAttribute: ...)`. **Al implementar los PanelProviders y cualquier Resource de Filament, no confiar en la sintaxis literal de versiones previas de `017-plan.md` — verificar contra la documentación real de Filament v5 (Context7) primero.**
4. Se habilitó la extensión PHP `intl` en `php.ini` (requerida por Filament v5, estaba deshabilitada en el sistema).
5. `php artisan filament:install --panels` + `make:filament-panel erp` ejecutados: `AdminPanelProvider` y `ErpPanelProvider` creados y registrados en `bootstrap/providers.php`. **Aún no tienen la configuración de colores/middleware/tenancy del plan** — son el scaffold default de Filament, falta personalizarlos.
 6. Config y migraciones de `spatie/laravel-permission` y `spatie/laravel-activitylog` publicadas (tablas aún no migradas — falta correr `php artisan migrate` una vez esté lista la migración de PostGIS que debe ir primero).

### Qué más se hizo el 2026-07-26: resto de infra autocontenida de `017`

Después de la instalación de paquetes se completó todo lo de `017-infraestructura-sistema` que **no depende de modelos de otras specs**:
- Migración PostGIS (`0000_00_00_000001_create_extension_postgis.php`), migrada. PostgreSQL 18.3 + PostGIS 3.6.2 ya estaban en el servidor.
- `config/app.php` timezone `America/La_Paz`.
- Queue: `QUEUE_CONNECTION=database` ya estaba en `.env`, y la tabla `jobs` ya viene por defecto en el skeleton de Laravel 13 — no hizo falta nada.
- `config/permission.php`: `teams=true` + `team_foreign_key=taller_id`, seteado **antes** de migrar las tablas de permisos (importante: si se migra con `teams=false` y se cambia después, hay que dropear y re-migrar esas tablas — spatie/laravel-permission no las actualiza retroactivamente).
- `app/Traits/BelongsToTaller.php` + `app/Models/Scopes/BelongsToTallerScope.php` + `sinScope()`.
- `app/Actions/SequentialCodeGenerator.php` (con un bug del plan corregido: el parámetro `$retries` no se usaba).
- `app/Traits/HasGeolocation.php` + `app/Casts/GeometryCast.php` — **verificados contra PostGIS real** (round-trip `get()`/`set()` con `ST_AsHexEWKB` da el resultado exacto, no solo revisado a ojo). El plan original no traía código para `GeometryCast`, se implementó desde cero decodificando EWKB hexadecimal a mano.

### Por qué se pausó `017` ahí y se sigue con `001`

Lo que queda de `017-infraestructura-sistema` (seeding de permisos/roles/super admin/catálogos, personalización real de los PanelProviders, middleware `SetTallerActivo`, rutas API/web, migración de reemplazo de `talleres`) **depende de modelos que no existen hasta `001`/`002`**: `usuarios_sistema`, `Identidad`, `Rol`/`Permiso` con su relación `asignacionesRol()`, controladores de `005`/`006` para las rutas. Esto es una inconsistencia estructural del plan original de `017` (fue escrito asumiendo que esos modelos ya existirían), documentada en detalle en `specs/017-infraestructura-sistema/tasks.md`.

**Decisión (2026-07-26, confirmada con el usuario):** en vez de forzar un workaround (ej. crear la migración de reemplazo de `talleres` sin la FK a `usuarios_sistema`, o inventar el shape de `asignacionesRol()`), se pausa `017` en su punto autocontenido y se pasa a implementar `001-identidad-autenticacion`. Cuando existan esos modelos, retomar `017-infraestructura-sistema/tasks.md` para terminar: seeding, `SetTallerActivo`, paneles Filament personalizados (paleta, middleware, guard `sistema`), rutas, y la migración de reemplazo de `talleres` (con la FK correcta esta vez).

## Qué se hizo después: 001-identidad-autenticacion (backend completo, sin UI)

Implementado y **verificado funcionalmente contra la BD real** (transacciones con rollback en cada prueba, no solo `class_exists`):

- 6 migraciones (`identidades`, `usuarios_marketplace`, `identidades_oauth`, `usuarios_sistema`, `credenciales_sistema`, `historial_passwords`) + 6 modelos Eloquent con relaciones probadas de punta a punta.
- Se eliminó la tabla/modelo `users` default de Laravel (no es parte de esta arquitectura) — `DatabaseSeeder` limpiado.
- Guards `web`/`sistema` en `config/auth.php`. `UsuarioMarketplace`/`UsuarioSistema` extienden `Illuminate\Foundation\Auth\User`, sin remember-me (`$rememberTokenName = ''`).
- OAuth Google: `GoogleAuthController`, `LoginOrRegisterMarketplaceUserAction` (idempotencia por `provider_subject` verificada: 2 logins seguidos no duplican nada).
- `App\Auth\UsuarioSistemaProvider` (provider custom): rechaza login si `activo=false` o `bloqueado_hasta` en el futuro — un provider `eloquent` estándar no puede validarlo porque esos datos viven en `CredencialSistema`, no en `UsuarioSistema`. Probado: cuenta bloqueada con password correcta → rechazada; desbloqueada → aceptada.
- Listeners `RegistrarIntentoFallidoListener`/`ReiniciarIntentosFallidosListener` (auto-descubiertos, sin registro manual): 5 fallos → bloqueo 15 min; login exitoso resetea el contador. Probado end-to-end con `Auth::attempt()` real.
- `CambiarPasswordAction`: rechaza reutilizar la actual o cualquiera de las últimas 5, recorta el historial a 5. Probado con 7 cambios sucesivos.
- `GenerarCredencialInicialAction` / `CrearUsuarioSistemaAction`: generan password temporal de 16 caracteres que cumple la política (`Password::min(12)->mixedCase()->numbers()->symbols()`), verificado contra el validador real.
- `App\Exceptions\BusinessException` (transversal, constitution.md §4) creada porque hacía falta para `CambiarPasswordAction`.
- `CheckSessionExpiration` (30 min de inactividad, guard `sistema`) registrado en ambos PanelProviders de Filament.
- Hallazgo importante: Filament v5 **ya trae rate limiting nativo** en su página de Login (`WithRateLimiting`, 5 intentos/60s por IP) — cubre el `throttle:5,1` del spec sin código adicional.
- Decisión: **no se corrió `laravel/breeze:install`** — genera scaffolding de login/registro con contraseña atado a una tabla `users` que ya no existe en esta arquitectura. Se implementó el flujo OAuth a mano en su lugar. El paquete queda instalado por si sirve de referencia.

### Qué falta de `001`

- **UI**: pantalla/Livewire component de cambio de contraseña obligatorio (primer login + expirada) — el backend (`CambiarPasswordAction`) ya está listo para que la consuma.
- OWASP: `APP_DEBUG=false`/`composer audit` en CI (no hay pipeline de CI configurado todavía en este repo), signed routes para desbloqueo de usuario (no hay pantalla que las emita aún, es de `002`/`008`).
- La asignación de rol `OWNER`/rol de empleado al crear un usuario sistema queda con una nota explícita en el código (`GenerarCredencialInicialAction`, `CrearUsuarioSistemaAction`) — depende del catálogo de roles de `002-roles-permisos`, que no existe todavía.

## Qué se hizo después: suite de tests Pest de 001 (26/26 verdes)

Se provisionó infraestructura de test real (no se podía usar antes de ahora):
- BD dedicada `taller_test` en el PostgreSQL local, `.env.testing`, `RefreshDatabase` habilitado en `tests/Pest.php`.
- **`phpunit.xml` forzaba SQLite en memoria** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), lo que tiene prioridad sobre `.env.testing` y violaba `constitution.md` §6 explícitamente. Se quitaron esas líneas.
- Factories nuevas: `IdentidadFactory`, `UsuarioSistemaFactory`, `CredencialSistemaFactory`, `UsuarioMarketplaceFactory`.
- 9 archivos de test en `tests/Feature/Identidad/` cubriendo los 9 criterios de `001-identidad-autenticacion/tasks.md` (idempotencia OAuth, separación de guards, bloqueo por intentos, rate limiting nativo de Filament vía `Livewire::test()`, política de password incluyendo `uncompromised()` con `Http::fake()`, expiración de password, reutilización de historial, expiración de sesión, password nunca expuesto en serialización).
- **26/26 tests pasan**, 55 assertions. `laravel/pint` sin pendientes.

### Tres bugs reales que la suite encontró (no solo bugs de los tests) — habrían llegado a producción

1. **`UsuarioSistema` no implementaba `Filament\Models\Contracts\FilamentUser`** → Filament v5 deniega el acceso a **todos** los paneles (403) por defecto sin ese contrato. Ningún usuario sistema habría podido entrar a `/admin` ni `/erp`. Corregido con `canAccessPanel()` (por ahora solo exige `activo`; restricción real por rol pendiente de `002`).
2. **Tampoco implementaba `Filament\Models\Contracts\HasName`** → Filament intenta leer un atributo `name` inexistente (el modelo usa `nombre`/`apellido`) y explota con `TypeError` al renderizar cualquier página. Corregido con `getFilamentName()`.
3. **Bug real en `CheckSessionExpiration`**: `now()->diffInMinutes($ultimaActividad) > 30` — en la versión de Carbon de este proyecto `diffInMinutes()` devuelve un valor **con signo** (negativo para fechas pasadas), no absoluto. La sesión nunca habría expirado por inactividad pese a que el código "se veía" correcto. Corregido comparando fechas directamente (`->lt(now()->subMinutes(30))`).

Estos tres son la razón de peso para no saltarse la suite de tests aunque el backend ya estuviera "funcionalmente verificado a mano" — las pruebas manuales por tinker no pasan por la pila real de Filament (paneles, Livewire) ni ejercitan el paso del tiempo, así que no los habrían atrapado.

## Qué es este proyecto

Plataforma de talleres mecánicos en Santa Cruz, Bolivia: marketplace público (conductores buscan talleres, con reseñas/favoritos, login Google) + mini-ERP por taller (clientes, vehículos, empleados, inventario, órdenes de trabajo, notas de venta, pagos), multi-tenant por `taller_id`. Stack objetivo: Laravel 13, PHP 8.4+, PostgreSQL + PostGIS, Blade+Tailwind+Alpine (marketplace), FilamentPHP v3 (ERP + Super Admin).

## Qué se hizo en esta sesión

Se ejecutó una reestructuración completa de `/specs`, que originalmente eran 4 archivos en bruto sin frontmatter, mezclando QUÉ y CÓMO, sin descomponer por feature:
- `MacroSpec.md` (monolito de ~17 dominios de negocio)
- `Baterias.md` (decisiones de paquetes Composer)
- `UI_Spec.md` (pantallas/UI de los 3 productos)
- `DataBase/database.md` (39 tablas del esquema físico)

**Esos 4 archivos ya no existen** — su contenido se migró y se eliminaron (nunca estuvieron en git, no se perdió historial).

### Resultado

```
AGENTS.md                    <- guía de navegación para agentes (leer primero)
memory/constitution.md       <- convenciones no-negociables (arquitectura, datos, reglas transversales, errores, UI, testing, seguridad)
resume.md                    <- este archivo
specs/
  001-identidad-autenticacion/
  002-roles-permisos/
  003-gestion-talleres/
  004-solicitud-alta-taller/
  005-marketplace-busqueda-perfil/
  006-resenas-favoritos/
  007-clientes-vehiculos/
  008-empleados-usuarios-erp/
  009-catalogo-servicios/
  010-inventario-repuestos/
  011-ordenes-trabajo/
  012-notas-venta/
   013-pagos/
   014-notificaciones/
   015-auditoria/
   016-ui-design-system/
   017-infraestructura-sistema/
```

Cada carpeta de feature tiene:
- `spec.md` — QUÉ (frontmatter YAML `id`/`status`/`depends_on`/`resumen` + criterios de aceptación Given/When/Then). Sin detalles de implementación.
- `plan.md` — CÓMO (tablas, columnas, constraints, modelos Eloquent, Actions, paquetes, Filament Resources).
- `tasks.md` — checklist de tareas atómicas y verificables derivadas del plan.

Las 17 features están numeradas por orden de dependencia (`depends_on` en el frontmatter). **Todas están en `status: draft`** — nada de esto está implementado todavía en código (ver más abajo).

## Estado real del código

El proyecto Laravel ya no es un esqueleto:
- **Stack instalado**: Filament v5.7.3, laravel/socialite 5.29, spatie/laravel-sluggable 4.0.2, spatie/laravel-activitylog 5.0, spatie/simple-excel 3.10, guzzlehttp/guzzle 7, laravel/breeze 2.4, barryvdh/laravel-debugbar 4.4 (dev). Pest v4 viene con el skeleton. **`spatie/laravel-permission` fue instalado y luego removido** (ver sesión 2026-07-26 tercera) — roles/permisos son 100% custom, no depende de ese paquete.
- **017-infraestructura-sistema**: migración PostGIS ejecutada, timezone configurado, `BelongsToTaller` trait + scope, `HasGeolocation` trait + `GeometryCast`, `SequentialCodeGenerator`. Seeding de permisos/roles/Super Admin hecho. **`SetTallerActivo` + `ForzarCambioPasswordMiddleware` + paneles Filament personalizados (paleta Awesomic) — hecho en la quinta sesión.** Pendiente: rutas API/web (bloqueadas por `005`/`006`), CSS/tema Tailwind completo (junto con `016`+`005`).
- **001-identidad-autenticacion**: backend (6 migraciones + modelos, OAuth Google, guards `web`/`sistema`, provider custom, bloqueo por 5 intentos, expiración de sesión 30 min, historial de 5 contraseñas) + **UI de cambio de contraseña obligatorio (quinta sesión)**. 26 tests de backend + cubierto por los tests de middleware de la quinta sesión.
- **002-roles-permisos**: backend (4 migraciones + modelos `Rol`/`Permiso`/`AsignacionRol`, `AsignarRolAction`, `tienePermiso()`/`esSuperAdmin()`/`canAccessPanel()`, catálogo de 64 permisos + 9 roles de sistema seedeados) + **UI (quinta sesión): `PermisoResource`, `RolResource` (admin+erp), `AsignacionRolResource`**. 21 tests de backend + 12 tests de autorización/render de Resources.
- **003-gestion-talleres**: backend (migración de reemplazo de `talleres`, `categorias`/`talleres_categorias`/`talleres_horarios`, modelos `Taller`/`Categoria`/`TallerHorario`, `CambiarVisibilidadTallerAction`/`CambiarPropietarioTallerAction`) + **nueva Action `CambiarEstadoTallerAction` y UI (quinta sesión): `TallerResource` (erp+admin), `CategoriaResource`**. 28 tests de backend + 12 tests de `CambiarEstadoTallerAction`/autorización de Resources. Pendiente: listener de recálculo de calificación (depende de `Resena`, spec `006`), conteo real de hijas activas antes de soft-delete (depende de `007+`).
- **Total: 107/107 tests Pest verdes**, `laravel/pint` sin pendientes.
- **Prototipo Taller viejo**: ya reemplazado por la migración de `003` (`2026_07_26_060001_replace_talleres_table.php`). El `TalleresSeeder` (importador de GeoJSON de OSM, no registrado en `DatabaseSeeder`) y la ruta prototipo `GET /api/talleres` (`TallerController@index`) se actualizaron para no romper con el esquema nuevo.
- Git: repositorio en rama `specs/planificacion`. Código de `017`+`001` commiteado y pusheado (`eca323c`). Código de `002` (`08c2d90`), `003` (`dee42c2`), y UI de 017/001/002/003 (`96bce0d`) — **todo commiteado y pusheado en `origin/specs/planificacion`**.

## Decisiones resueltas (2026-07-25)

1. **`002-roles-permisos` — Roles operativos**: son roles de sistema fijos (`es_sistema = true`). El admin de taller puede crear roles personalizados adicionales pero no eliminar/modificar los de sistema.
2. **`002-roles-permisos` — Catálogo de permisos**: lista final aprobada (60 permisos en 16 módulos, definida en `plan.md`).
3. **`012-notas-venta` — Múltiples notas por orden**: permitido (regla permisiva), respetando ACID.
4. **`011-ordenes-trabajo` / `012-notas-venta` — Motivo de anulación**: **obligatorio** en ambos casos. Reglas ACID definidas: bloqueo si hay pagos, auto-anulación de nota EMITIDA al anular orden, reposición automática de stock.
5. **`016-ui-design-system`**: aprobado. Awesomic en marketplace (Blade), paleta mapeada en Filament. Sin modo oscuro en marketplace. Alpine stores + data components.

## Brechas críticas cubiertas (2026-07-25)

1. **`017-infraestructura-sistema`** — paquetes, paneles Filament, BelongsToTaller, concurrencia en códigos, rutas API, helpers PostGIS, migración de reemplazo de talleres.
2. **`005`** — endpoint JSON `GET /api/talleres/search` para Alpine.fetch().
3. **`006`** — endpoints `POST /api/resenas`, `DELETE /api/resenas/{id}`, `POST /api/favoritos`, `POST /api/favoritos/delete`.
4. **`001`** — ruta `POST /auth/logout` para marketplace.
5. **`003`** — política de soft delete en cascada (sin bloqueo, advertencia al Super Admin).
6. **`003`** — migración de reemplazo del prototipo `talleres` documentada en `017-plan.md`.

## Brechas de infraestructura y calidad cubiertas (2026-07-25)

| # | Omisión | Solución |
|---|---|---|
| 1 | Seeding inicial | `017-plan.md` §8: Super Admin, permisos, roles + mapping, métodos pago, unidades, categorías |
| 2 | PostGIS requisito | `017-plan.md` §0: migración `CREATE EXTENSION postgis` |
| 3 | Timezone | `017-plan.md` §0: `config/app.php: timezone => America/La_Paz` |
| 4 | Queue | `017-plan.md` §0: `QUEUE_CONNECTION=database`, tabla jobs migrada, recálculo síncrono |
| 5 | "Abierto ahora" vía SQL | `005-plan.md`: LEFT JOIN a `talleres_horarios` con `whereTime`, no post-query |
| 6 | Soft delete bloqueante | `constitution.md` §3.13: bloquea si hay hijos activos (excepción: taller) |
| 7 | Error handling pattern | `constitution.md` §4: `BusinessException` → 422/redirect, toast Filament, JSON error |
| 8 | Autocomplete en ERP | `017-plan.md` §9: patrón `->searchable()->getSearchResultsUsing()` con global scope |
| 9 | File uploads | `constitution.md` §1: `Storage::putFile()` (disco `public`), no medialibrary |
| 10 | Naming convention | `constitution.md` §1: `app/Actions/{Feature}/{Nombre}Action.php` |
| 11 | Test DB | `constitution.md` §6: PostgreSQL dedicado (`.env.testing`), no SQLite |
| 12 | Exportaciones | `017-plan.md` §9: Resources que exportan listados |
| 13 | Loading states | `constitution.md` §5: skeleton en búsqueda, spinner en mapa |
| 14 | Notification triggers | `014-plan.md`: tabla con 10 eventos, feature origen, destinatario |

## Próximos pasos sugeridos

1. ~~Commitear y pushear el código de implementación de `017` y `001`~~ **Hecho** (commit `eca323c` en `origin/specs/planificacion`).
2. ~~Implementar `002-roles-permisos`~~ **Hecho** (commit `08c2d90` en `origin/specs/planificacion`).
3. ~~Implementar `003-gestion-talleres`~~ **Hecho** (commit `dee42c2` en `origin/specs/planificacion`).
4. ~~Retomar `017-infraestructura-sistema`: `SetTallerActivo`, paneles Filament personalizados (paleta)~~ **Hecho** (quinta sesión). Rutas API/web siguen bloqueadas por `005`/`006` (no tocar antes).
5. ~~Implementar la UI (Filament Resources) de `001`, `002` y `003`~~ **Hecho** (commit `96bce0d` en `origin/specs/planificacion`).
6. Seguir en orden de dependencia: `004-solicitud-alta-taller` → `005` + `016` → `006` → `007`…`015`. Al llegar a `006-resenas-favoritos`, recordar dos cosas pendientes de sesiones previas: (a) implementar el listener de recálculo de `calificacion_promedio`/`cantidad_resenas` de `Taller` sobre eventos de `Resena` (columnas y defaults ya listos desde `003`), y (b) decidir cómo asignar el rol `MARKETPLACE_USER` (recomendación ya documentada: chequear permisos `marketplace.*` sin pasar por `asignaciones_rol`, ver brecha estructural de la tercera sesión más abajo).
7. ~~Al completar una feature con código + tests que cubran sus criterios de aceptación **y su UI**, actualizar `status: implemented`~~ **Hecho para 001, 002 y 003** (confirmado por el usuario tras la quinta sesión, commit `96bce0d`).

## Cómo navegar si eres un agente retomando esto

Lee `AGENTS.md` primero — tiene las reglas de navegación completas. Resumen rápido: `memory/constitution.md` una vez (convenciones globales, no se repiten por feature) → `specs/<feature>/spec.md` → `plan.md` → `tasks.md` de la feature que vayas a trabajar. No leas features no relacionadas salvo que aparezcan en `depends_on`.