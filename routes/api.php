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
Route::post('/auth/registro', [AuthApiController::class, 'registro'])->middleware('throttle:acceso');
Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:acceso');
Route::post('/auth/google', [AuthApiController::class, 'google'])->middleware('throttle:acceso');
Route::post('/auth/reenviar-verificacion', [AuthApiController::class, 'reenviarVerificacion'])->middleware('throttle:acceso');
Route::post('/organizaciones', [OrganizacionApiController::class, 'store'])->middleware('throttle:acceso'); // RF01.1

// ---------- Cuenta ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/yo', [AuthApiController::class, 'yo']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
});

// Todo lo demás exige token Y correo confirmado
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // ---------- Organización (RF01.1, RF01.2) ----------
    Route::post('/organizacion/unirse', [OrganizacionApiController::class, 'unirse'])->middleware('throttle:acceso');
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
        // Inventario de espacios y recursos
    Route::get('/objetos', [\App\Http\Controllers\Api\ObjetoApiController::class, 'index']);        
    Route::get('/objetos/{objeto}', [\App\Http\Controllers\Api\ObjetoApiController::class, 'show']);        
    Route::middleware('rol:admin')->group(function () {            
    Route::post('/objetos', [\App\Http\Controllers\Api\ObjetoApiController::class, 'store']);            
    Route::put('/objetos/{objeto}', [\App\Http\Controllers\Api\ObjetoApiController::class, 'update']);            
    Route::delete('/objetos/{objeto}', [\App\Http\Controllers\Api\ObjetoApiController::class, 'destroy']);            
    Route::put('/objetos/{objeto}/horarios', [\App\Http\Controllers\Api\ObjetoApiController::class, 'horarios']);       
    Route::put('/objetos/{objeto}/recursos', [\App\Http\Controllers\Api\ObjetoApiController::class, 'recursos']);      
      });

    // Bloqueos 
    Route::middleware('rol:admin')->group(function () {            
    Route::get('/bloqueos', [\App\Http\Controllers\Api\BloqueoApiController::class, 'index']);            
    Route::post('/bloqueos', [\App\Http\Controllers\Api\BloqueoApiController::class, 'store']);            
    Route::delete('/bloqueos/{bloqueo}', [\App\Http\Controllers\Api\BloqueoApiController::class, 'destroy']);     
      });

    
    // Calendario centralizado
    Route::get('/calendario', [\App\Http\Controllers\Api\CalendarioApiController::class, 'index'])->middleware('rol:admin');

    // Lista de espera
    Route::post('/alternativas', [\App\Http\Controllers\Api\AlternativaApiController::class, 'buscar']);        
    Route::get('/lista-espera', [\App\Http\Controllers\Api\ListaEsperaApiController::class, 'index']);        
    Route::post('/lista-espera', [\App\Http\Controllers\Api\ListaEsperaApiController::class, 'store']);        
    Route::post('/lista-espera/{entrada}/convertir', [\App\Http\Controllers\Api\ListaEsperaApiController::class, 'convertir']);        
    Route::delete('/lista-espera/{entrada}', [\App\Http\Controllers\Api\ListaEsperaApiController::class, 'destroy']);


        // ===== Felipe: notificaciones, perfil, propósitos, reemplazos y analítica =====
        //Notificaciones
    Route::get('/notificaciones', [\App\Http\Controllers\Api\NotificacionApiController::class, 'index']);        
    Route::patch('/notificaciones/{notificacion}/leer', [\App\Http\Controllers\Api\NotificacionApiController::class, 'leer']);        
    Route::post('/notificaciones/leer-todas', [\App\Http\Controllers\Api\NotificacionApiController::class, 'leerTodas']);
    });

    //Perfil
    Route::get('/perfil', [\App\Http\Controllers\Api\PerfilApiController::class, 'show']);        
    Route::put('/perfil', [\App\Http\Controllers\Api\PerfilApiController::class, 'update']);

    //Propositos y prioridad
      Route::get('/propositos', [\App\Http\Controllers\Api\PropositoApiController::class, 'index']);        
    Route::middleware('rol:admin')->group(function () {            
    Route::post('/propositos', [\App\Http\Controllers\Api\PropositoApiController::class, 'store']);            
    Route::put('/propósitos/{proposito}', [\App\Http\Controllers\Api\PropositoApiController::class, 'update']);            
    Route::delete('/propositos/{proposito}', [\App\Http\Controllers\Api\PropositoApiController::class, 'destroy']);        });

    // Reemplazos por prioridad
    Route::get('/reemplazos', [\App\Http\Controllers\Api\ReemplazoApiController::class, 'index']);        
Route::post('/reemplazos/{reemplazo}/alternativa', [\App\Http\Controllers\Api\ReemplazoApiController::class, 'aceptarAlternativa']);        
Route::post('/reemplazos/{reemplazo}/ceder', [\App\Http\Controllers\Api\ReemplazoApiController::class, 'ceder']);

    //Analítica
    Route::middleware('rol:admin')->group(function () {            
Route::get('/analitica/ocupacion', [\App\Http\Controllers\Api\AnaliticaApiController::class, 'ocupacion']);            
Route::get('/analítica/subutilizados', [\App\Http\Controllers\Api\AnaliticaApiController::class, 'subutilizados']);            
Route::get('/analitica/demanda', [\App\Http\Controllers\Api\AnaliticaApiController::class, 'demanda']);            
Route::get('/analitica/reporte-pdf', [\App\Http\Controllers\Api\AnaliticaApiController::class, 'pdf']);        });

});
