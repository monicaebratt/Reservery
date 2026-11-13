<?php

namespace App\Http\Controllers;

use App\Models\Proposito;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Propósitos y reglas de prioridad que configura el administrador
class PropositoController extends Controller
{
    public function index()
    {
        return view('propositos.index', [
            'propositos' => Proposito::withCount('reservas')->orderByDesc('nivel_prioridad')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Proposito::create($this->validar($request));

        return back()->with('ok', 'Propósito creado.');
    }

    public function update(Request $request, Proposito $proposito)
    {
        $proposito->update($this->validar($request, $proposito));

        return back()->with('ok', "Se actualizó la prioridad de {$proposito->nombre}.");
    }

    // Sube o baja un propósito un puesto intercambiando niveles con su vecino
    public function mover(Proposito $proposito, string $direccion)
    {
        $vecino = Proposito::query()
            ->where('nivel_prioridad', $direccion === 'subir' ? '>' : '<', $proposito->nivel_prioridad)
            ->orderBy('nivel_prioridad', $direccion === 'subir' ? 'asc' : 'desc')
            ->first();

        if ($vecino) {
            [$a, $b] = [$proposito->nivel_prioridad, $vecino->nivel_prioridad];
            $proposito->update(['nivel_prioridad' => $b]);
            $vecino->update(['nivel_prioridad' => $a]);
        }

        return back();
    }

    public function destroy(Proposito $proposito)
    {
        if ($proposito->reservas()->exists()) {
            return back()->with('error', 'No se puede eliminar: ya hay reservas con ese propósito. Puedes cambiarle el nombre o la prioridad.');
        }

        $proposito->delete();

        return back()->with('ok', 'Propósito eliminado.');
    }

    private function validar(Request $request, ?Proposito $proposito = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:80',
                Rule::unique('propositos')->where('organizacion_id', $request->user()->organizacion_id)->ignore($proposito?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'nivel_prioridad' => ['required', 'integer', 'between:1,99'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [], ['nivel_prioridad' => 'nivel de prioridad']);
    }
}
