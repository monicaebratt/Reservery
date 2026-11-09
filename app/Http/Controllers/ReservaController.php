<?php

namespace App\Http\Controllers;

use App\Exceptions\NoDisponibleException;
use App\Http\Requests\SolicitudReservaRequest;
use App\Models\ObjetoReservable;
use App\Models\Proposito;
use App\Models\Reserva;
use App\Services\Reservas\ReservaService;
use App\Services\Sustitucion\SustitucionService;
use Illuminate\Http\Request;

/**
 * Reservas en la web. Toda la lógica vive en ReservaService, el mismo que usa la API.
 */
class ReservaController extends Controller
{
    public function __construct(private ReservaService $reservas)
    {
    }

    /** RF09 / HU06: historial con el estado actual, ordenado por fecha. El admin ve todas (HU19). */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $verTodas = $usuario->esAdmin() && $request->query('ver') !== 'mias';

        // Pestañas: próximas (por defecto), anteriores, canceladas o todas
        $pestana = $request->query('pestana', $request->filled('estado') ? 'todas' : 'proximas');
        $buscar = trim((string) $request->query('q'));

        $reservas = Reserva::with(['usuario', 'objetos', 'proposito'])
            ->when(! $verTodas, fn ($q) => $q->where('user_id', $usuario->id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->query('periodo') === 'proximas', fn ($q) => $q->whereDate('fecha', '>=', today()))
            ->when($request->query('periodo') === 'pasadas', fn ($q) => $q->whereDate('fecha', '<', today()))
            ->when($pestana === 'proximas', fn ($q) => $q->whereIn('estado', ['pendiente', 'aprobada'])->whereDate('fecha', '>=', today()))
            ->when($pestana === 'anteriores', fn ($q) => $q->where(fn ($w) => $w->where('estado', 'completada')
                ->orWhere(fn ($x) => $x->whereIn('estado', ['pendiente', 'aprobada'])->whereDate('fecha', '<', today()))))
            ->when($pestana === 'canceladas', fn ($q) => $q->whereIn('estado', ['cancelada', 'rechazada']))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('motivo', 'like', "%{$buscar}%")
                ->orWhereHas('objetos', fn ($o) => $o->where('nombre', 'like', "%{$buscar}%"))
                ->orWhereHas('usuario', fn ($u) => $u->where('name', 'like', "%{$buscar}%"))))
            ->when($pestana === 'proximas',
                fn ($q) => $q->orderBy('fecha')->orderBy('hora_inicio'),
                fn ($q) => $q->orderByDesc('fecha')->orderByDesc('hora_inicio'))
            ->paginate(10)->withQueryString();

        return view('reservas.index', compact('reservas', 'verTodas', 'pestana', 'buscar'));
    }

    /** RNF04: espacio + recursos + propósito en un solo formulario. */
    public function create(Request $request)
    {
        return view('reservas.create', $this->datosFormulario($request) + ['reserva' => new Reserva()]);
    }

    public function store(SolicitudReservaRequest $request)
    {
        try {
            $reserva = $this->reservas->solicitar($request->user(), $request->datos());
        } catch (NoDisponibleException $e) {
            // RF19/RF20: se guardan los datos y se muestran alternativas o la lista de espera
            session(['solicitud_no_disponible' => ['datos' => $request->datos(), 'problemas' => $e->resultado->problemas]]);

            return redirect()->route('reservas.no-disponible');
        }

        $mensaje = match (true) {
            $reserva->reemplazosPropuestos()->exists() => 'Solicitud creada. Por su prioridad desplazaría a otras reservas: les avisamos y les ofrecimos alternativas. El administrador decidirá.',
            $reserva->estado === 'aprobada' => '¡Reserva confirmada!',
            default => 'Solicitud creada. Queda pendiente de aprobación.',
        };

        return redirect()->route('reservas.show', $reserva)->with('ok', $mensaje);
    }

    /** RF19, RF20, RF21, RF22: alternativas equivalentes o lista de espera. */
    public function noDisponible(SustitucionService $sustitucion)
    {
        $solicitud = session('solicitud_no_disponible');
        abort_unless($solicitud, 404);

        $datos = $solicitud['datos'];
        $inicio = microtime(true);
        $alternativas = $sustitucion->buscarAlternativas($datos['objetos'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

        return view('reservas.no-disponible', [
            'datos' => $datos,
            'problemas' => $solicitud['problemas'],
            'alternativas' => $alternativas,
            'pedidos' => ObjetoReservable::whereIn('id', $datos['objetos'])->get(),
            'proposito' => Proposito::find($datos['proposito_id']),
            'milisegundos' => round((microtime(true) - $inicio) * 1000),
        ]);
    }

    public function show(Request $request, Reserva $reserva)
    {
        $this->autorizar($request, $reserva);

        return view('reservas.show', [
            'reserva' => $reserva->load(['usuario', 'objetos', 'proposito', 'historial.responsable',
                'reemplazosPropuestos.afectada.usuario', 'reemplazosRecibidos.prioritaria.proposito']),
        ]);
    }

    public function edit(Request $request, Reserva $reserva)
    {
        $this->autorizar($request, $reserva);

        if (! $reserva->puedeEditarse()) {
            return redirect()->route('reservas.show', $reserva)->with('error', "Una reserva {$reserva->estado} ya no se puede editar.");
        }

        return view('reservas.edit', $this->datosFormulario($request) + ['reserva' => $reserva->load('objetos')]);
    }

    public function update(SolicitudReservaRequest $request, Reserva $reserva)
    {
        $this->reservas->actualizar($reserva, $request->user(), $request->datos());

        return redirect()->route('reservas.show', $reserva)->with('ok', 'Reserva actualizada.');
    }

    public function destroy(Request $request, Reserva $reserva)
    {
        $this->reservas->eliminar($reserva, $request->user());

        return redirect()->route('reservas.index')->with('ok', 'Reserva eliminada.');
    }

    /** RF05 con el patrón State: aprobar, rechazar, cancelar o completar. */
    public function cambiarEstado(Request $request, Reserva $reserva, string $accion)
    {
        $motivo = $request->validate(['motivo' => ['nullable', 'string', 'max:255']])['motivo'] ?? null;
        $reserva = $this->reservas->cambiarEstado($reserva, $accion, $request->user(), $motivo);

        return back()->with('ok', "La reserva #{$reserva->id} ahora está {$reserva->estado}.");
    }

    private function datosFormulario(Request $request): array
    {
        $espacios = ObjetoReservable::espacios()->disponibles()->with('recursosAsociados:id')->orderBy('categoria')->orderBy('nombre')->get();

        return [
            'propositos' => Proposito::orderByDesc('nivel_prioridad')->get(),
            'espacios' => $espacios,
            'recursos' => ObjetoReservable::recursos()->disponibles()->orderBy('categoria')->orderBy('nombre')->get(),
            // Mapa espacio => recursos permitidos (RF14), para que el formulario solo muestre combinaciones válidas
            'paquetes' => $espacios->mapWithKeys(fn ($e) => [$e->id => $e->recursosAsociados->pluck('id')]),
            'preseleccion' => $request->query(),
        ];
    }

    private function autorizar(Request $request, Reserva $reserva): void
    {
        $usuario = $request->user();
        abort_unless($usuario->esAdmin() || $reserva->user_id === $usuario->id, 403, 'Esta reserva no es tuya.');
    }
}
