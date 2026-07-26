<?php

namespace App\Http\Controllers;

use App\Models\Taller;

class TallerController extends Controller
{
    public function index()
    {
        $talleres = Taller::select('id', 'nombre', 'direccion', 'telefono', 'lat', 'lon')->get();

        return response()->json($talleres);
    }
}
