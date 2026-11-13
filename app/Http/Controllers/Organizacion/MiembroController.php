<?php

namespace App\Http\Controllers\Organizacion;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

// La organización asigna o revoca el rol de administrador en cualquier momento
class MiembroController extends Controller
{
    public function index(Request $request)
    {
        $org = $request->user()->organizacion;

        return view('organizacion.miembros', [
            'org' => $org,
            'miembros' => $org->miembros()->withCount('reservas')->orderByRaw("CASE rol WHEN 'organizacion' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")->orderBy('name')->get(),
            'invitaciones' => $org->invitaciones()->whereNull('aceptada_at')->latest()->get(),
        ]);
    }

    public function cambiarRol(Request $request, User $miembro)
    {
        $request->validate(['rol' => ['required', 'in:admin,usuario']]);
        $org = $request->user()->organizacion;

        $request->rol === 'admin' ? $org->asignarRolAdmin($miembro) : $org->revocarRolAdmin($miembro);

        return back()->with('ok', $request->rol === 'admin'
            ? "{$miembro->name} ahora es administrador."
            : "{$miembro->name} ya no es administrador.");
    }

    public function destroy(Request $request, User $miembro)
    {
        abort_unless($miembro->organizacion_id === $request->user()->organizacion_id, 404);
        abort_if($miembro->esOrganizacion(), 422, 'No se puede quitar la cuenta de la organización.');

        $miembro->tokens()->delete();
        $miembro->update(['organizacion_id' => null, 'rol' => 'usuario']);

        return back()->with('ok', "{$miembro->name} ya no pertenece a la organización.");
    }
}
