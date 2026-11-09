<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DisponibilidadApiController;
use App\Http\Controllers\Api\OrganizacionApiController;
use App\Http\Controllers\Api\ReservaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST de Reservery (prefijo automático: /api)
|--------------------------------------------------------------------------
| Todo lo que se hace en la web también se puede hacer aquí. Las rutas
| protegidas exigen las cabeceras:
|     Authorization: Bearer {token}
|     Accept: application/json
*/

// ---------- Público: obtener un token ----------
Route::post('/auth/registro', [AuthApiController::class, 'registro']);
Route::post('/auth/login', [AuthApiController::class, 'login']);
Route::post('/auth/google', [AuthApiController::class, 'google']);
Route::post('/organizaciones', [OrganizacionApiController::class, 'store']); // RF01.1

Route::middleware('auth:sanctum')->group(function () {
    // ---------- Cuenta ----------
    Route::get('/auth/yo', [AuthApiController::class, 'yo']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);

    // ---------- Organización (RF01.1, RF01.2) ----------
    Route::post('/organizacion/unirse', [OrganizacionApiController::class, 'unirse']);
    Route::get('/organizacion', [OrganizacionApiController::class, 'show']);
    Route::middleware('rol:organizacion')->group(function () {
        Route::put('/organizacion', [OrganizacionApiController::class, 'update']);
        Route::post('/organizacion/habilitar', [OrganizacionApiController::class, 'habilitar']);
        Route::post('/organizacion/codigo', [OrganizacionApiController::class, 'regenerarCodigo']);
        Route::get('/organizacion/miembros', [OrganizacionApiController::class, 'miembros']);
        Route::put('/organizacion/miembros/{miembro}/rol', [OrganizacionApiController::class, 'cambiarRol']);
        Route::delete('/organizacion/miembros/{miembro}', [OrganizacionApiController::class, 'quitarMiembro']);
        Route::get('/organizacion/invitaciones', [OrganizacionApiController::class, 'invitaciones']);
        Route::post('/organizacion/invitaciones', [OrganizacionApiController::class, 'invitar']);
        Route::delete('/organizacion/invitaciones/{invitacion}', [OrganizacionApiController::class, 'quitarInvitacion']);
    });

    // ---------- Requiere organización habilitada ----------
    Route::middleware('organizacion.activa')->group(function () {
        // Disponibilidad (RF02, RF04, RF15)
        Route::get('/disponibilidad', [DisponibilidadApiController::class, 'agenda']);
        Route::post('/disponibilidad/verificar', [DisponibilidadApiController::class, 'verificar']);

        // Reservas: GET, POST, PUT/PATCH y DELETE (RF03–RF05, RF08, RF09, RF13)
        Route::apiResource('reservas', ReservaApiController::class)->names('api.reservas');
        Route::patch('/reservas/{reserva}/estado', [ReservaApiController::class, 'cambiarEstado']);
        Route::get('/reservas/{reserva}/historial', [ReservaApiController::class, 'historial']);

        // ===== Isabella: inventario, bloqueos, calendario y lista de espera =====

        // ===== Felipe: notificaciones, perfil, propósitos, reemplazos y analítica =====

    });
});
