<?php

namespace App\Http\Controllers;

use App\Models\ObjetoReservable;
use App\Services\CalendarioService;
use Illuminate\Http\Request;

/** RF26 / HU18: calendario centralizado de todas las reservas activas. */
class CalendarioController extends Controller
{
    public function index()
    {
        return view('calendario.index', [
            'objetos' => ObjetoReservable::orderBy('tipo')->orderBy('nombre')->get(['id', 'nombre', 'tipo']),
        ]);
    }

    /** FullCalendar pide los eventos del rango visible. */
    public function eventos(Request $request, CalendarioService $calendario)
    {
        $datos = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'objeto' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
            'finalizadas' => ['nullable', 'boolean'],
        ]);

        return response()->json($calendario->eventos(
            $datos['start'], $datos['end'], $datos['objeto'] ?? null, $datos['tipo'] ?? null, $request->boolean('finalizadas'),
        ));
    }
}
