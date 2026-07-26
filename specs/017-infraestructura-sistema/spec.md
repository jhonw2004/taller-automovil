---
id: 017-infraestructura-sistema
status: draft
depends_on: []
resumen: "Configuración global del proyecto: instalación de paquetes, paneles Filament, trait multi-tenant BelongsToTaller, generación concurrente de códigos, registro de rutas API, helpers de PostGIS."
---

# Infraestructura del Sistema

## Propósito

Centralizar toda la configuración transversal que no pertenece a una sola feature: qué paquetes instalar y cómo configurarlos, cómo se estructuran los paneles Filament, cómo se implementa el aislamiento multi-tenant por `taller_id`, cómo se generan códigos secuenciales sin colisiones bajo concurrencia, y cómo se organizan las rutas API que consume Alpine.

## Actores

- Desarrollador (sigue esta guía para configurar el proyecto desde cero).
- Super admin (usa el panel `/admin` configurado aquí).
- Usuario sistema (usa el panel `/erp` configurado aquí).
- Visitante público + usuario marketplace (consumen rutas API y Blade configuradas aquí).

## Criterios de aceptación

### PostGIS habilitado

- Dado que el sistema usa `GEOMETRY(Point,4326)`, `ST_DWithin`, `GIST` indexes, cuando se ejecuta `php artisan migrate` por primera vez, entonces la extensión `postgis` debe estar habilitada en PostgreSQL.
- Dado que la migración `create_extension_postgis` se ejecuta, entonces `CREATE EXTENSION IF NOT EXISTS postgis;` se corre antes de cualquier otra migración que use tipos geométricos.

### Zona horaria configurada

- Dado que `config/app.php` se configuró, entonces `'timezone' => 'America/La_Paz'` en producción y desarrollo. El cálculo de "abierto ahora" y todos los timestamps de negocio usan esta zona.

### Cola de trabajos (queue)

- Dado que algunos procesos pueden ser asíncronos en el futuro (notificaciones, exports), entonces `QUEUE_CONNECTION=database` en `.env` y la tabla `jobs` está migrada.
- En el MVP, el recálculo de calificación de taller es **síncrono** (por regla 12 en constitution). No se usa queue para operaciones críticas del negocio.

### Seeding inicial

- Dado que se ejecuta `php artisan db:seed`, entonces el sistema crea:
  - Un **Super Admin** inicial: `username = superadmin`, contraseña temporal generada, `debe_cambiar_password = true`.
  - El **catálogo completo de permisos** (60 permisos definidos en `002-roles-permisos/plan.md`).
  - Los **roles de sistema**: `SUPER_ADMIN` (global), `OWNER`, `SHOP_ADMIN`, `MARKETPLACE_USER`, `MECANICO`, `CAJERO`, `RECEPCIONISTA`, `SUPERVISOR`, `VENDEDOR` (los operativos con `es_sistema = true`).
  - El **role-permission mapping** (qué permisos tiene cada rol — ver plan.md).
  - **Métodos de pago** globales: efectivo, tarjeta de crédito/débito, QR, transferencia bancaria.
  - **Unidades de medida**: unidad, litro, metro, kilo.
  - **Categorías de taller** de ejemplo: mecánica general, electricidad automotriz, neumáticos, diagnóstico computarizado, tuning, hojalatería y pintura.
- Dado que los seeds son idempotentes, cuando se ejecutan múltiples veces, entonces no duplican registros (usan `firstOrCreate`).

### Autocomplete / búsqueda en Resources del ERP

- Dado un campo Select en Filament que debe buscar entidades relacionadas (clientes, vehículos, servicios, repuestos, empleados, órdenes), entonces se usa `->searchable()` con `->getSearchResultsUsing()` que filtra por nombre/código + el global scope `BelongsToTaller` (el scope se aplica automáticamente).
- Ejemplo: al seleccionar un cliente en una orden, el Select busca por `nombre` o `codigo` dentro del mismo taller, con `->searchDebounce(300)`.
- Al seleccionar un cliente, el Select de vehículos se filtra automáticamente por `cliente_id` usando `->reactive()` + `->options(fn ($get) => Vehiculo::where('cliente_id', $get('cliente_id'))->pluck(...))`.

### Exportaciones

- Dado un Resource de Filament que requiere exportación a Excel, entonces se usa `pxlrbt/filament-excel` con `->export()` en la tabla.
- Los Resources que exportan en el MVP: `TallerResource` (Admin), `OrdenTrabajoResource`, `NotaVentaResource`, `ClienteResource`, `RepuestoResource`, `MovimientoInventarioResource`.

