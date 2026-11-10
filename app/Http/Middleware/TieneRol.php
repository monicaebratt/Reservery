<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF12: protege rutas según el rol. Uso: ->middleware('rol:admin') o 'rol:organizacion'.
 * La cuenta de la organización también tiene los permisos de administrador.
 */
class TieneRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        $permitido = collect($roles)->contains(fn ($rol) => match ($rol) {
            'admin' => $usuario?->esAdmin(),
            'organizacion' => $usuario?->esOrganizacion(),
            default => $usuario?->rol === $rol,
        });

        abort_unless($permitido, 403, 'No tienes permiso para esta sección.');

        return $next($request);
    }
}
