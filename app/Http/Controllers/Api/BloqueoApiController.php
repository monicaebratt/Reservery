<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BloqueoResource;
use App\Models\Bloqueo;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** RF07 por API. */
class BloqueoApiController extends Controller
{
    public function __construct(private InventarioService $inventario)
    {
    }

    /** GET /api/bloqueos */
    public function index()
    {
        return BloqueoResource::collection(Bloqueo::with(['objeto', 'creador'])->where('fecha_fin', '>=', now())->orderBy('fecha_inicio')->get());
    }

    /**
     * POST /api/bloqueos {objetos[], fecha_inicio, fecha_fin, motivo, cancelar_afectadas?}
     * Si hay reservas dentro y no se envía cancelar_afectadas, responde 409 con la lista.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'objetos' => ['required', 'array', 'min:1'],
            'objetos.*' => [Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $request->user()->organizacion_id)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:255'],
            'cancelar_afectadas' => ['nullable', 'boolean'],
        ]);

        $inicio = Carbon::parse($datos['fecha_inicio']);
        $fin = Carbon::parse($datos['fecha_fin']);
        $afectadas = $this->inventario->reservasAfectadas($datos['objetos'], $inicio, $fin);

        if ($afectadas->isNotEmpty() && ! $request->has('cancelar_afectadas')) {
            return response()->json([
                'mensaje' => "Hay {$afectadas->count()} reserva(s) activas en ese rango. Reenvía con cancelar_afectadas=true para cancelarlas, o false para bloquear sin tocarlas.",
                'reservas_afectadas' => $afectadas->map(fn ($r) => ['id' => $r->id, 'fecha' => $r->fecha->toDateString(), 'horario' => $r->horario, 'usuario' => $r->usuario->name]),
            ], 409);
        }

        $r = $this->inventario->bloquear($datos['objetos'], $inicio, $fin, $datos['motivo'], $request->user(), $request->boolean('cancelar_afectadas'));

        return response()->json([
            'data' => BloqueoResource::collection(collect($r['bloqueos'])->each->load('objeto')),
            'reservas_canceladas' => $r['canceladas'],
        ], 201);
    }

    /** DELETE /api/bloqueos/{id} */
    public function destroy(Bloqueo $bloqueo)
    {
        $this->inventario->desbloquear($bloqueo);

        return response()->json(['mensaje' => 'Bloqueo eliminado.']);
    }
}
