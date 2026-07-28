# Business Logic — TallerPro

## 1. ¿Qué es TallerPro?

Plataforma digital para el sector de talleres mecánicos en Santa Cruz, Bolivia. Conecta conductores con talleres (marketplace público) y provee un mini-ERP a cada taller para gestionar su operación diaria (clientes, vehículos, inventario, órdenes de trabajo, cobros). Dos caras de una misma moneda: un conductor encuentra un taller, y ese taller lo recibe con toda su información ya registrada.

---

## 2. Usuarios del sistema

### 2.1 Visitante público
Persona sin cuenta que navega el marketplace. Busca talleres por ubicación, categoría o nombre, y ve perfiles públicos. No necesita registrarse para buscar.

### 2.2 Usuario marketplace (conductor)
Persona con cuenta creada vía Google OAuth. Busca talleres, escribe reseñas (una por taller), guarda favoritos, y su vehículo puede ser registrado por el taller al llegar. Su identidad es solo marketplace — no accede al ERP.

### 2.3 Solicitante de alta de taller
Dueño de un taller que llena un formulario público (multi-paso con mapa) para solicitar que su taller sea incorporado a la plataforma. No necesita cuenta; recibe un token para dar seguimiento.

### 2.4 Usuario sistema (empleado de taller)
Personal del taller con credenciales (usuario/contraseña) para acceder al ERP. Tipos según el rol que tenga asignado:

- **Owner / Shop admin**: dueño o gerente. Ve todo lo de su taller, gestiona empleados, usuarios y permisos.
- **Mecánico**: ve las órdenes asignadas, registra avance, repuestos usados.
- **Recepcionista**: registra clientes, vehículos, crea órdenes y notas de venta.
- ** roles de inventario, servicios, pagos, etc. según el catálogo de 64 permisos.

### 2.5 Super admin
Usuario del panel `/admin` con acceso a todos los talleres y la configuración global del sistema. Aprueba o rechaza solicitudes de alta de taller, modera reseñas, gestiona el catálogo global de permisos/roles/unidades de medida/métodos de pago, y audita cualquier operación. No es empleado de ningún taller en particular.

---

## 3. El marketplace (cara pública)

### 3.1 Home y búsqueda
Cualquier visitante llega a una página principal con buscador geográfico. Puede filtrar talleres por:
- Ubicación (cerca de una dirección o coordenada)
- Categoría del taller (mecánica general, electricidad, neumáticos, diagnóstico, tuning, hojalatería)
- Texto libre (nombre del taller)
- "Abierto ahora" (según el horario configurado)

Los resultados se muestran en un mapa (Leaflet) y en una lista. El perfil público de cada taller muestra: datos de contacto, horarios, categorías, calificación promedio, reseñas y ubicación.

### 3.2 Reseñas y favoritos
Un usuario marketplace autenticado puede:
- Escribir **una única reseña** por taller (calificación 1-5 + comentario). Si ya escribió una, puede editarla o eliminarla, pero no duplicar.
- Marcar talleres como **favoritos** para encontrarlos rápido después.
- Ver sus reseñas y favoritos en un dashboard personal.

Las reseñas pueden ser moderadas (ocultadas) por el Super admin si violan las reglas. Al cambiar una reseña, la calificación del taller se recalcula al instante (promedio + cantidad total).

### 3.3 Registro e inicio de sesión
Todo en el marketplace usa Google OAuth. No hay registro con email/contraseña. El flujo es idempotente: si el usuario ya existe, inicia sesión; si no, crea la cuenta. Hay botones "Iniciar sesión" y "Registrarse" en el nav que llevan al mismo flujo de Google.

---

## 4. Solicitud de alta de taller (onboarding)

Un dueño de taller sin cuenta puede pedir ser incorporado a la plataforma mediante un formulario público de varios pasos:

1. **Datos básicos**: nombre, teléfono, email, dirección
2. **Categorías**: selecciona los tipos de servicio que ofrece
3. **Horario**: días y horas de atención
4. **Ubicación**: pincha en un mapa (OpenStreetMap / Leaflet) donde está su taller. Si el usuario da lat/lon manual, se valida contra Santa Cruz.

