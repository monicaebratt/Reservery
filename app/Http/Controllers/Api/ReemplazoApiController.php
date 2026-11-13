<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReemplazoResource;
use App\Models\Reemplazo;
use App\Services\Prioridad\PrioridadService;
use Illuminate\Http\Request;

/** RF18 por API: el afectado responde la propuesta de reemplazo. */
class ReemplazoApiController extends Controller
{
    public function __construct(private PrioridadService $prioridad)
    {
    }

    /** GET /api/reemplazos — los que afectan mis reservas */
    public function index(Request $request)
    {
        return ReemplazoResource::collection(
            Reemplazo::with(['afectada.objetos', 'prioritaria.proposito'])
                ->whereHas('afectada', fn ($q) => $q->where('user_id', $request->user()->id))
                ->latest()->get()
        );
    }

    /** POST /api/reemplazos/{id}/alternativa {alternativa: índice} */
    public function aceptarAlternativa(Request $request, Reemplazo $reemplazo)
    {
        $indice = (int) $request->validate(['alternativa' => ['required', 'integer', 'min:0']])['alternativa'];

        return new ReemplazoResource($this->prioridad->aceptarAlternativa($reemplazo, $request->user(), $indice)->load(['afectada.objetos', 'prioritaria.proposito']));
    }

    /** POST /api/reemplazos/{id}/ceder — acepta perder el horario sin alternativa */
    public function ceder(Request $request, Reemplazo $reemplazo)
    {
        return new ReemplazoResource($this->prioridad->aceptarSinAlternativa($reemplazo, $request->user()));
    }
}
