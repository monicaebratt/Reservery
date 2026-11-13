<?php

namespace Tests\Feature;

use App\Models\ObjetoReservable;
use App\Models\Reemplazo;
use App\Models\Reserva;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Recorre todas las pantallas web con cada rol, sobre los datos de demostración
class PantallasWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function como(string $email): static
    {
        return $this->actingAs(User::firstWhere('email', $email));
    }

    public function test_paginas_publicas(): void
    {
        foreach (['/', '/login', '/registro', '/registro-organizacion', '/olvide-contrasena'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_todas_las_pantallas_del_administrador(): void
    {
        $reserva = Reserva::first();
        $objeto = ObjetoReservable::first();
        config(['reservery.consola_api' => true]);  // La consola de la API solo existe si se activa
        $this->como('admin@reservery.com');

        foreach ([
            '/dashboard', '/disponibilidad', '/disponibilidad/agenda?fecha='.today()->addDay()->toDateString(),
            '/reservas', '/reservas?estado=pendiente', "/reservas/{$reserva->id}",
            '/lista-espera', '/reemplazos', '/notificaciones', '/notificaciones/contador', '/api-consola',
            '/objetos', '/objetos/create', "/objetos/{$objeto->id}", "/objetos/{$objeto->id}/edit",
            '/bloqueos', '/calendario', '/calendario/eventos?start='.today()->toDateString().'&end='.today()->addWeek()->toDateString(),
            '/listados/reservas', '/listados/objetos', '/propositos', '/analitica', '/perfil',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/analitica/reporte-pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/organizacion')->assertForbidden();  // La configuración es solo de la cuenta de la organización
        $this->get('/reservas/create')->assertForbidden();  // El administrador gestiona, no reserva
    }

    public function test_pantallas_de_la_cuenta_de_la_organizacion(): void
    {
        $this->como('org@reservery.com');
        $this->get('/organizacion')->assertOk()->assertSee('DEMO2026');
        $this->get('/organizacion/miembros')->assertOk()->assertSee('nuevo.admin@reservery.com');
    }

    public function test_el_usuario_no_entra_a_lo_administrativo(): void
    {
        $this->como('usuario@reservery.com');
        $this->get('/dashboard')->assertOk();
        $this->get('/reservas/create')->assertOk();
        foreach (['/objetos/create', '/bloqueos', '/calendario', '/propositos', '/analitica', '/organizacion'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_la_organizacion_sin_habilitar_va_a_su_configuracion(): void
    {
        $this->como('colegio@reservery.com');
        $this->get('/dashboard')->assertRedirect(route('organizacion.edit'));
        $this->get('/organizacion')->assertOk()->assertSee('Habilitar acceso de miembros');
    }

    public function test_flujo_web_no_disponible_muestra_alternativas_y_lista_de_espera(): void
    {
        $this->como('usuario2@reservery.com');
        $ocupada = Reserva::where('estado', 'aprobada')->whereDate('fecha', '>=', today())->with('objetos')->first();

        $this->post('/reservas', [
            'proposito_id' => $ocupada->proposito_id, 'fecha' => $ocupada->fecha->toDateString(),
            'hora_inicio' => substr($ocupada->hora_inicio, 0, 5), 'hora_fin' => substr($ocupada->hora_fin, 0, 5),
            'motivo' => 'Prueba', 'objetos' => $ocupada->objetos->pluck('id')->all(),
        ])->assertRedirect(route('reservas.no-disponible'));

        $this->get('/reservas/no-disponible')->assertOk()->assertSee('Alternativas equivalentes')->assertSee('Anotarme en la lista de espera');
    }

    public function test_el_afectado_ve_y_acepta_la_alternativa_desde_la_web(): void
    {
        $reemplazo = Reemplazo::first();
        $this->como('usuario@reservery.com');

        $this->get("/reemplazos/{$reemplazo->id}")->assertOk()->assertSee('Aceptar esta alternativa');
        $this->post("/reemplazos/{$reemplazo->id}/alternativa", ['alternativa' => 0])->assertRedirect();
        $this->assertSame('alternativa_aceptada', $reemplazo->fresh()->estado);
    }
}