Al enviar, la solicitud queda **PENDIENTE**. El solicitante recibe un token de seguimiento para ver el estado sin tener cuenta.

### Flujo de aprobación (Super admin)
1. Solicitud en PENDIENTE → el Super admin la pasa a **EN_REVISIÓN**
2. Durante la revisión, puede **APROBAR** o **RECHAZAR** con un motivo
3. Si aprueba: la solicitud pasa a **APROBADA** y luego a **COMPLETADA** — en ese mismo momento se **crea el taller** en el sistema con los datos de la solicitud, y se genera un usuario sistema inicial para el dueño (con credenciales temporales que debe cambiar al primer ingreso)
4. Si rechaza: la solicitud queda en **RECHAZADA** con el motivo visible para el solicitante

En cualquier momento antes de completar, el solicitante puede **CANCELAR** su solicitud.

---

## 5. El ERP (cara interna de cada taller)

Cada taller tiene su propio entorno aislado dentro del panel `/erp`. Un usuario sistema ve y opera solo sobre los datos del taller que tiene activo en sesión.

### 5.1 Clientes y vehículos
El taller registra a sus clientes (personas naturales o jurídicas) con nombre, NIT/CI, teléfono, email. A cada cliente se le asocian uno o más vehículos con placa (formato boliviano ABC-123), marca, modelo, año y número de chasis.

La placa es única por taller — no puede haber dos vehículos con la misma placa dentro del mismo taller (sí puede repetirse entre talleres distintos). Los clientes y vehículos se marcan como inactivos (no se borran) cuando ya no son necesarios.

### 5.2 Empleados y usuarios del sistema
El taller registra a su personal (nombre, cargo, teléfono). Un empleado puede tener o no acceso al ERP:
- **Sin acceso**: solo registro administrativo (ej. un ayudante que no usa el sistema)
- **Con acceso**: se le crea un usuario sistema con nombre de usuario y contraseña temporal, y se le asigna un rol dentro del taller

El usuario debe cambiar la contraseña temporal en su primer ingreso. Las contraseñas expiran cada 90 días (con aviso 7 días antes). Si alguien falla 5 intentos de login seguidos, queda bloqueado 15 minutos.

Un empleado con acceso puede ser **desactivado por taller**: si trabaja en varios talleres de la plataforma, desactivarlo en uno no afecta su acceso a los otros.

### 5.3 Catálogo de servicios
Cada taller define los servicios mecánicos que ofrece (ej. "Cambio de aceite", "Alineación y balanceo", "Diagnóstico computarizado") con un precio base sugerido. Estos servicios se usan después al crear órdenes de trabajo. El precio se congela en el momento de crear la orden — si el catálogo cambia después, las órdenes existentes no se ven afectadas.

### 5.4 Inventario de repuestos
El taller gestiona su stock de repuestos:
- Cada repuesto tiene código único por taller, nombre, unidad de medida (unidad, litro, metro, kilo) y precio de venta.
- Se registran proveedores y qué repuestos vende cada uno.
- Los movimientos de inventario son **append-only**: no se editan ni borran. Cada vez que entra o sale stock, queda un registro inmutable.
- El stock nunca puede ser negativo. Si una venta intenta descontar más de lo que hay, la operación se rechaza.
- Cuando un repuesto llega a un nivel bajo configurable, el sistema genera un evento (que se traduce en una notificación al taller).

### 5.5 Órdenes de trabajo
Es el documento operativo central del taller. Representa el trabajo sobre un vehículo de un cliente.

**Estados por los que pasa una orden:**
```
PENDIENTE → EN_DIAGNÓSTICO → ESPERANDO_APROBACIÓN → EN_PROGRESO → COMPLETADA → ENTREGADA
                                                                          ↘ ANULADA
                                                                   PAUSADA ↗
```

1. **PENDIENTE**: el vehículo está registrado pero no se ha empezado a trabajar
2. **EN_DIAGNÓSTICO**: se está revisando para determinar qué hacer
3. **ESPERANDO_APROBACIÓN**: se le presentó el presupuesto al cliente y se espera su ok
4. **EN_PROGRESO**: el cliente aprobó y se está trabajando
5. **PAUSADA**: el trabajo se detuvo (ej. falta un repuesto). Puede volver a EN_PROGRESO
6. **COMPLETADA**: el trabajo terminó, el vehículo está listo
7. **ENTREGADA**: el cliente retiró el vehículo
8. **ANULADA**: la orden se canceló (ej. el cliente decidió no hacer el trabajo)

