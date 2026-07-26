---
id: 004-solicitud-alta-taller
status: draft
depends_on: [002-roles-permisos, 003-gestion-talleres]
resumen: "Flujo público de alta de taller (sin cuenta) con máquina de estados y aprobación transaccional que crea el taller."
---

# Solicitud de Alta de Taller

## Propósito

Permitir que un dueño de taller sin cuenta solicite el ingreso de su taller a la plataforma, y que el super admin revise, apruebe o rechace, con trazabilidad completa.

## Actores

- Solicitante (sin cuenta requerida).
- Super admin / usuario con permiso `solicitudes.*`.

## Criterios de aceptación

### Creación de solicitud

- Dado un formulario público completo con nombre, email, teléfono del solicitante y nombre del taller (mínimos obligatorios), cuando se envía, entonces el sistema crea la solicitud en estado `PENDIENTE`, genera un `token_publico` único, y registra el primer evento de historial — todo en una sola transacción.
- El `token_publico` no expone IDs internos y permite consultar el estado sin crear cuenta.

### Máquina de estados

| Estado actual | Evento | Estado resultante |
|---|---|---|
| PENDIENTE | Super admin inicia revisión | EN_REVISION |
| PENDIENTE | Super admin aprueba y completa | COMPLETADA |
| PENDIENTE | Super admin aprueba sin completar | APROBADA |
| PENDIENTE | Super admin rechaza | RECHAZADA |
| PENDIENTE | Solicitante cancela | CANCELADA |
| EN_REVISION | Super admin aprueba y completa | COMPLETADA |
| EN_REVISION | Super admin aprueba sin completar | APROBADA |
| EN_REVISION | Super admin rechaza | RECHAZADA |
| EN_REVISION | Solicitante cancela | CANCELADA |
| APROBADA | Super admin crea taller | COMPLETADA |
| APROBADA | Super admin rechaza por error posterior | RECHAZADA |

- Cualquier transición no listada en la tabla se rechaza como error de negocio.
- Una solicitud `COMPLETADA` siempre tiene `taller_id` asociado; una `RECHAZADA` nunca lo tiene.
- Una solicitud no puede aprobarse dos veces; una `COMPLETADA` no vuelve a `PENDIENTE`; una `CANCELADA` no puede aprobarse.
- El rechazo exige `motivo_rechazo` no vacío.

### Aprobación con creación de taller

- Dado un super admin que aprueba y completa una solicitud, cuando confirma, entonces en una sola transacción: se bloquea la fila de la solicitud (`FOR UPDATE`), se valida su estado, se crea el taller, se vincula `solicitudes_taller.taller_id`, el estado pasa a `COMPLETADA`, se inserta historial y se registra auditoría.
- Si la creación del taller falla, la solicitud permanece sin completar (rollback total, no queda taller huérfano ni solicitud a medias).
- El bloqueo de fila evita que dos aprobaciones concurrentes completen la misma solicitud dos veces.

### Creación directa (sin solicitud)

- Un super admin puede crear un taller sin pasar por solicitud; ese taller no queda vinculado a ninguna `solicitud_taller`.

### Historial

- Cada cambio de estado genera un registro append-only en `solicitudes_taller_historial` con estado anterior, estado nuevo, observación y usuario sistema (puede ser NULL si el evento inicial fue público). El historial nunca se edita ni se borra.

### Detección de duplicados (opcional, no bloqueante)

- Si existe un taller activo con el mismo `osm_id`, o una solicitud/taller cercano con mismo nombre o teléfono, el sistema puede marcarlo como posible duplicado; esto es informativo y no bloquea automáticamente la solicitud salvo que el negocio lo decida explícitamente.

## Fuera de alcance (MVP)

- Notificación automática al solicitante por email (revisar si `014-notificaciones` lo cubre).
- Edición de la solicitud por el propio solicitante tras enviarla.
