<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropositoResource;
use App\Models\Proposito;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Por API. Leer: cualquier miembro. Escribir: administradores
class PropositoApiController extends Controller
{
    // GET /api/propositos — el nivel de prioridad solo aparece para administradores
    public function index()
    {
        return PropositoResource::collection(Proposito::orderByDesc('nivel_prioridad')->get());
    }

    // POST /api/propositos
    public function store(Request $request)
    {
        return (new PropositoResource(Proposito::create($this->validar($request))))->response()->setStatusCode(201);
    }

    // PUT /api/propositos/{id}
    public function update(Request $request, Proposito $proposito)
    {
        $proposito->update($this->validar($request, $proposito));

        return new PropositoResource($proposito);
    }

    // DELETE /api/propositos/{id}
    public function destroy(Proposito $proposito)
    {
        abort_if($proposito->reservas()->exists(), 409, 'Hay reservas con ese propósito; cámbiale el nombre o la prioridad en lugar de borrarlo.');
        $proposito->delete();

        return response()->json(['mensaje' => 'Propósito eliminado.']);
    }

    private function validar(Request $request, ?Proposito $proposito = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80',
                Rule::unique('propositos')->where('organizacion_id', $request->user()->organizacion_id)->ignore($proposito?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'nivel_prioridad' => ['required', 'integer', 'between:1,99'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