**Líneas de la orden:** cada orden tiene líneas de servicio (mano de obra) y/o líneas de repuesto. Cada línea tiene su propio estado (PENDIENTE, COMPLETADA, CANCELADA). Cuando se completa una línea de repuesto, el stock se descuenta automáticamente.

El total de la orden se calcula automáticamente a partir de sus líneas. La orden se asigna a un empleado (mecánico) que será el responsable del trabajo.

### 5.6 Notas de venta
Documento interno de cobro, no fiscal. Representa lo que el taller cobra al cliente.

**Formas de crear una nota:**
- **Desde una orden**: cuando la orden está COMPLETADA o ENTREGADA, se genera la nota copiando las líneas de servicio y repuesto (sin volver a descontar stock). No se pueden modificar las líneas desde la nota — son un snapshot de la orden.
- **Venta directa**: se crea la nota desde cero, eligiendo servicios del catálogo y/o repuestos (estos sí descuentan stock en el momento).

**Estados de la nota:**
```
EMITIDA → PENDIENTE → PAGADA
                ↘ ANULADA
EMITIDA → ANULADA
```

Una nota ANULADA no puede recibir pagos. Si una nota tiene pagos confirmados, no se puede anular — hay que reversar los pagos primero. Si la nota venía de una orden y se anula, los repuestos se reponen al inventario automáticamente (solo si era venta directa; si venía de orden, no, porque el repuesto ya se descontó al completar la línea de la orden).

### 5.7 Pagos
Contra una nota de venta, se pueden registrar uno o varios pagos (parciales o totales). Métodos de pago disponibles: efectivo, tarjeta, QR, transferencia.

Al registrar un pago:
- Se valida que el monto no supere el saldo pendiente
- El saldo de la nota se recalcula
- El estado de la nota se actualiza automáticamente:
  - Si monto pagado = 0 → EMITIDA
  - Si monto pagado > 0 y saldo > 0 → PENDIENTE
  - Si saldo = 0 y total > 0 → PAGADA

Un pago puede anularse (ej. si fue registrado por error), lo que dispara el recálculo de vuelta. Una nota PAGADA puede volver a PENDIENTE o EMITIDA si se anula el pago correspondiente.

### 5.8 Notificaciones in-app
Dentro de la plataforma (sin email ni SMS en el MVP), los usuarios reciben notificaciones ante estos eventos:

**Para usuarios sistema:**
- Stock bajo de un repuesto
- Pago registrado contra una nota
- Orden de trabajo asignada a mí
- Cambio de estado de una orden que sigo
- Nota de venta emitida
- Solicitud de alta de taller aprobada (al dueño)
- Solicitud de alta de taller rechazada (al dueño)
- Se me creó un usuario en el sistema

**Para usuarios marketplace:**
- Alguien respondió o moderó mi reseña
- Contraseña próxima a vencer (usuarios sistema)

Las notificaciones tienen un indicador de leídas/no leídas. Se pueden marcar como leídas una por una.

### 5.9 Roles y permisos
Cada usuario sistema tiene un rol dentro de cada taller donde trabaja:
- **Roles globales** (definidos por Super admin, aplican a todos los talleres): SUPER_ADMIN
- **Roles por taller** (definidos por el owner de cada taller): OWNER, SHOP_ADMIN, MECANICO, RECEPCION, INVENTARIO, etc.

Cada rol agrupa permisos en formato `modulo.accion` (ej. `ordenes.ver`, `ordenes.crear`, `inventario.ajustar`). Los permisos son globales — no se crean por taller.

Un usuario puede tener asignaciones vigentes en múltiples talleres, y cambia entre ellos desde un selector en el topbar del ERP.

---

## 6. Super administración (panel /admin)

El Super admin tiene un panel separado del ERP donde puede:

