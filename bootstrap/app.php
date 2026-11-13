<?php

use App\Exceptions\NoDisponibleException;
use App\Exceptions\TransicionInvalidaException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => \App\Http\Middleware\TieneRol::class,
            'organizacion.activa' => \App\Http\Middleware\OrganizacionActiva::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Las rutas de la API siempre responden errores en JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // Transición de estado no permitida (patrón State): 409 en la API, mensaje en la web
        $exceptions->render(function (TransicionInvalidaException $e, Request $request) {
            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['mensaje' => $e->getMessage()], 409)
                : back()->with('error', $e->getMessage());
        });

        // No disponible: la API responde 409
        $exceptions->render(function (NoDisponibleException $e, Request $request) {
            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['mensaje' => $e->getMessage()] + $e->resultado->toArray(), 409)
                : back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            return $request->is('api/*') || $request->expectsJson()
                ? response()->json(['mensaje' => $e->getMessage() ?: 'No autorizado.'], 403)
                : abort(403, $e->getMessage());
        });
    })->create();