### Paneles Filament

- Dado un usuario sistema autenticado con rol `SUPER_ADMIN`, cuando accede a `/admin`, entonces ve el panel Super Admin con sidebar completo (talleres globales, solicitudes, moderación, permisos, auditoría).
- Dado un usuario sistema autenticado sin rol `SUPER_ADMIN`, cuando accede a `/admin`, entonces recibe 403.
- Dado un usuario sistema autenticado con al menos un rol activo en un taller, cuando accede a `/erp`, entonces ve el panel ERP con sidebar filtrado por sus permisos.
- Dado un usuario sistema sin roles activos, cuando accede a `/erp`, entonces se le redirige al login con mensaje "No tienes acceso a ningún taller".
- Ambos paneles (`/admin` y `/erp`) usan la paleta Awesomic definida en `016-ui-design-system` (`primary` → obsidian, `danger` → ember, escala de grises zinc).
- El panel `/admin` no tiene Tenant Switcher (el Super Admin ve todo globalmente).
- El panel `/erp` sí muestra el Tenant Switcher si el usuario tiene acceso a más de un taller.
- El middleware `auth:sistema` protege ambos paneles. El rate limiting `throttle:5,1` protege la ruta de login.

### Aislamiento multi-tenant (BelongsToTaller)

- Dado un modelo que pertenece a un taller (clientes, vehículos, empleados, servicios, etc.), cuando se ejecuta cualquier query, entonces un global scope agrega automáticamente `WHERE taller_id = ?` usando el `taller_id` activo en sesión.
- Dado un Super Admin que necesita acceder a datos de un taller específico sin el scope, cuando ejecuta `Model::withoutGlobalScope(BelongsToTallerScope::class)`, entonces la acción queda registrada en auditoría con el ID del taller accedido y el usuario.
- Dado que el scope se aplica automáticamente en todas las queries (incluyendo relaciones), cuando un usuario del taller A intenta acceder a datos del taller B a través de una relación, entonces el filtro lo impide.
- Dado un modelo con `BelongsToTaller` trait, cuando se crea un registro, entonces `taller_id` se auto-asigna desde `session('taller_activo_id')` a menos que se especifique explícitamente.
- El `taller_id` activo se setea en un middleware que se ejecuta en cada request del guard `sistema`: si el usuario tiene un solo taller, se usa ese; si tiene varios, se usa el de la sesión (o se fuerza a elegir uno vía Tenant Switcher).

### Generación concurrente de códigos

- Dado que dos usuarios intentan crear una orden de trabajo simultáneamente en el mismo taller, cuando el sistema genera el código `OT-YYYY-###`, entonces ambos obtienen códigos distintos sin colisión.
- La generación de códigos usa `FOR UPDATE` sobre la tabla de secuencia (o sobre la fila del último código del taller) dentro de una transacción, garantizando exclusión mutua.
- Si la transacción falla por deadlock, el sistema reintenta automáticamente hasta 3 veces antes de lanzar excepción.
- El patrón se aplica a: órdenes de trabajo (`OT-YYYY-###`), notas de venta (`NV-YYYY-###`), códigos de cliente, códigos de empleado, códigos de servicio, códigos de repuesto.

### Rutas API

- Todas las rutas API que consume Alpine están agrupadas bajo el prefijo `/api/` con middleware `throttle:30,1` (o el específico de cada endpoint).
- Existe un archivo `routes/api.php` (o `routes/api/` directorio) separado de `routes/web.php` para mantenerlas organizadas.

### Helpers PostGIS

- Dado que cualquier modelo necesita filtrar por cercanía geográfica, existe un scope reutilizable `scopeCercanoA($lat, $lon, $radioMetros)` que usa `ST_DWithin`.
- Dado que cualquier modelo necesita calcular distancia, existe un scope `scopeConDistanciaA($lat, $lon)` que agrega `ST_DistanceSphere` como `distancia`.
- Dado que se necesita sincronizar `geom` desde `lat`/`lon`, existe un método global que genera `ST_SetSRID(ST_MakePoint(lon, lat), 4326)`.

## Fuera de alcance (MVP)

- Auto-detección de base de datos PostGIS habilitada (se asume que PostGIS está instalado en PostgreSQL).
- Migración de datos del prototipo actual a la nueva estructura.
- Deployment automatizado (CI/CD).
