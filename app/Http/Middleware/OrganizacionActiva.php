<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF01.1: nadie usa Reservery hasta pertenecer a una organización que ya
 * configuró sus datos básicos y habilitó el acceso.
 */
class OrganizacionActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario->organizacion_id) {
            return $request->expectsJson()
                ? response()->json(['mensaje' => 'Primero únete a una organización con su código o registra la tuya.', 'siguiente' => 'POST /api/organizacion/unirse o POST /api/organizaciones'], 409)
                : redirect()->route('bienvenida');
        }

        if (! $usuario->organizacion->configurada) {
            if ($request->expectsJson()) {
                return response()->json(['mensaje' => 'La organización todavía no termina su configuración.'], 409);
            }

            return $usuario->esOrganizacion()
                ? redirect()->route('organizacion.edit')->with('error', 'Configura los datos básicos y habilita el acceso para empezar.')
                : redirect()->route('bienvenida');
        }

        return $next($request);
    }
}
