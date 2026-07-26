# Plan — ERP: Clientes y Vehículos

## Tablas

### `clientes`
- `taller_id` FK NOT NULL. `codigo` VARCHAR(50), `tipo_persona` VARCHAR(20) DEFAULT 'NATURAL', `CHECK (tipo_persona IN ('NATURAL','JURIDICA'))`.
- `nombre` NOT NULL, `apellido`, `razon_social`, `nit_ci`, `telefono`, `email`, `direccion`, `observaciones`.
- `activo` BOOLEAN DEFAULT TRUE. Soft delete.
- `UNIQUE (taller_id, codigo)`; índice único parcial `UNIQUE (taller_id, nit_ci) WHERE nit_ci IS NOT NULL`.

### `vehiculos`
- `taller_id`, `cliente_id` FK NOT NULL. `placa` VARCHAR(10) NOT NULL, `CHECK (placa ~ '^[A-Z]{3}[0-9]{3}$')`.
- `marca`, `modelo`, `anio` SMALLINT `CHECK (anio BETWEEN 1900 AND EXTRACT(YEAR FROM CURRENT_DATE) + 1)`, `color`, `vin`.
- `tipo_vehiculo` VARCHAR(30) DEFAULT 'AUTO', `CHECK (tipo_vehiculo IN ('AUTO','MOTO','CAMIONETA','CAMION','OTRO'))`.
- `kilometraje` INTEGER DEFAULT 0, `CHECK (kilometraje >= 0)`. `activo` BOOLEAN DEFAULT TRUE. Soft delete.
- `UNIQUE (taller_id, placa)`.

## Implementación

- Modelos `Cliente`, `Vehiculo` usan trait `App\Traits\BelongsToTaller` (global scope `taller_id`, auto-fill al crear desde `session('taller_activo_id')`).
- Mutator/accessor de placa: normaliza a mayúsculas sin guion al guardar (`ABC123`), accessor de presentación agrega guion (`ABC-123`).
- Form Requests: `ClienteRequest` (unicidad de código y nit_ci por taller), `VehiculoRequest` (regex de placa, unicidad por taller, cliente pertenece al mismo taller).
- Filament: `ClienteResource` (tabla con código, nombre, NIT/CI, teléfono, email, count de vehículos, estado; formulario cambia campos según `tipo_persona`), `VehiculoResource` (input de placa con máscara/validación en tiempo real, select de cliente).
