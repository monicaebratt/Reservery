<?php

namespace App\Http\Controllers;

use App\Models\Reemplazo;
use App\Services\Prioridad\PrioridadService;
use Illuminate\Http\Request;

/** RF18 / HU11: el afectado por una reserva prioritaria recibe aviso y alternativas. */
class ReemplazoController extends Controller
{
    public function __construct(private PrioridadService $prioridad)
    {
    }

    public function index(Request $request)
    {
        $usuario = $request->user();

        return view('reemplazos.index', [
            'reemplazos' => Reemplazo::with(['prioritaria.proposito', 'prioritaria.usuario', 'afectada.objetos', 'afectada.proposito', 'afectada.usuario'])
                ->whereHas('afectada', fn ($q) => $q->when(! $usuario->esAdmin(), fn ($q) => $q->where('user_id', $usuario->id)))
                ->latest()->get(),
        ]);
    }

    public function show(Request $request, Reemplazo $reemplazo)
    {
        $reemplazo->load(['prioritaria.proposito', 'prioritaria.objetos', 'afectada.objetos', 'afectada.proposito']);
        abort_unless($request->user()->esAdmin() || $reemplazo->afectada->user_id === $request->user()->id, 403);

        return view('reemplazos.show', compact('reemplazo'));
    }

    public function aceptarAlternativa(Request $request, Reemplazo $reemplazo)
    {
        $indice = (int) $request->validate(['alternativa' => ['required', 'integer', 'min:0']])['alternativa'];
        $this->prioridad->aceptarAlternativa($reemplazo, $request->user(), $indice);

        return redirect()->route('reservas.show', $reemplazo->reserva_afectada_id)
            ->with('ok', 'Aceptaste la alternativa: tu reserva ya quedó con los nuevos espacios o recursos.');
    }

    public function ceder(Request $request, Reemplazo $reemplazo)
    {
        $this->prioridad->aceptarSinAlternativa($reemplazo, $request->user());

        return back()->with('ok', 'Gracias. Cuando el administrador apruebe la reserva prioritaria, la tuya quedará cancelada.');
    }
}
