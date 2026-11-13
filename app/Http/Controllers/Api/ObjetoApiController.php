<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ObjetoReservableRequest;
use App\Http\Resources\ObjetoResource;
use App\Models\ObjetoReservable;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Por API
class ObjetoApiController extends Controller
{
    public function __construct(private InventarioService $inventario)
    {
    }

    // GET /api/objetos?tipo=espacio&categoria=Auditorio
    public function index(Request $request)
    {
        return ObjetoResource::collection(
            ObjetoReservable::with(['horarios', 'recursosAsociados'])
                ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
                ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
                ->orderBy('tipo')->orderBy('nombre')->get()
        );
    }

    // GET /api/objetos/{id}
    public function show(ObjetoReservable $objeto)
    {
        return new ObjetoResource($objeto->load(['horarios', 'recursosAsociados']));
    }

    // POST /api/objetos (multipart si trae imagen)
    public function store(ObjetoReservableRequest $request)
    {
        $objeto = $this->inventario->guardar(null, $request->datos(),
            $request->has('horarios') ? $request->input('horarios') : null,
            $request->input('recursos_asociados', []), $request->file('imagen'));

        return (new ObjetoResource($objeto))->response()->setStatusCode(201);
    }

    // PUT /api/objetos/{id}
    public function update(ObjetoReservableRequest $request, ObjetoReservable $objeto)
    {
        $objeto = $this->inventario->guardar($objeto, $request->datos(),
            $request->has('horarios') ? $request->input('horarios') : null,
            $request->has('recursos_asociados') ? $request->input('recursos_asociados', []) : null,
            $request->file('imagen'));

        return new ObjetoResource($objeto);
    }

    // DELETE /api/objetos/{id}
    public function destroy(ObjetoReservable $objeto)
    {
        $this->inventario->eliminar($objeto);

        return response()->json(['mensaje' => 'Se eliminó del inventario.']);
    }

    // PUT /api/objetos/{id}/horarios {horarios: {1: {abre, inicio, fin}, ...}}
    public function horarios(Request $request, ObjetoReservable $objeto)
    {
        $request->validate([
            'horarios' => ['required', 'array'],
            'horarios.*.abre' => ['required', 'boolean'],
            'horarios.*.inicio' => ['required_if_accepted:horarios.*.abre', 'nullable', 'date_format:H:i'],
            'horarios.*.fin' => ['required_if_accepted:horarios.*.abre', 'nullable', 'date_format:H:i'],
        ]);
        $this->inventario->guardarHorarios($objeto, $request->input('horarios'));

        return new ObjetoResource($objeto->load(['horarios', 'recursosAsociados']));
    }

    // PUT /api/objetos/{id}/recursos {recursos: [ids]}
    public function recursos(Request $request, ObjetoReservable $objeto)
    {
        abort_unless($objeto->esEspacio(), 422, 'Solo los espacios tienen recursos asociados.');
        $request->validate([
            'recursos' => ['present', 'array'],
            'recursos.*' => [Rule::exists('objetos_reservables', 'id')->where('organizacion_id', $request->user()->organizacion_id)->where('tipo', 'recurso')],
        ]);
        $objeto->recursosAsociados()->sync($request->input('recursos'));

        return new ObjetoResource($objeto->load(['horarios', 'recursosAsociados']));
    }
}
