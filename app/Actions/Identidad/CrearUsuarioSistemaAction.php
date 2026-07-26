<?php

namespace App\Actions\Identidad;

/**
 * El admin de un taller crea un usuario sistema para un empleado de su propio taller
 * (spec 001). Reutiliza la misma creación de identidad+usuario+credencial que
 * `GenerarCredencialInicialAction` (Super Admin → admin de taller); lo que cambia es
 * el actor y que el usuario queda ligado a un taller vía asignación de rol.
 *
 * NOTA: la asignación de rol + `taller_id` (`asignacion_rol`) queda pendiente de conectar
 * aquí hasta que exista el catálogo de roles de sistema (spec 002-roles-permisos).
 */
class CrearUsuarioSistemaAction
{
    public function __construct(
        private readonly GenerarCredencialInicialAction $generarCredencial,
    ) {}

    public function execute(
        int $tallerId,
        string $username,
        string $nombre,
        ?string $email = null,
        ?string $apellido = null,
    ): array {
        return $this->generarCredencial->execute($username, $nombre, $email, $apellido);
    }
}
