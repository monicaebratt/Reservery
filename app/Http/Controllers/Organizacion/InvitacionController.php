<?php

namespace App\Http\Controllers\Organizacion;

use App\Http\Controllers\Controller;
use App\Models\Invitacion;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;

/** RF01.2: autorizar correos con el rol que tendrán. */
class InvitacionController extends Controller
{
    public function store(Request $request, OrganizacionService $organizaciones)
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'rol' => ['required', 'in:admin,usuario'],
        ]);

        $resultado = $organizaciones->invitar($request->user()->organizacion, $datos['email'], $datos['rol'], $request->user());
        $rol = $datos['rol'] === 'admin' ? 'administrador' : 'usuario';

        return back()->with('ok', match ($resultado['estado']) {
            'actualizado' => "{$resultado['usuario']->name} ahora tiene el rol de {$rol}.",
            'unido' => "{$resultado['usuario']->name} ya tenía cuenta y quedó unido como {$rol}.",
            default => "Le enviamos la invitación a {$datos['email']} como {$rol}. Al crear su cuenta quedará unido automáticamente.",
        });
    }

    public function destroy(Invitacion $invitacion)
    {
        $invitacion->delete();

        return back()->with('ok', 'Invitación anulada.');
    }
}