1. **Gestionar solicitudes**: revisar, aprobar o rechazar altas de taller
2. **Moderar reseñas**: ocultar reseñas inapropiadas
3. **Gestionar permisos y roles**: crear roles globales, definir qué permisos incluye cada rol
4. **Ver todos los talleres**: sin restricción de tenant, puede inspeccionar y editar cualquier taller
5. **Auditar**: ver el registro inmutable de accesos al sistema, eventos de negocio críticos y cambios en datos sensibles de talleres
6. **Catálogos globales**: gestionar unidades de medida, métodos de pago, categorías de taller

---

## 7. Auditoría

Tres tipos de registros inmutables (append-only, nunca se editan ni borran):

1. **Auditoría de eventos**: cada operación crítica de negocio queda registrada: cambios de estado de taller, aprobación/rechazo de solicitudes, moderación de reseñas, anulación de notas/pagos, asignación de roles, ajustes de inventario, accesos de Super admin a talleres ajenos.
2. **Auditoría de accesos**: cada login exitoso, login fallido, logout y cambio de contraseña queda registrado, incluyendo los accesos denegados (usuario autenticado pero sin permiso para el panel).
3. **Auditoría de talleres**: cualquier cambio en los campos sensibles de un taller (nombre, teléfono, email, dirección, estado, ubicación geográfica, visibilidad en mapa) queda registrado con el valor anterior y el nuevo.

---

## 8. Reglas de negocio clave

1. **Separación de identidades**: un usuario es marketplace o sistema, nunca ambas. No existe cruce.
2. **Aislamiento por taller**: cada taller ve solo sus propios datos. Ni siquiera los errores revelan datos de otros talleres.
3. **Sin stock negativo**: ninguna operación puede dejar el inventario en negativo.
4. **Transaccionalidad**: toda operación que modifica varias entidades (crear orden con líneas, aprobar solicitud que crea taller, pagar una nota) ocurre en una sola transacción de base de datos. Si algo falla, todo se revierte.
5. **Códigos únicos por taller**: órdenes, notas, clientes, vehículos, repuestos y servicios tienen códigos secuenciales que son únicos dentro de cada taller. Nunca se reutilizan, incluso si el registro original se anula.
6. **Moneda única**: todo en bolivianos (Bs). Sin facturación fiscal — la nota de venta es un documento interno.
7. **Placas bolivianas**: formato ABC-123, único por taller.
8. **Contraseñas**: con expiración a 90 días, bloqueo por 5 intentos fallidos, obligación de cambiar la temporal en el primer ingreso. Nunca se almacenan ni auditan en texto plano.
9. **Soft delete con protección**: al intentar desactivar un cliente, vehículo, empleado, repuesto, etc., el sistema verifica si tiene órdenes o movimientos activos. Si los tiene, bloquea la operación. La excepción es desactivar un taller completo — eso siempre se permite (cierre administrativo), solo con advertencia.
10. **Idempotencia**: registrar un pago contra una nota cuyo saldo ya está cubierto, intentar aprobar una solicitud ya aprobada, o hacer login con Google cuando la cuenta ya existe — todas estas operaciones son seguras de repetir.

---

## 9. Estructura de vistas y componentes del marketplace

### 9.1 Layouts

Tres layouts que envuelven las páginas del marketplace según el contexto del usuario:

- **`marketplace/layouts/guest.blade.php`**: usado para páginas sin autenticación (login/register). Fondo `bg-paper`, contenido centrado vertical y horizontalmente. Favicon con `logo.png`. Carga `app.css` y `app.js` vía Vite.

- **`marketplace/layouts/auth.blade.php`**: extiende `guest` y agrega la navegación completa: nav superior con logo y enlaces, contenedor de toasts Alpine (`toast-container`), barra de saludo "Hola, {nombre}" con enlace al dashboard, alertas de sesión flash (`session('status')`/`session('error')`), y footer.

- **`marketplace/layouts/app.blade.php`**: igual que `auth` pero **sin la barra de usuario** — para páginas públicas (home, búsqueda, perfil de taller). Mismo nav, alertas flash y footer.

- **`layouts/marketplace.blade.php`**: layout mínimo (sin nav, sin footer, sin auth) usado exclusivamente por las páginas de solicitud de alta de taller (formulario multi-paso y seguimiento). Carga solo `app.css`, contenedor angosto `max-w-2xl` centrado.

