<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NoDisponibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudReservaRequest;
use App\Http\Resources\ReservaResource;
use App\Models\Reserva;
use App\Services\Reservas\ReservaService;
use App\Services\Sustitucion\SustitucionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Reservas por API: GET, POST, PUT, PATCH y DELETE. */
class ReservaApiController extends Controller
{
    public function __construct(private ReservaService $reservas, private SustitucionService $sustitucion)
    {
    }

    /** GET /api/reservas?estado=pendiente&todas=1 — RF09 historial */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $todas = $usuario->esAdmin() && $request->boolean('todas');

        return ReservaResource::collection(
            Reserva::with(['usuario', 'objetos', 'proposito'])
                ->when(! $todas, fn ($q) => $q->where('user_id', $usuario->id))
                ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
                ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
                ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
                ->orderByDesc('fecha')->orderByDesc('hora_inicio')
                ->paginate($request->integer('por_pagina', 20))
        );
    }

    /**
     * POST /api/reservas
     * Si no está disponible responde 409 con las alternativas (RF19–RF21) para
     * que el cliente elija una o se anote en la lista de espera (RF22).
     */
    public function store(SolicitudReservaRequest $request)
    {
        try {
            $reserva = $this->reservas->solicitar($request->user(), $request->datos());
        } catch (NoDisponibleException $e) {
            return $this->noDisponible($e, $request->datos());
        }

        return (new ReservaResource($reserva->load('reemplazosPropuestos')))->response()->setStatusCode(201);
    }

    /** GET /api/reservas/{id} */
    public function show(Request $request, Reserva $reserva)
    {
        $this->autorizar($request, $reserva);

        return new ReservaResource($reserva->load(['usuario', 'objetos', 'proposito', 'reemplazosPropuestos']));
    }

    /** PUT /api/reservas/{id} — editar datos (solo si sigue pendiente) */
    public function update(SolicitudReservaRequest $request, Reserva $reserva)
    {
        try {
            $reserva = $this->reservas->actualizar($reserva, $request->user(), $request->datos());
        } catch (NoDisponibleException $e) {
            return $this->noDisponible($e, $request->datos(), [$reserva->id]);
        }

        return new ReservaResource($reserva);
    }

    /** PATCH /api/reservas/{id}/estado {accion: aprobar|rechazar|cancelar|completar, motivo?} — RF05 */
    public function cambiarEstado(Request $request, Reserva $reserva)
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(Reserva::ACCIONES)],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        return new ReservaResource($this->reservas->cambiarEstado($reserva, $datos['accion'], $request->user(), $datos['motivo'] ?? null));
    }

    /** GET /api/reservas/{id}/historial — RNF08 */
    public function historial(Request $request, Reserva $reserva)
    {
        $this->autorizar($request, $reserva);

        return response()->json(['data' => $reserva->historial()->with('responsable:id,name')->get()->map(fn ($h) => [
            'fecha' => $h->created_at->toIso8601String(),
            'accion' => $h->accion,
            'estado_anterior' => $h->estado_anterior,
            'estado_nuevo' => $h->estado_nuevo,
            'detalle' => $h->detalle,
            'responsable' => $h->responsable_nombre,
        ])]);
    }

    /** DELETE /api/reservas/{id} */
    public function destroy(Request $request, Reserva $reserva)
    {
        $this->reservas->eliminar($reserva, $request->user());

        return response()->json(['mensaje' => 'Reserva eliminada.']);
    }

    private function noDisponible(NoDisponibleException $e, array $datos, array $ignorar = [])
    {
        return response()->json([
            'mensaje' => $e->getMessage(),
            'problemas' => $e->resultado->toArray()['problemas'],
            'alternativas' => $this->sustitucion->buscarAlternativas($datos['objetos'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], $ignorar),
            'lista_espera' => 'Para anotarte, envía los mismos datos a POST /api/lista-espera.',
        ], 409);
    }

    private function autorizar(Request $request, Reserva $reserva): void
    {
        abort_unless($request->user()->esAdmin() || $reserva->user_id === $request->user()->id, 403, 'Esta reserva no es tuya.');
    }
}
