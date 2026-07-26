---
id: 014-notificaciones
status: draft
depends_on: [001-identidad-autenticacion, 010-inventario-repuestos, 011-ordenes-trabajo, 012-notas-venta]
resumen: "Notificaciones in-app dirigidas a un único destinatario (marketplace o sistema), disparadas por eventos del negocio."
---

# Notificaciones

## Propósito

Avisar a usuarios marketplace o sistema sobre eventos relevantes (solicitud resuelta, orden asignada/cambiada, pago registrado, stock bajo, nueva reseña, usuario creado).

## Actores

- Usuario marketplace, usuario sistema (como destinatarios).

## Criterios de aceptación

- Toda notificación tiene exactamente un destinatario: `usuario_marketplace_id` o `usuario_sistema_id`, nunca ambos ni ninguno.
- Puede referenciar opcionalmente un taller y/o una orden de trabajo.
- Tipos esperados en el MVP: solicitud de taller aprobada/rechazada, orden asignada, orden cambiada de estado, pago registrado, nota de venta emitida, stock bajo, nueva reseña recibida, usuario creado, contraseña por vencer/restablecida.
- Una notificación inicia como no leída (`leida = FALSE`); el destinatario puede marcarla como leída, lo que registra `leida_at`.
- Un usuario solo puede marcar como leídas sus propias notificaciones.
- Canal del MVP: solo notificaciones dentro del sistema (in-app). Email/SMS/WhatsApp quedan fuera de alcance.

## Fuera de alcance (MVP)

- Envío por email, SMS o WhatsApp (queda como extensión futura del mismo modelo).
- Preferencias de notificación configurables por usuario.