### 9.2 Páginas del marketplace

**Home (`marketplace/home.blade.php`)** — ruta `/`
Página de aterrizaje. Contiene:
- Hero oscuro (`bg-obsidian`) con logo circular + marca "TallerPro", título, descripción, formulario de búsqueda por texto y botón "Registra tu taller" que lleva al formulario de alta.
- Cuadrícula de categorías (enlazan a búsqueda con filtro por categoría).
- Bloque de estadísticas (`stats-block`): cantidad de talleres, categorías y calificación promedio.
- Separador visual de ancho completo.
- Sección "Talleres cerca de ti" que incluye el partial de experiencia de búsqueda (`search-experience`).

**Búsqueda (`marketplace/search/index.blade.php`)** — ruta `/talleres/buscar`
Página completa de búsqueda. Incluye el partial `search-experience`.

**Partial de búsqueda (`marketplace/partials/search-experience.blade.php`)**
Panel dividido en dos columnas:
- **Panel izquierdo (35%)**: filtros de búsqueda (texto, categoría, radio en km, calificación mínima, "abierto ahora") + resultados en lista. Mientras carga muestra 3 skeletons; si hay error lo muestra; si no hay resultados muestra estado vacío. Cada resultado es una `workshop-card` con nombre, descripción corta, badge abierto/cerrado, categorías, calificación y distancia.
- **Panel derecho (65%)**: mapa Leaflet interactivo con marcadores de los talleres encontrados.

Usa un store Alpine global `$store.search` que maneja: query, categoría, radio, calificación mínima, open-now, resultados, total, error, loading. El método `useMyLocation()` pide geolocalización al navegador.

**Perfil de taller (`marketplace/workshops/show.blade.php`)** — ruta `/talleres/{taller:slug}`
Página pública de un taller individual. Contiene:
- Cabecera: logo del taller (o inicial como fallback), nombre, dirección, calificación con estrellas.
- Botón de favorito (solo visible si el visitante está autenticado).
- Badges de categorías.
- Descripción del taller.
- Información de contacto: teléfono y email.
- Horarios: lista de días con hora de apertura/cierre o "Cerrado".
- Sección de reseñas: formulario para escribir/editar/eliminar la propia reseña (solo autenticado), tarjetas de reseñas de otros usuarios.
- Mapa Leaflet lateral con la ubicación del taller.
- Talleres similares al final.

Usa componentes Alpine: `singleMap()` para el mapa, `resenaForm()` para el formulario de reseña, `favoritoToggle()` para el botón de favorito.

**Dashboard (`marketplace/dashboard/index.blade.php`)** — ruta `/dashboard`
Panel del usuario marketplace autenticado ("Mi cuenta"). Tres secciones:
- **Notificaciones**: lista reactiva Alpine con botón "Marcar leída" por fila.
- **Favoritos**: cuadrícula de `workshop-card` con botón de favorito superpuesto.
- **Mis reseñas**: lista de reseñas del usuario con enlace al taller y formulario para editar.

Usa `notificacionesPanel()` Alpine para el panel de notificaciones.

### 9.3 Solicitudes de alta de taller

**Formulario multi-paso (`solicitudes/crear.blade.php`)**
Cuatro pasos en JavaScript vainilla (sin Alpine):
1. **Datos del solicitante**: nombre, email, teléfono.
2. **Datos del taller**: nombre, dirección, referencia, categoría, comentario.
3. **Ubicación**: botón "Usar mi ubicación actual", mapa Leaflet para pinchar la ubicación, coordenadas en campos ocultos.
4. **Confirmación**: resumen de todo lo ingresado + aviso de privacidad.

Navegación con botones Anterior/Siguiente/Enviar. Validación del lado cliente antes de avanzar de paso.

**Seguimiento (`solicitudes/seguimiento.blade.php`)**
Página pública con token. Muestra:
- Estado actual de la solicitud con badge de color.
- Datos del solicitante y fecha de envío.
- Motivo de rechazo (si aplica).
- Botón de cancelar (solo si está PENDIENTE o EN_REVISIÓN).
- Línea de tiempo del historial de cambios de estado.

