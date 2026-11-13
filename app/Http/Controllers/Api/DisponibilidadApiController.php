<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Reservas\ReservaService;
use Illuminate\Http\Request;

// Por API
class DisponibilidadApiController extends Controller
{
    // GET /api/disponibilidad?fecha=2026-11-20&tipo=espacio&objetos[]=1
    public function agenda(Request $request, DisponibilidadService $disponibilidad)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
            'objetos' => ['nullable', 'array'],
        ]);

        return response()->json([
            'fecha' => $datos['fecha'],
            'data' => $disponibilidad->agenda($datos['objetos'] ?? null, $datos['fecha'], $datos['tipo'] ?? null, $request->user()->esAdmin()),
        ]);
    }

    // POST /api/disponibilidad/verificar {objetos[], fecha, hora_inicio, hora_fin}
    public function verificar(Request $request, DisponibilidadService $disponibilidad, ReservaService $reservas)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'objetos' => ['required', 'array', 'min:1'],
        ]);

        $objetos = $reservas->cargarObjetos($datos['objetos']);

        return response()->json($disponibilidad->verificar($objetos, $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'])->toArray());
    }
}
