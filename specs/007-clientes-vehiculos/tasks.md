# Tasks — ERP: Clientes y Vehículos

- [x] Trait `App\Traits\BelongsToTaller` (global scope + auto-fill `taller_id`), reutilizable por todas las features ERP siguientes.
- [x] Migraciones `clientes` y `vehiculos` con constraints e índices únicos parciales.
- [x] Modelos `Cliente`, `Vehiculo` con `BelongsToTaller` y soft deletes.
- [x] Accessor/mutator de placa (normalización sin guion en BD, presentación con guion).
- [x] Form Requests `ClienteRequest`, `VehiculoRequest` con validaciones de unicidad por taller y regex de placa.
- [x] Filament `ClienteResource` (formulario dinámico natural/jurídica) y `VehiculoResource` (máscara de placa).
- [x] Regla de baja: desactivar en vez de borrar cuando hay órdenes/notas asociadas.
- [x] Tests Pest: aislamiento por taller (un taller no ve clientes/vehículos de otro), unicidad código/nit_ci/placa por taller, regex de placa, cliente/vehículo inactivo no seleccionable en orden nueva.
