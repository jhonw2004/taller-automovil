---
id: 009-catalogo-servicios
status: draft
depends_on: [003-gestion-talleres]
resumen: "Catálogo de servicios ofrecidos por un taller, con precio base snapshot en líneas de orden/nota."
---

# ERP: Catálogo de Servicios

## Propósito

Cada taller define su propio catálogo de servicios (mano de obra) con precio base, usado luego en órdenes de trabajo y notas de venta.

## Actores

- Usuario sistema con permiso `servicios.*`.

## Criterios de aceptación

- Un servicio pertenece a exactamente un taller. `codigo` es único por taller.
- `precio_base` no puede ser negativo.
- Un servicio puede estar activo o inactivo; solo servicios activos pueden seleccionarse al crear líneas nuevas en órdenes o notas.
- Dado un servicio con `precio_base` modificado, cuando ya existen líneas de orden/nota que lo referencian, entonces esas líneas históricas no cambian — cada línea guarda su propio `precio_unitario` como snapshot al momento de crearla.

## Fuera de alcance (MVP)

- Categorías o familias de servicios.
- Precio variable según tipo de vehículo.
