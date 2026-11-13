<?php

namespace App\Http\Controllers;

use App\Http\Requests\ObjetoReservableRequest;
use App\Models\ObjetoReservable;
use App\Models\Organizacion;
use App\Services\InventarioService;
use Illuminate\Http\Request;

// Inventario de espacios y recursos, con imagen, horario y paquetes
class ObjetoReservableController extends Controller
{
    public function __construct(private InventarioService $inventario)
    {
    }

    public function index(Request $request)
    {
        $objetos = ObjetoReservable::query()
            ->withCount('recursosAsociados')
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
            ->when($request->filled('buscar'), fn ($q) => $q->where('nombre', 'like', '%'.$request->buscar.'%'))
            ->orderBy('tipo')->orderBy('nombre')
            ->paginate(12)->withQueryString();

        return view('objetos.index', [
            'objetos' => $objetos,
            'categorias' => ObjetoReservable::query()->distinct()->orderBy('categoria')->pluck('categoria'),
        ]);
    }

    public function create(Request $request)
    {
        $objeto = new ObjetoReservable(['disponible' => true, 'tipo' => $request->query('tipo', 'espacio')]);

        return view('objetos.create', $this->datosFormulario($objeto));
    }

    public function store(ObjetoReservableRequest $request)
    {
        $objeto = $this->inventario->guardar(
            null,
            $request->datos(),
            $request->has('horarios') ? $request->input('horarios') : null,
            $request->input('recursos_asociados', []),
            $request->file('imagen'),
        );

        return redirect()->route('objetos.show', $objeto)->with('ok', "{$objeto->nombre} quedó en el inventario.");
    }

    public function show(ObjetoReservable $objeto)
    {
        $objeto->load(['horarios', 'recursosAsociados', 'espaciosAsociados',
            'bloqueos' => fn ($q) => $q->where('fecha_fin', '>=', now())->orderBy('fecha_inicio'),
            'reservas' => fn ($q) => $q->with(['usuario', 'proposito'])->whereDate('fecha', '>=', today())->activas()->orderBy('fecha')->orderBy('hora_inicio')->take(8),
        ]);

        return view('objetos.show', ['objeto' => $objeto, 'dias' => Organizacion::DIAS]);
    }

    public function edit(ObjetoReservable $objeto)
    {
        return view('objetos.edit', $this->datosFormulario($objeto->load(['horarios', 'recursosAsociados'])));
    }

    public function update(ObjetoReservableRequest $request, ObjetoReservable $objeto)
    {
        $objeto = $this->inventario->guardar(
            $objeto,
            $request->datos(),
            $request->input('horarios', []),
            $request->input('recursos_asociados', []),
            $request->file('imagen'),
        );

        return redirect()->route('objetos.show', $objeto)->with('ok', 'Cambios guardados.');
    }

    public function destroy(ObjetoReservable $objeto)
    {
        $this->inventario->eliminar($objeto);

        return redirect()->route('objetos.index')->with('ok', 'Se eliminó del inventario.');
    }

    private function datosFormulario(ObjetoReservable $objeto): array
    {
        $org = auth()->user()->organizacion;

        // Horario actual, o el de la organización si es nuevo
        $horarios = collect(Organizacion::DIAS)->mapWithKeys(function ($nombre, $dia) use ($objeto, $org) {
            $h = $objeto->exists ? $objeto->horarios->firstWhere('dia_semana', $dia) : null;
            $abre = $objeto->exists ? (bool) $h : $org->operaEl($dia);

            return [$dia => [
                'nombre' => $nombre,
                'abre' => $abre,
                'inicio' => substr($h->hora_inicio ?? $org->hora_apertura, 0, 5),
                'fin' => substr($h->hora_fin ?? $org->hora_cierre, 0, 5),
            ]];
        });

        return [
            'objeto' => $objeto,
            'horarios' => $horarios,
            'recursos' => ObjetoReservable::recursos()->orderBy('categoria')->orderBy('nombre')->get(),
            'categorias' => ObjetoReservable::query()->distinct()->orderBy('categoria')->pluck('categoria'),
        ];
    }
}
