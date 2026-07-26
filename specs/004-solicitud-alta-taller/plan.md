# Plan — Solicitud de Alta de Taller

## Tablas

### `solicitudes_taller`
- `token_publico` UUID UNIQUE NOT NULL. `estado` VARCHAR(20) DEFAULT 'PENDIENTE'.
- `solicitante_nombre`, `solicitante_email`, `solicitante_telefono` NOT NULL. `taller_nombre` NOT NULL. `taller_direccion`, `referencia` opcionales.
- `categoria_principal_id` FK NULL -> `categorias.id`. `lat`/`lon` DOUBLE PRECISION NULL, `geom` GEOMETRY(Point,4326) NULL, `osm_id` VARCHAR NULL.
- `comentario`, `motivo_rechazo` TEXT NULL. `gestionada_por_usuario_sistema_id` FK NULL.
- `enviada_at`, `revisada_at`, `aprobada_at`, `rechazada_at`, `completada_at` TIMESTAMPTZ NULL.
- `taller_id` FK NULL -> `talleres.id`, UNIQUE.
- `CHECK (estado IN ('PENDIENTE','EN_REVISION','APROBADA','RECHAZADA','COMPLETADA','CANCELADA'))`.
- `CHECK (estado <> 'COMPLETADA' OR taller_id IS NOT NULL)`. `CHECK (estado <> 'RECHAZADA' OR taller_id IS NULL)`.
- `CHECK (lat BETWEEN -90 AND 90)`, `CHECK (lon BETWEEN -180 AND 180)`. Índice `GIST (geom)`.

### `solicitudes_taller_historial`
- `solicitud_taller_id` FK NOT NULL. `estado_anterior` VARCHAR NULL, `estado_nuevo` VARCHAR NOT NULL.
- `usuario_sistema_id` FK NULL. `observacion` TEXT NULL. Append-only.

## Implementación

- Modelo `SolicitudTaller` usa `App\Traits\HasGeolocation` (mismo cast que `Taller`, ver `003-gestion-talleres`).
- Máquina de estados implementada como Action por transición (`IniciarRevisionSolicitud`, `AprobarYCompletarSolicitud`, `AprobarSinCompletarSolicitud`, `RechazarSolicitud`, `CancelarSolicitud`), cada una valida la tabla de transiciones antes de ejecutar.
- `AprobarYCompletarSolicitud`: `DB::transaction()` con `SolicitudTaller::whereKey($id)->lockForUpdate()`, crea `Taller` (reutiliza validaciones de `003-gestion-talleres`), setea `taller_id`, estado `COMPLETADA`, inserta historial, dispara evento de auditoría (`015-auditoria`).
- Endpoint público: formulario multi-paso (datos solicitante → datos taller → mapa → confirmación), ver detalle de pantallas en `plan.md` de UI si se documenta aparte; página de seguimiento por `token_publico` (stepper de estado).
- Panel Super Admin (`/admin`): listado de solicitudes con filtros rápidos (Pendientes, En Revisión), detalle con mapa, botones "Iniciar Revisión", "Aprobar y Crear Taller" (modal pre-cargado con datos + mapa editable), "Rechazar" (modal con textarea obligatorio).
