<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerificacionController;
use App\Http\Controllers\BienvenidaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\Organizacion\InvitacionController;
use App\Http\Controllers\Organizacion\MiembroController;
use App\Http\Controllers\Organizacion\OrganizacionController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Página pública
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('inicio');

/*
|--------------------------------------------------------------------------
| Autenticación (solo visitantes) — RF01, HU01, HU02
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:acceso');
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:acceso');

    // RF01.1: una organización se registra junto con su cuenta
    Route::get('/registro-organizacion', [OrganizacionController::class, 'create'])->name('organizacion.create');
    Route::post('/registro-organizacion', [OrganizacionController::class, 'store'])->middleware('throttle:acceso')->name('organizacion.store');

    // Restaurar contraseña
    Route::get('/olvide-contrasena', [PasswordController::class, 'showForgot'])->name('password.request');
    Route::post('/olvide-contrasena', [PasswordController::class, 'sendLink'])->middleware('throttle:acceso')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordController::class, 'reset'])->middleware('throttle:acceso')->name('password.update');
});

// Inicio de sesión con Google (sirve para entrar, registrarse o registrar una organización)
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

// Confirmar el correo (necesario para recibir una invitación con rol)
Route::get('/correo/verificar/{id}/{hash}', [VerificacionController::class, 'verificar'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // Pantalla "Confirma tu correo": hasta confirmarlo no se puede usar nada más
    Route::get('/correo/confirmar', [VerificacionController::class, 'aviso'])->name('verification.notice');
    Route::post('/correo/reenviar', [VerificacionController::class, 'reenviar'])
        ->middleware('throttle:3,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Con sesión, pero aún sin organización habilitada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bienvenida', [BienvenidaController::class, 'index'])->name('bienvenida');
    Route::post('/bienvenida/unirse', [BienvenidaController::class, 'unirse'])->middleware('throttle:acceso')->name('bienvenida.unirse');
    Route::post('/bienvenida/organizacion', [OrganizacionController::class, 'storeParaCuenta'])->name('bienvenida.organizacion');

    // RF01.1: la cuenta de la organización configura sus datos aunque aún no esté habilitada
    Route::middleware('rol:organizacion')->group(function () {
        Route::get('/organizacion', [OrganizacionController::class, 'edit'])->name('organizacion.edit');
        Route::put('/organizacion', [OrganizacionController::class, 'update'])->name('organizacion.update');
        Route::post('/organizacion/habilitar', [OrganizacionController::class, 'habilitar'])->name('organizacion.habilitar');
        Route::post('/organizacion/codigo', [OrganizacionController::class, 'regenerarCodigo'])->name('organizacion.codigo');

        // RF01.2: asignar o revocar el rol de administrador
        Route::get('/organizacion/miembros', [MiembroController::class, 'index'])->name('miembros.index');
        Route::patch('/organizacion/miembros/{miembro}/rol', [MiembroController::class, 'cambiarRol'])->name('miembros.rol');
        Route::delete('/organizacion/miembros/{miembro}', [MiembroController::class, 'destroy'])->name('miembros.destroy');
        Route::post('/organizacion/invitaciones', [InvitacionController::class, 'store'])->name('invitaciones.store');
        Route::delete('/organizacion/invitaciones/{invitacion}', [InvitacionController::class, 'destroy'])->name('invitaciones.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Zona privada: sesión iniciada y organización habilitada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'organizacion.activa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Disponibilidad en tiempo real (RF02, HU03)
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index');
    Route::get('/disponibilidad/agenda', [DisponibilidadController::class, 'agenda'])->name('disponibilidad.agenda');
    Route::get('/disponibilidad/semana', [DisponibilidadController::class, 'semana'])->name('disponibilidad.semana');
    Route::post('/disponibilidad/verificar', [DisponibilidadController::class, 'verificar'])->name('disponibilidad.verificar');

    // Reservas simples y compuestas (RF03–RF05, RF08, RF09, RF13, RF15, RF16)
    // Solo los usuarios realizan reservas (RF03); el administrador las gestiona
    Route::middleware('rol:usuario')->group(function () {
        Route::get('/reservas/no-disponible', [ReservaController::class, 'noDisponible'])->name('reservas.no-disponible');
        Route::get('/reservas/create', [ReservaController::class, 'create'])->name('reservas.create');
        Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    });
    Route::resource('reservas', ReservaController::class)->except(['create', 'store']);
    Route::patch('/reservas/{reserva}/{accion}', [ReservaController::class, 'cambiarEstado'])
        ->whereIn('accion', ['aprobar', 'rechazar', 'cancelar', 'completar'])
        ->name('reservas.estado');

    // Tokens y consola para consumir la API REST
    Route::get('/api-consola', [TokenController::class, 'index'])->name('api.consola');
    Route::post('/api-tokens', [TokenController::class, 'store'])->name('api.tokens.store');
    Route::delete('/api-tokens/{id}', [TokenController::class, 'destroy'])->name('api.tokens.destroy');

    // ===== Isabella: inventario, bloqueos, calendario, listados y lista de espera =====
    // Inventario de espacios y recursos 
    Route::resource('objetos', \App\Http\Controllers\ObjetoReservableController::class)        
        ->parameters(['objetos' => 'objeto'])->except(['index', 'show'])->middleware('rol:admin');    
    Route::resource('objetos', \App\Http\Controllers\ObjetoReservableController::class)        
        ->parameters(['objetos' => 'objeto'])->only(['index', 'show']);

    // Bloqueos por mantenimientos o eventos
    Route::middleware('rol:admin')->group(function () {        
    Route::get('/bloqueos', [\App\Http\Controllers\BloqueoController::class, 'index'])->name('bloqueos.index');        
    Route::post('/bloqueos', [\App\Http\Controllers\BloqueoController::class, 'store'])->name('bloqueos.store');        
    Route::delete('/bloqueos/{bloqueo}', [\App\Http\Controllers\BloqueoController::class, 'destroy'])->name('bloqueos.destroy');  
      });

    // Calendarios centralizados
    Route::middleware('rol:admin')->group(function () {        
    Route::get('/calendario', [\App\Http\Controllers\CalendarioController::class, 'index'])->name('calendario.index');        
    Route::get('/calendario/eventos', [\App\Http\Controllers\CalendarioController::class, 'eventos'])->name('calendario.eventos');   
         });

    // Listados con datatables
    Route::get('/listados/reservas', [\App\Http\Controllers\ListadoController::class, 'reservas'])->name('listados.reservas');    
    Route::get('/listados/objetos', [\App\Http\Controllers\ListadoController::class, 'objetos'])->name('listados.objetos');

    // Lista de espera
    Route::post('/lista-espera', [\App\Http\Controllers\ListaEsperaController::class, 'store'])->middleware('rol:usuario')->name('lista-espera.store');    
    Route::get('/lista-espera', [\App\Http\Controllers\ListaEsperaController::class, 'index'])->name('lista-espera.index');    
    Route::post('/lista-espera/{entrada}/convertir', [\App\Http\Controllers\ListaEsperaController::class, 'convertir'])->name('lista-espera.convertir');    
    Route::delete('/lista-espera/{entrada}', [\App\Http\Controllers\ListaEsperaController::class, 'destroy'])->name('lista-espera.destroy');


    // ===== Felipe: notificaciones, perfil, propósitos, reemplazos y analítica =====
    // Notificaciones
    Route::get('/notificaciones', [\App\Http\Controllers\NotificacionController::class, 'index'])->name('notificaciones.index');    
    Route::get('/notificaciones/contador', [\App\Http\Controllers\NotificacionController::class, 'contador'])->name('notificaciones.contador');    
    Route::patch('/notificaciones/{notificacion}/leer', [\App\Http\Controllers\NotificacionController::class, 'leer'])->name('notificaciones.leer');    
    Route::post('/notificaciones/leer-todas', [\App\Http\Controllers\NotificacionController::class, 'leerTodas'])->name('notificaciones.leer-todas');

    // Perfil del usuario activo
    Route::get('/perfil', [\App\Http\Controllers\PerfilController::class, 'edit'])->name('perfil.edit');    
    Route::put('/perfil', [\App\Http\Controllers\PerfilController::class, 'update'])->name('perfil.update');    
    Route::put('/perfil/contrasena', [\App\Http\Controllers\PerfilController::class, 'updatePassword'])->name('perfil.password');    
    Route::delete('/perfil/foto', [\App\Http\Controllers\PerfilController::class, 'destroyFoto'])->name('perfil.foto.destroy');

    //Propositos y regla de prioridad
    Route::middleware('rol:admin')->group(function () {        
Route::get('/propositos', [\App\Http\Controllers\PropositoController::class, 'index'])->name('propositos.index');        
Route::post('/propositos', [\App\Http\Controllers\PropositoController::class, 'store'])->name('propositos.store');        
Route::put('/propositos/{proposito}', [\App\Http\Controllers\PropositoController::class, 'update'])->name('propositos.update');        
Route::patch('/propositos/{proposito}/{direccion}', [\App\Http\Controllers\PropositoController::class, 'mover'])            ->whereIn('direccion', ['subir', 'bajar'])->name('propositos.mover');        
Route::delete('/propositos/{proposito}', [\App\Http\Controllers\PropositoController::class, 'destroy'])->name('propositos.destroy');    });
});
