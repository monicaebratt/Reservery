<?php

namespace App\Http\Controllers;

use App\Models\Organizacion;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;

// Quien tiene cuenta pero aún no pertenece a una organización habilitada
class BienvenidaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();

        if ($usuario->tieneOrganizacionActiva()) {
            return redirect()->route('dashboard');
        }

        if ($usuario->organizacion_id && $usuario->esOrganizacion()) {
            return redirect()->route('organizacion.edit');
        }

        return view('bienvenida', [
            'usuario' => $usuario->load('organizacion'),
            'tipos' => Organizacion::TIPOS,
        ]);
    }

    public function unirse(Request $request, OrganizacionService $organizaciones)
    {
        $request->validate(['codigo' => ['required', 'string', 'max:12']]);

        $org = $organizaciones->unirseConCodigo($request->user(), $request->codigo);

        return redirect()->route('dashboard')->with('ok', "Te uniste a {$org->nombre}.");
    }
}
