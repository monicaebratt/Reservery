<?php

namespace Tests\Feature;

use App\Models\ListaEspera;
use App\Models\Notificacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SustitucionYListaEsperaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rf19_equivalente_mismo_tipo_capacidad_suficiente_y_zona_cercana_primero(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $pedido = $this->crearObjeto($org, ['nombre' => 'Sala A1', 'categoria' => 'Sala', 'capacidad' => 12, 'zona' => 'Bloque A', 'piso' => 1]);
        $lejos = $this->crearObjeto($org, ['nombre' => 'Sala C3', 'categoria' => 'Sala', 'capacidad' => 12, 'zona' => 'Bloque C', 'piso' => 3]);
        $cerca = $this->crearObjeto($org, ['nombre' => 'Sala A2', 'categoria' => 'Sala', 'capacidad' => 14, 'zona' => 'Bloque A', 'piso' => 2]);
        $pequena = $this->crearObjeto($org, ['nombre' => 'Sala A0', 'categoria' => 'Sala', 'capacidad' => 4, 'zona' => 'Bloque A', 'piso' => 1]);
        $otraCategoria = $this->crearObjeto($org, ['nombre' => 'Auditorio', 'categoria' => 'Auditorio', 'capacidad' => 100, 'zona' => 'Bloque A']);

        Sanctum::actingAs($d);
        $this->postJson('/api/reservas', $this->solicitud($org, [$pedido]));

        Sanctum::actingAs($u);
        $alternativas = $this->postJson('/api/alternativas', $this->solicitud($org, [$pedido]))->assertOk()->json('data');
        $ids = collect($alternativas)->pluck('objetos.0')->all();

        $this->assertSame([$cerca->id, $lejos->id], $ids);  // La de la misma zona va primero
        $this->assertNotContains($pequena->id, $ids);  // Capacidad menor: no es equivalente
        $this->assertNotContains($otraCategoria->id, $ids);  // Otro tipo de espacio: no es equivalente
    }

    public function test_rf21_la_alternativa_de_una_reserva_compuesta_conserva_el_paquete(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $aud1 = $this->crearObjeto($org, ['nombre' => 'Auditorio 1', 'categoria' => 'Auditorio', 'capacidad' => 100]);
        $aud2 = $this->crearObjeto($org, ['nombre' => 'Auditorio 2', 'categoria' => 'Auditorio', 'capacidad' => 100]);
        $mic1 = $this->crearObjeto($org, ['nombre' => 'Mic 1', 'tipo' => 'recurso', 'categoria' => 'Sonido', 'capacidad' => null]);
        $mic2 = $this->crearObjeto($org, ['nombre' => 'Mic 2', 'tipo' => 'recurso', 'categoria' => 'Sonido', 'capacidad' => null]);
        $aud1->recursosAsociados()->sync([$mic1->id]);
        $aud2->recursosAsociados()->sync([$mic2->id]);  // El auditorio 2 solo admite el mic 2

        Sanctum::actingAs($d);
        $this->postJson('/api/reservas', $this->solicitud($org, [$aud1, $mic1]))->assertCreated();

        Sanctum::actingAs($u);
        $r = $this->postJson('/api/reservas', $this->solicitud($org, [$aud1, $mic1]))->assertStatus(409);

        $alternativa = $r->json('alternativas.0');
        $this->assertEqualsCanonicalizing([$aud2->id, $mic2->id], $alternativa['objetos']);
        $this->assertStringContainsString('Mic 2 en lugar de Mic 1', $alternativa['descripcion']);
    }

    public function test_rnf02_la_busqueda_de_equivalentes_tarda_menos_de_5_segundos(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $objetos = collect(range(1, 25))->map(fn () => $this->crearObjeto($org));
        Sanctum::actingAs($u);

        $ms = $this->postJson('/api/alternativas', $this->solicitud($org, [$objetos->first()]))->assertOk()->json('milisegundos');
        $this->assertLessThan(5000, $ms);
    }

    public function test_rf22_hu14_lista_de_espera_por_orden_de_llegada_y_aviso_al_liberarse(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $segundo = $this->crearUsuario($org);
        $sala = $this->crearObjeto($org);

        Sanctum::actingAs($d);
        $reserva = $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->json('data.id');

        Sanctum::actingAs($u);
        $this->postJson('/api/lista-espera', $this->solicitud($org, [$sala]))->assertCreated()->assertJsonPath('data.posicion', 1);
        Sanctum::actingAs($segundo);
        $this->postJson('/api/lista-espera', $this->solicitud($org, [$sala]))->assertCreated()->assertJsonPath('data.posicion', 2);

        // Se libera el horario: solo el primero de la fila recibe el aviso
        Sanctum::actingAs($a);
        $this->patchJson("/api/reservas/{$reserva}/estado", ['accion' => 'cancelar'])->assertOk();

        $this->assertSame('notificada', ListaEspera::firstWhere('user_id', $u->id)->estado);
        $this->assertSame('esperando', ListaEspera::firstWhere('user_id', $segundo->id)->estado);
        $this->assertTrue(Notificacion::where('user_id', $u->id)->where('titulo', '¡Se liberó tu horario!')->exists());

        // El primero convierte su turno en reserva
        Sanctum::actingAs($u);
        $entrada = ListaEspera::firstWhere('user_id', $u->id);
        $this->postJson("/api/lista-espera/{$entrada->id}/convertir")->assertCreated()->assertJsonPath('data.estado', 'pendiente');
        $this->assertSame('atendida', $entrada->fresh()->estado);
    }

    public function test_si_el_avisado_sale_de_la_fila_el_turno_pasa_al_siguiente(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $segundo = $this->crearUsuario($org);
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($d);
        $reserva = $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->json('data.id');
        foreach ([$u, $segundo] as $persona) {
            Sanctum::actingAs($persona);
            $this->postJson('/api/lista-espera', $this->solicitud($org, [$sala]));
        }
        Sanctum::actingAs($a);
        $this->patchJson("/api/reservas/{$reserva}/estado", ['accion' => 'rechazar']);

        Sanctum::actingAs($u);
        $this->deleteJson('/api/lista-espera/'.ListaEspera::firstWhere('user_id', $u->id)->id)->assertOk();

        $this->assertSame('notificada', ListaEspera::firstWhere('user_id', $segundo->id)->estado);
    }
}
