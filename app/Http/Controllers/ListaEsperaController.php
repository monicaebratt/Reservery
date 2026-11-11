<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudReservaRequest;
use App\Models\ListaEspera;
use App\Services\ListaEspera\ListaEsperaService;
use Illuminate\Http\Request;

/** RF22 / HU14: lista de espera por orden de llegada. */
class ListaEsperaController extends Controller
{
    public function __construct(private ListaEsperaService $listaEspera)
    {
    }

    public function index(Request $request)
    {
        $usuario = $request->user();

        return view('lista-espera.index', [
            'entradas' => ListaEspera::with(['objeto', 'proposito', 'reserva', 'usuario'])
                ->when(! $usuario->esAdmin() || $request->query('ver') === 'mias', fn ($q) => $q->where('user_id', $usuario->id))
                ->orderByRaw("CASE estado WHEN 'notificada' THEN 0 WHEN 'esperando' THEN 1 WHEN 'atendida' THEN 2 ELSE 3 END")
                ->orderBy('fecha')->orderBy('created_at')
                ->get(),
        ]);
    }

    public function store(SolicitudReservaRequest $request)
    {
        $entrada = $this->listaEspera->inscribir($request->user(), $request->datos());
        session()->forget('solicitud_no_disponible');

        return redirect()->route('lista-espera.index')
            ->with('ok', "Quedaste en la posición {$entrada->posicion} de la lista de espera. Te avisaremos si se libera.");
    }

    public function convertir(Request $request, ListaEspera $entrada)
    {
        $reserva = $this->listaEspera->convertir($entrada, $request->user());

        return redirect()->route('reservas.show', $reserva)->with('ok', '¡Listo! Tu turno de la lista de espera ahora es una reserva.');
    }

    public function destroy(Request $request, ListaEspera $entrada)
    {
        $this->listaEspera->cancelar($entrada, $request->user());

        return back()->with('ok', 'Saliste de la lista de espera.');
    }
}
