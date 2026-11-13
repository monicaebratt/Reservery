<?php

namespace App\Http\Controllers;

use App\Models\Bloqueo;
use App\Models\ObjetoReservable;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

// Inhabilitar fechas u horarios por mantenimiento o eventos
class BloqueoController extends Controller
{
    public function __construct(private InventarioService $inventario)
    {
    }

    public function index(Request $request)
    {
        return view('bloqueos.index', [
            'bloqueos' => Bloqueo::with(['objeto', 'creador'])
                ->when($request->query('ver') !== 'todos', fn ($q) => $q->where('fecha_fin', '>=', now()))
                ->orderBy('fecha_inicio')->get(),
            'objetos' => ObjetoReservable::orderBy('tipo')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $org = $request->user()->organizacion_id;
        $datos = $request->validate([
            'objetos' => ['required', 'array', 'min:1'],
            'objetos.*' => [Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $org)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:255'],
            'cancelar_afectadas' => ['nullable', 'boolean'],
        ], ['objetos.required' => 'Elige al menos un espacio o recurso.']);

        $inicio = Carbon::parse($datos['fecha_inicio']);
        $fin = Carbon::parse($datos['fecha_fin']);
        $afectadas = $this->inventario->reservasAfectadas($datos['objetos'], $inicio, $fin);

        // Primero se avisa cuántas reservas quedarían dentro, para decidir
        if ($afectadas->isNotEmpty() && ! $request->has('cancelar_afectadas')) {
            return back()->withInput()->with('afectadas', $afectadas->map(fn ($r) => "#{$r->id} · {$r->usuario->name} · {$r->fecha->format('d/m/Y')} {$r->horario}")->all());
        }

        $r = $this->inventario->bloquear($datos['objetos'], $inicio, $fin, $datos['motivo'], $request->user(), $request->boolean('cancelar_afectadas'));

        return redirect()->route('bloqueos.index')->with('ok', count($r['bloqueos']).' bloqueo(s) creado(s).'
            .($r['canceladas'] ? " Se cancelaron {$r['canceladas']} reserva(s) y se notificó a sus dueños." : ''));
    }

    public function destroy(Bloqueo $bloqueo)
    {
        $this->inventario->desbloquear($bloqueo);

        return back()->with('ok', 'Bloqueo eliminado: el horario vuelve a estar disponible.');
    }
}
