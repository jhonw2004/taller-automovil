# Tasks — Roles y Permisos

- [ ] Instalar `spatie/laravel-permission`, publicar config, setear `teams=true` y `team_foreign_key=taller_id`.
- [ ] Migración/seed de catálogo global de `permisos` (lista definitiva en `plan.md`).
- [ ] Migración/seed de roles de sistema: `SUPER_ADMIN`, `OWNER`, `SHOP_ADMIN`, `MARKETPLACE_USER`.
- [ ] Seed de roles operativos como roles de sistema fijos: `MECANICO`, `CAJERO`, `RECEPCIONISTA`, `SUPERVISOR`, `VENDEDOR` con `es_sistema = true`.
- [ ] Middleware/listener que setea `setPermissionsTeamId()` desde `taller_activo_id` de sesión en cada request `sistema`.
- [ ] Action `AsignarRolAction` con validaciones de ámbito (taller_id coincide, usuario/rol activos, solo super admin asigna SUPER_ADMIN).
- [ ] Instalar `filament/spatie-laravel-permission-plugin`, formulario de asignación filtrado por taller activo.
- [ ] Tests Pest: asignación rechazada si taller_id no coincide, rechazada si usuario/rol inactivo, solo super admin asigna SUPER_ADMIN, owner no asigna fuera de su taller.
