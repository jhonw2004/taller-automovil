<?php

namespace App\Actions\Identidad;

/**
 * El admin de un taller crea un usuario sistema para un empleado de su propio taller
 * (spec 001). Reutiliza la misma creación de identidad+usuario+credencial que
 * `GenerarCredencialInicialAction` (Super Admin → admin de taller); lo que cambia es
 * el actor y que el usuario queda ligado a un taller vía asignación de rol.
 *
 * No asigna el rol aquí a propósito (mismo motivo que `GenerarCredencialInicialAction`):
 * el llamador compone con `App\Actions\Roles\AsignarRolAction` pasando el rol operativo
 * que corresponda (CAJERO, MECANICO, etc.) y `$tallerId`.
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
