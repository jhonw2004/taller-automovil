# Tasks — ERP: Catálogo de Servicios

- [ ] Migración `servicios_catalogo` (UNIQUE taller+codigo, CHECK precio_base >= 0).
- [ ] Modelo `ServicioCatalogo` con `BelongsToTaller` y scope `activos()`.
- [ ] Form Request de creación/edición (unicidad de código por taller, precio >= 0).
- [ ] Filament `ServicioResource`.
- [ ] Tests Pest: código único por taller, precio negativo rechazado, servicio inactivo no aparece en selects de líneas nuevas, cambio de precio_base no altera líneas históricas (snapshot).
