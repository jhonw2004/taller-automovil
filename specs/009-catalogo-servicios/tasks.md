# Tasks — ERP: Catálogo de Servicios

- [x] Migración `servicios_catalogo` (UNIQUE taller+codigo, CHECK precio_base >= 0).
- [x] Modelo `ServicioCatalogo` con `BelongsToTaller` y scope `activos()`.
- [x] Form Request de creación/edición (unicidad de código por taller, precio >= 0).
- [x] Filament `ServicioResource`.
- [x] Tests Pest: código único por taller, precio negativo rechazado, servicio inactivo no aparece en selects de líneas nuevas (via `scopeActivos()` — el consumidor real llega en `011`/`012`), cambio de precio_base no altera líneas históricas (snapshot real de `precio_unitario` pendiente hasta `011-ordenes-trabajo`/`012-notas-venta`, ver `resume.md`).
