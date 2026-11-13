<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sustitucion\SustitucionService;
use Illuminate\Http\Request;

// Buscar equivalentes libres para una solicitud
class AlternativaApiController extends Controller
{
    // POST /api/alternativas {objetos[], fecha, hora_inicio, hora_fin}
    public function buscar(Request $request, SustitucionService $sustitucion)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'objetos' => ['required', 'array', 'min:1'],
            'objetos.*' => ['integer'],
        ]);

        $inicio = microtime(true);
        $alternativas = $sustitucion->buscarAlternativas($datos['objetos'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

        return response()->json([
            'data' => $alternativas,
            'milisegundos' => round((microtime(true) - $inicio) * 1000),
        ]);
    }
}