### 9.4 Componentes Blade compartidos (design system)

Componentes genéricos reutilizables en `components/`:

| Componente | Función |
|---|---|
| **`alert`** | Mensaje flash con tipo (success/error/warning/info), opcionalmente dismissible con Alpine |
| **`badge`** | Tag pequeño con tipo (neutral/success/warning/danger/info/accent) y tamaño (md/sm) |
| **`button`** | Botón o enlace con variante (primary/ghost/neutral), estado loading con spinner, disabled |
| **`card`** | Contenedor con borde, padding configurable y sombra opcional |
| **`empty-state`** | Placeholder para lista vacía con icono, título, descripción y acción opcional |
| **`input`** | Campo de formulario con label, asterisco de requerido y mensaje de error |
| **`modal`** | Diálogo modal con overlay, cierre por Escape/clic fuera, Alpine-powered |
| **`pagination`** | Navegación Anterior/Siguiente con "Página X de Y" |
| **`select`** | Selector desplegable con label, placeholder y error |
| **`skeleton`** | Bloque gris animado de carga con altura configurable |

### 9.5 Componentes del marketplace

| Componente | Función |
|---|---|
| **`nav`** | Nav superior sticky: logo, enlaces (Buscar, Registra tu taller), botones de auth (Registrarse/Iniciar sesión) o nombre de usuario + logout |
| **`footer`** | Pie con logo, enlaces y copyright |
| **`workshop-card`** | Card de resumen de taller: logo, nombre, dirección, categorías, calificación, distancia opcional |
| **`star-rating`** | Estrellas de calificación: modo lectura o modo edición interactivo con Alpine |
| **`search-filters`** | Filtros de búsqueda: input de texto, selector de categoría, slider de radio, selector de calificación mínima, checkbox "abierto ahora", botón de geolocalización |
| **`map`** | Mapa Leaflet multi-marcador para resultados de búsqueda, con spinner de carga |
| **`toast-container`** | Contenedor fijo de notificaciones toast desde `$store.ui.toasts` |
| **`stats-block`** | Número + label para mostrar estadísticas |
| **`review-card`** | Card de reseña: avatar, nombre, fecha, calificación, comentario |
| **`resena-form`** | Formulario completo de reseña: modo vista (ver reseña propia con botones editar/eliminar) y modo edición (selector de estrellas + textarea + guardar). Gestión completa de estado con Alpine |
| **`favorito-button`** | Botón corazón toggle con Alpine, estado filled/outline según favorito |

### 9.6 Componentes Livewire y Filament

- **`livewire/notificaciones-bell.blade.php`**: componente Livewire montado en el topbar de los paneles Filament (`/erp` y `/admin`). Icono de campana con badge rojo de no leídas. Dropdown con lista de notificaciones y botón "Marcar leída" por fila.

- **`filament/erp/widgets/tenant-switcher.blade.php`**: widget del panel ERP que permite al usuario cambiar entre los talleres a los que tiene acceso. Select desplegable con `wire:model.live`.

### 9.7 Tecnología de frontend

| Tecnología | Dónde se usa |
|---|---|
| **Blade** | Todos los layouts, páginas y componentes — renderizado del lado servidor |
| **Tailwind CSS** | Todos los estilos, usando tokens personalizados del design system (rounded-cards, rounded-icons, colores: obsidian, paper, mist, ember, etc.) |
| **Alpine.js** | Búsqueda (store global `$store.search`), mapa (multi y single), reseñas (crear/editar/eliminar), favoritos (toggle), toasts (store `$store.ui`), notificaciones (panel), star rating interactivo, modal |
| **Leaflet** | Mapa de búsqueda multi-marcador, mapa de perfil de taller, selector de ubicación en formulario de alta |
| **Livewire** | Solo un componente: campana de notificaciones en el header de Filament |
| **Filament** | Paneles `/erp` y `/admin`: tablas, formularios, widgets, notificaciones. El tenant-switcher y la campana de notificaciones son los únicos componentes Blade custom dentro de Filament |
| **JavaScript vainilla** | Formulario multi-paso de solicitud de alta de taller (validación, navegación entre pasos, mapa Leaflet)