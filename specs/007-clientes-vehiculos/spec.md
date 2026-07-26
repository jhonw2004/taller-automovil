---
id: 007-clientes-vehiculos
status: draft
depends_on: [002-roles-permisos, 003-gestion-talleres]
resumen: "Registro administrativo de clientes y sus vehículos dentro de un taller, sin relación con usuarios marketplace/sistema."
---

# ERP: Clientes y Vehículos

## Propósito

Permitir que cada taller registre y administre sus propios clientes (personas naturales o jurídicas) y los vehículos asociados, como base para órdenes de trabajo y notas de venta.

## Actores

- Usuario sistema con permiso `clientes.*` / `vehiculos.*` dentro de un taller.

## Criterios de aceptación

### Clientes

- Un cliente pertenece a exactamente un taller; no es un usuario marketplace ni un usuario sistema.
- `codigo` de cliente es único por taller. Si se registra `nit_ci`, también es único por taller.
- `nombre` es obligatorio; `tipo_persona` debe ser `NATURAL` o `JURIDICA`; `email`, si se registra, debe tener formato válido; `teléfono` es opcional.
- Un cliente puede estar `activo = TRUE/FALSE`.
- Dado un cliente con órdenes o notas asociadas, cuando se intenta eliminarlo, entonces no se borra físicamente — se marca `activo = FALSE` o `deleted_at` (soft delete).
- Un cliente inactivo no puede seleccionarse para crear una orden o nota nueva (ver `011-ordenes-trabajo`, `012-notas-venta`).

### Vehículos

- Un vehículo pertenece a un cliente y, por consistencia, al mismo taller que ese cliente.
- `placa` es única por taller, se normaliza sin guion en base de datos (`ABC123`) y se muestra con guion (`ABC-123`); formato `^[A-Z]{3}[0-9]{3}$`.
- `kilometraje` no puede ser negativo; `anio` debe ser razonable (entre 1900 y el año actual + 1).
- Dado un vehículo con órdenes asociadas, cuando se intenta eliminarlo, entonces no se borra físicamente — se marca `activo = FALSE` o `deleted_at`.
- Un vehículo inactivo no puede seleccionarse para crear una orden nueva.

## Fuera de alcance (MVP)

- Historial de propietarios anteriores de un vehículo (transferencia entre clientes).
- Fotos de vehículo.
