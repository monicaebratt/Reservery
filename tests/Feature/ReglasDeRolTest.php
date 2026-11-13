<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Reglas de roles: quién reserva y quién ve las prioridades
class ReglasDeRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_rf03_el_administrador_y_la_organizacion_no_realizan_reservas(): void
    {
        ['org' => $org, 'admin' => $a, 'cuenta' => $c] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);

        foreach ([$a, $c] as $gestor) {
            Sanctum::actingAs($gestor);
            $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->assertForbidden();
            $this->postJson('/api/lista-espera', $this->solicitud($org, [$sala]))->assertForbidden();
        }

        $this->actingAs($a)->get('/reservas/create')->assertForbidden();
    }

    public function test_rf17_el_usuario_no_ve_los_niveles_de_prioridad(): void
    {
        ['usuario' => $u, 'admin' => $a] = $this->crearOrganizacion();

        Sanctum::actingAs($u);
        $this->assertArrayNotHasKey('nivel_prioridad', $this->getJson('/api/propositos')->json('data.0'));

        Sanctum::actingAs($a);
        $this->assertArrayHasKey('nivel_prioridad', $this->getJson('/api/propositos')->json('data.0'));
    }

    public function test_rf16_mantenimiento_no_es_un_proposito_para_reservar(): void
    {
        ['org' => $org] = $this->crearOrganizacion();

        $this->assertNotContains('Mantenimiento', $org->propositos()->pluck('nombre'));  // Para eso están los bloqueos
    }

    public function test_la_consola_de_la_api_solo_existe_si_se_activa(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();
        $this->actingAs($u);

        config(['reservery.consola_api' => false]);
        $this->get('/api-consola')->assertNotFound();
        $this->get('/dashboard')->assertDontSee('API y tokens');

        config(['reservery.consola_api' => true]);
        $this->get('/api-consola')->assertOk();
    }
}
