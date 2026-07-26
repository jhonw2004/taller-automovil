# Tasks — Roles y Permisos

- [x] ~~Instalar `spatie/laravel-permission`...~~ **Obsoleto**: removido por completo (ver `plan.md`, decisión 2026-07-26). Implementación real: migraciones propias `roles`/`permisos`/`roles_permisos`/`asignaciones_rol`.
- [x] Migración/seed de catálogo global de `permisos` (lista definitiva en `plan.md`). `PermisoSeeder`, 64 permisos.
- [x] Migración/seed de roles de sistema: `SUPER_ADMIN`, `OWNER`, `SHOP_ADMIN`, `MARKETPLACE_USER`. `RolSistemaSeeder`.
- [x] Seed de roles operativos como roles de sistema fijos: `MECANICO`, `CAJERO`, `RECEPCIONISTA`, `SUPERVISOR`, `VENDEDOR` con `es_sistema = true`. 9 roles de sistema en total, `RolSistemaSeeder`.
- [x] ~~Middleware/listener que setea `setPermissionsTeamId()`~~ **Obsoleto**, ver nota arriba. El middleware real (`SetTallerActivo`, spec `017`) solo escribe `session('taller_activo_id')`.
- [x] Action `AsignarRolAction` con validaciones de ámbito (taller_id coincide, usuario/rol activos, solo super admin asigna SUPER_ADMIN). `app/Actions/Roles/AsignarRolAction.php`.
- [x] ~~Instalar `filament/spatie-laravel-permission-plugin`~~ **Reemplazado** por Resources propios: `PermisoResource`, `RolResource` (admin, global), `RolResource` (erp, por taller), `AsignacionRolResource` (erp, formulario de asignación sobre `AsignarRolAction`).
- [x] Tests Pest: asignación rechazada si taller_id no coincide, rechazada si usuario/rol inactivo, solo super admin asigna SUPER_ADMIN, owner no asigna fuera de su taller. 21 tests en `tests/Feature/Roles/`.
