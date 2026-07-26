# Plan — ERP: Catálogo de Servicios

## Tabla

### `servicios_catalogo`
- `taller_id` FK NOT NULL. `codigo` VARCHAR(50), `nombre` NOT NULL, `descripcion` TEXT NULL.
- `precio_base` NUMERIC(12,2) DEFAULT 0, `CHECK (precio_base >= 0)`. `duracion_minutos` INTEGER NULL.
- `activo` BOOLEAN DEFAULT TRUE. Soft delete.
- `UNIQUE (taller_id, codigo)`.

## Implementación

- Modelo `ServicioCatalogo` con `BelongsToTaller`.
- Scope `activos()` usado por los selects de líneas de orden/nota (`011-ordenes-trabajo`, `012-notas-venta`).
- Campo `descripcion` puede usar `awcodes/filament-tiptap-editor` (editor enriquecido) si el negocio lo requiere; no crítico para MVP.
- Filament `ServicioResource`: tabla y formulario simples en panel `/erp`.
