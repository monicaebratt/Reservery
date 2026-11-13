<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarioService;
use Illuminate\Http\Request;

// Por API
class CalendarioApiController extends Controller
{
    // GET /api/calendario?desde=2026-11-01&hasta=2026-11-30&objeto=3&tipo=espacio
    public function index(Request $request, CalendarioService $calendario)
    {
        $datos = $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'objeto' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
        ]);

        return response()->json(['data' => $calendario->eventos($datos['desde'], $datos['hasta'].' 23:59:59', $datos['objeto'] ?? null, $datos['tipo'] ?? null)]);
    }
}
