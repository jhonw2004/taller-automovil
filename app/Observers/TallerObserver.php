<?php

namespace App\Observers;

use App\Models\AuditoriaTaller;
use App\Models\Taller;
use Illuminate\Support\Facades\Auth;

/**
 * `auditoria_talleres` (015-plan.md/spec.md): campos sensibles nombre/telefono/email/direccion/
 * estado/visible_en_mapa/lat/lon/geom/osm_id, con valores antiguo y nuevo. Observer propio (no
 * la `RegistrarEventoAuditoriaAction` genérica de `auditoria_eventos`) porque esta tabla necesita
 * capturar `geom_old`/`geom_new` con `App\Casts\GeometryCast`, algo que un log genérico
 * `evento`/`datos` JSON no modela bien.
 *
 * `geom_new`/`geom_old` se arman desde `lat`/`lon`, nunca desde `$taller->geom` directo: una
 * instancia recién guardada sin `fresh()` no puede leer su propio `geom` (el cast recibe el
 * `Expression` crudo antes de que Postgres lo resuelva a WKB — mismo hallazgo documentado en
 * `CambiarVisibilidadTallerAction`), pero `lat`/`lon` sí son fiables en memoria porque son floats
 * planos, no una columna derivada/casteada a `Expression`.
 */
class TallerObserver
{
    private const CAMPOS_SENSIBLES_TEXTO = ['nombre', 'telefono', 'email', 'direccion', 'estado', 'visible_en_mapa', 'osm_id'];

    public function created(Taller $taller): void
    {
        $datos = ['taller_id' => $taller->id, 'operacion' => 'INSERT', 'datos_new' => []];

        foreach (self::CAMPOS_SENSIBLES_TEXTO as $campo) {
            $datos['datos_new'][$campo] = $taller->{$campo};
        }

        $this->registrar($taller, $datos, latNew: $taller->lat, lonNew: $taller->lon);
    }

    public function updated(Taller $taller): void
    {
        $camposCambiados = array_intersect(self::CAMPOS_SENSIBLES_TEXTO, array_keys($taller->getChanges()));
        $latLonCambiaron = $taller->wasChanged('lat') || $taller->wasChanged('lon');

        if ($camposCambiados === [] && ! $latLonCambiaron) {
            return;
        }

        $datosOld = [];
        $datosNew = [];

        foreach ($camposCambiados as $campo) {
            $datosOld[$campo] = $taller->getOriginal($campo);
            $datosNew[$campo] = $taller->{$campo};
        }

        $datos = ['taller_id' => $taller->id, 'operacion' => 'UPDATE'];

        if ($datosOld !== []) {
            $datos['datos_old'] = $datosOld;
            $datos['datos_new'] = $datosNew;
        }

        $this->registrar(
            $taller,
            $datos,
            latOld: $taller->getOriginal('lat'),
            lonOld: $taller->getOriginal('lon'),
            latNew: $taller->lat,
            lonNew: $taller->lon,
        );
    }

    public function deleted(Taller $taller): void
    {
        $datosOld = [];

        foreach (self::CAMPOS_SENSIBLES_TEXTO as $campo) {
            $datosOld[$campo] = $taller->{$campo};
        }

        $this->registrar(
            $taller,
            ['taller_id' => $taller->id, 'operacion' => 'DELETE', 'datos_old' => $datosOld],
            latOld: $taller->lat,
            lonOld: $taller->lon,
        );
    }

    private function registrar(
        Taller $taller,
        array $datos,
        ?float $latOld = null,
        ?float $lonOld = null,
        ?float $latNew = null,
        ?float $lonNew = null,
    ): void {
        $datos['usuario_sistema_id'] = Auth::guard('sistema')->id();
        $datos['lat_old'] = $latOld;
        $datos['lat_new'] = $latNew;
        $datos['lon_old'] = $lonOld;
        $datos['lon_new'] = $lonNew;

        if ($latOld !== null && $lonOld !== null) {
            $datos['geom_old'] = ['lat' => $latOld, 'lon' => $lonOld];
        }

        if ($latNew !== null && $lonNew !== null) {
            $datos['geom_new'] = ['lat' => $latNew, 'lon' => $lonNew];
        }

        AuditoriaTaller::create($datos);
    }
}
