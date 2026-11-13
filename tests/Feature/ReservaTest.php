<?php

namespace Tests\Feature;

use App\Models\Bloqueo;
use App\Models\Notificacion;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rf02_la_agenda_muestra_franjas_ocupadas_y_libres(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($d);
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->assertCreated();

        Sanctum::actingAs($u);
        $agenda = $this->getJson('/api/disponibilidad?fecha='.$this->fecha())->assertOk()->json('data.0');

        $this->assertSame(['inicio' => '07:00', 'fin' => '21:00'], $agenda['horario']);
        $this->assertSame('10:00', $agenda['ocupado'][0]['inicio']);
        $this->assertArrayNotHasKey('solicitante', $agenda['ocupado'][0]);  // El usuario no ve quién reservó
        $this->assertSame([['inicio' => '07:00', 'fin' => '10:00'], ['inicio' => '12:00', 'fin' => '21:00']], $agenda['libre']);
    }

    public function test_rf03_hu04_la_solicitud_queda_pendiente_y_con_proposito(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))
            ->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.proposito.nombre', 'Reunión de trabajo');
    }

    public function test_rf16_el_proposito_es_obligatorio(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)], ['proposito_id' => null]))
            ->assertJsonValidationErrors('proposito_id');
    }

    public function test_rf04_rnf03_bloquea_horarios_ocupados_y_ofrece_alternativas(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org, ['categoria' => 'Sala']);
        $otra = $this->crearObjeto($org, ['categoria' => 'Sala', 'nombre' => 'Sala gemela']);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->assertCreated();
        $r = $this->postJson('/api/reservas', $this->solicitud($org, [$sala], ['hora_inicio' => '11:00', 'hora_fin' => '13:00']))
            ->assertStatus(409)
            ->assertJsonPath('problemas.0.tipo', 'ocupado');

        $this->assertSame([$otra->id], $r->json('alternativas.0.objetos'));
        $this->assertSame(1, Reserva::count());  // La duplicada nunca se registró
    }

    public function test_rf06_no_permite_reservar_fuera_del_horario_operativo(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$sala], ['hora_inicio' => '20:00', 'hora_fin' => '22:00']))
            ->assertStatus(409)->assertJsonPath('problemas.0.tipo', 'fuera_de_horario');
    }

    public function test_rf07_no_permite_reservar_un_horario_bloqueado(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Bloqueo::create(['organizacion_id' => $org->id, 'objeto_reservable_id' => $sala->id, 'creado_por' => $a->id,
            'fecha_inicio' => $this->fecha().' 08:00', 'fecha_fin' => $this->fecha().' 11:00', 'motivo' => 'Pintura']);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))
            ->assertStatus(409)->assertJsonPath('problemas.0.tipo', 'bloqueado');
    }

    public function test_rf07_un_bloqueo_puede_cancelar_las_reservas_que_quedan_dentro(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->json('data.id');

        Sanctum::actingAs($a);
        $datos = ['objetos' => [$sala->id], 'fecha_inicio' => $this->fecha().' 07:00', 'fecha_fin' => $this->fecha().' 13:00', 'motivo' => 'Fumigación'];
        $this->postJson('/api/bloqueos', $datos)->assertStatus(409)->assertJsonPath('reservas_afectadas.0.id', $id);
        $this->postJson('/api/bloqueos', $datos + ['cancelar_afectadas' => true])->assertCreated()->assertJsonPath('reservas_canceladas', 1);

        $this->assertSame('cancelada', Reserva::find($id)->estado);
    }

    public function test_rf13_rf14_la_reserva_compuesta_solo_admite_recursos_asociados(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $auditorio = $this->crearObjeto($org, ['categoria' => 'Auditorio']);
        $proyector = $this->crearObjeto($org, ['tipo' => 'recurso', 'categoria' => 'Proyector', 'capacidad' => null]);
        $balon = $this->crearObjeto($org, ['tipo' => 'recurso', 'categoria' => 'Deporte', 'capacidad' => null]);
        $auditorio->recursosAsociados()->sync([$proyector->id]);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$auditorio, $balon]))->assertJsonValidationErrors('recursos');
        $this->postJson('/api/reservas', $this->solicitud($org, [$auditorio, $proyector]))
            ->assertCreated()->assertJsonPath('data.compuesta', true)->assertJsonCount(2, 'data.objetos');
    }

    public function test_rnf04_espacio_y_recursos_se_envian_en_una_sola_solicitud(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        $pc = $this->crearObjeto($org, ['tipo' => 'recurso', 'categoria' => 'Portátil', 'capacidad' => null]);
        $sala->recursosAsociados()->sync([$pc->id]);
        Sanctum::actingAs($u);

        $datos = $this->solicitud($org, []);
        unset($datos['objetos']);
        $this->postJson('/api/reservas', $datos + ['espacio_id' => $sala->id, 'recursos' => [$pc->id]])->assertCreated()->assertJsonCount(2, 'data.objetos');
    }

    public function test_rf15_si_un_recurso_del_paquete_esta_ocupado_no_se_reserva_nada(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala1 = $this->crearObjeto($org);
        $sala2 = $this->crearObjeto($org);
        $vb = $this->crearObjeto($org, ['tipo' => 'recurso', 'categoria' => 'Proyector', 'capacidad' => null]);
        $sala1->recursosAsociados()->sync([$vb->id]);
        $sala2->recursosAsociados()->sync([$vb->id]);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$sala1, $vb]))->assertCreated();
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala2, $vb]))->assertStatus(409);
        $this->assertSame(1, Reserva::count());
    }

    public function test_rf05_patron_state_impide_saltos_de_estado(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)]))->json('data.id');

        Sanctum::actingAs($a);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'completar'])->assertStatus(409);  // Pendiente → completada no
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'rechazar'])->assertOk()->assertJsonPath('data.estado', 'rechazada');
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar'])->assertStatus(409)
            ->assertJsonPath('mensaje', 'No se puede aprobar una reserva en estado rechazada.');
    }

    public function test_hu19_solo_el_admin_aprueba(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)]))->json('data.id');

        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar'])->assertForbidden();
    }

    public function test_rf08_hu05_el_usuario_cancela_y_el_horario_queda_libre(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->json('data.id');

        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'cancelar'])->assertOk()->assertJsonPath('data.estado', 'cancelada');
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->assertCreated();
    }

    public function test_rf09_hu06_historial_con_reservas_pasadas_y_estado(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala], ['fecha' => $this->fecha(3)]));
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala], ['fecha' => $this->fecha(5)]));

        $r = $this->getJson('/api/reservas')->assertOk();
        $this->assertSame($this->fecha(5), $r->json('data.0.fecha'));  // Ordenadas por fecha
        $this->assertSame('pendiente', $r->json('data.1.estado'));
    }

    public function test_rf11_hu20_cada_cambio_de_estado_notifica_al_dueno(): void
    {
        ['org' => $org, 'usuario' => $u, 'admin' => $a, 'docente' => $d] = $this->crearOrganizacion();
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)]))->json('data.id');
        $this->assertTrue(Notificacion::where('user_id', $a->id)->where('tipo', 'nueva_solicitud')->exists());  // El admin se entera

        Sanctum::actingAs($a);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar']);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'completar']);

        $this->assertSame(['aprobada', 'completada'], Notificacion::where('user_id', $u->id)->orderBy('id')->pluck('tipo')->all());
    }

    public function test_aprobacion_automatica_si_la_organizacion_no_la_exige(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion(['requiere_aprobacion' => false]);
        Sanctum::actingAs($u);

        $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)]))->assertJsonPath('data.estado', 'aprobada');
    }

    public function test_rnf01_la_validacion_responde_en_menos_de_5_segundos(): void
    {
        ['org' => $org, 'usuario' => $u] = $this->crearOrganizacion();
        $objetos = collect(range(1, 6))->map(fn () => $this->crearObjeto($org));
        Sanctum::actingAs($u);

        $inicio = microtime(true);
        $this->postJson('/api/disponibilidad/verificar', $this->solicitud($org, $objetos->all()))->assertOk()->assertJsonPath('disponible', true);
        $this->assertLessThan(5, microtime(true) - $inicio);
    }
}
