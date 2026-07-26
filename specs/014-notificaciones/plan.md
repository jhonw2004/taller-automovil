# Plan — Notificaciones

## Tabla

### `notificaciones`
- `usuario_marketplace_id` FK NULL, `usuario_sistema_id` FK NULL — `CHECK` exactamente uno no nulo.
- `taller_id` FK NULL, `orden_trabajo_id` FK NULL. `tipo` VARCHAR(50) NOT NULL. `titulo` VARCHAR(255) NOT NULL. `mensaje` TEXT NOT NULL. `data` JSONB NULL.
- `leida` BOOLEAN DEFAULT FALSE. `leida_at` TIMESTAMPTZ NULL.

## Implementación

- Sistema nativo de notificaciones de Laravel (canal `database`), sin paquete adicional. Clases `App\Notifications\*` por tipo.
- Endpoint `POST /notificaciones/{id}/marcar-leida` valida que el destinatario coincide con el usuario autenticado antes de setear `leida_at`.
- UI: campana de notificaciones en topbar del ERP (dropdown con no leídas) y equivalente en dashboard del usuario marketplace.

### Triggers concretos (qué feature dispara qué notificación y a quién)

| Tipo | Feature origen | Evento | Destinatario | Título ejemplo |
|---|---|---|---|---|
| `solicitud.aprobada` | `004-solicitud-alta-taller` | Solicitud pasa a `APROBADA` o `COMPLETADA` | `usuario_sistema_id` del Super Admin que la procesó | "Solicitud de [taller] aprobada" |
| `solicitud.rechazada` | `004-solicitud-alta-taller` | Solicitud pasa a `RECHAZADA` | `usuario_sistema_id` del Super Admin que la procesó | "Solicitud de [taller] rechazada: [motivo]" |
| `orden.asignada` | `011-ordenes-trabajo` | `empleado_asignado_id` se setea en una orden | `usuario_sistema_id` vinculado al empleado (si tiene acceso) | "Nueva orden OT-2026-001 asignada" |
| `orden.cambio_estado` | `011-ordenes-trabajo` | Orden cambia de estado (cualquier transición) | `usuario_sistema_id` del empleado asignado + admin del taller | "Orden OT-2026-001 cambió a COMPLETADA" |
| `nota.emitida` | `012-notas-venta` | Nota de venta se crea en estado `EMITIDA` | Admin del taller (todos con permiso `notas.ver`) | "Nueva nota NV-2026-001 emitida por [cliente]" |
| `pago.registrado` | `013-pagos` | Pago registrado contra una nota | Admin del taller (todos con permiso `pagos.ver`) | "Pago de Bs 450 registrado en NV-2026-001" |
| `stock.bajo` | `010-inventario-repuestos` | `stock_actual <= stock_minimo` detectado al crear movimiento de salida | Usuarios con permiso `inventario.ver` del taller | "Stock bajo: [repuesto] — disponible: X" |
| `resena.nueva` | `006-resenas-favoritos` | Reseña creada en taller del usuario | Admin del taller (propietario + shop admin) | "Nueva reseña de [usuario] — 4 estrellas" |
| `usuario.creado` | `008-empleados-usuarios-erp` | Usuario sistema creado para un empleado | El nuevo `usuario_sistema_id` | "Tu cuenta en [taller] fue creada. Cambia tu contraseña." |
| `password.expirada` | `001-identidad-autenticacion` | `NOW() > password_expires_at - 7 days` (7 días antes de expirar) | El propio `usuario_sistema_id` | "Tu contraseña expirará en 7 días" |

Cada feature implementa su propio listener/observer y llama a `Notification::send($destinatarios, new ...Notification($data))`. La feature `014` solo define la tabla `notificaciones` y el endpoint de marcar como leída. No define triggers — esos viven en cada feature origen.
