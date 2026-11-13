<?php

namespace Tests\Feature;

use App\Models\HistorialReserva;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rf10_rf06_rf14_el_admin_gestiona_inventario_horario_y_paquetes(): void
    {
        ['org' => $org, 'admin' => $a] = $this->crearOrganizacion();
        $vb = $this->crearObjeto($org, ['tipo' => 'recurso', 'categoria' => 'Proyector', 'capacidad' => null]);
        Sanctum::actingAs($a);

        $id = $this->postJson('/api/objetos', [
            'nombre' => 'Sala nueva', 'tipo' => 'espacio', 'categoria' => 'Sala', 'capacidad' => 8,
            'recursos_asociados' => [$vb->id],
            'horarios' => [1 => ['abre' => true, 'inicio' => '08:00', 'fin' => '12:00']],
        ])->assertCreated()->assertJsonPath('data.recursos_asociados.0.id', $vb->id)->json('data.id');

        $this->assertSame(1, \App\Models\ObjetoReservable::find($id)->horarios()->count());  // Solo abre los lunes

        $this->putJson("/api/objetos/{$id}/horarios", ['horarios' => [2 => ['abre' => true, 'inicio' => '14:00', 'fin' => '18:00']]])
            ->assertOk()->assertJsonPath('data.horarios.0.dia_semana', 2);
        $this->deleteJson("/api/objetos/{$id}")->assertOk();
    }

    public function test_rf23_rf24_reporte_de_ocupacion_y_subutilizados(): void
    {
        ['org' => $org, 'admin' => $a, 'usuario' => $u] = $this->crearOrganizacion(['umbral_subutilizacion' => 20]);
        $usada = $this->crearObjeto($org, ['nombre' => 'Muy usada']);
        $vacia = $this->crearObjeto($org, ['nombre' => 'Vacía']);
        $r = Reserva::create(['organizacion_id' => $org->id, 'user_id' => $u->id, 'proposito_id' => $this->proposito($org, 'Clase programada'),
            'fecha' => today()->toDateString(), 'hora_inicio' => '07:00', 'hora_fin' => '21:00', 'motivo' => 'Todo el día', 'estado' => 'completada']);
        $r->objetos()->sync([$usada->id]);

        Sanctum::actingAs($a);
        $hoy = today()->toDateString();
        $reporte = $this->getJson("/api/analitica/ocupacion?desde={$hoy}&hasta={$hoy}")->assertOk()->json();

        $fila = collect($reporte['objetos'])->firstWhere('nombre', 'Muy usada');
        $this->assertEquals(100.0, $fila['tasa']);
        $this->assertSame(['Vacía'], collect($this->getJson("/api/analitica/subutilizados?desde={$hoy}&hasta={$hoy}")->json('data'))->pluck('nombre')->all());

        $pdf = $this->get("/api/analitica/reporte-pdf?desde={$hoy}&hasta={$hoy}")->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_rf25_horarios_de_mayor_demanda(): void
    {
        ['org' => $org, 'admin' => $a, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        $lunes = today()->subWeek()->startOfWeek();
        foreach ([0, 7, 14] as $d) {
            $r = Reserva::create(['organizacion_id' => $org->id, 'user_id' => $u->id, 'proposito_id' => $this->proposito($org, 'Clase programada'),
                'fecha' => $lunes->copy()->subDays($d)->toDateString(), 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'motivo' => 'Clase', 'estado' => 'completada']);
            $r->objetos()->sync([$sala->id]);
        }

        Sanctum::actingAs($a);
        $demanda = $this->getJson('/api/analitica/demanda?desde='.$lunes->copy()->subDays(20)->toDateString().'&hasta='.today()->toDateString().'&tipo=espacio')->assertOk();

        $demanda->assertJsonPath('picos.0.dia_nombre', 'Lunes')->assertJsonPath('picos.0.total', 3);
        $this->assertSame(['08:00', '09:00'], collect($demanda->json('picos'))->pluck('hora')->sort()->values()->all());
    }

    public function test_rf26_hu18_calendario_centralizado_solo_para_admin(): void
    {
        ['org' => $org, 'admin' => $a, 'usuario' => $u] = $this->crearOrganizacion();
        $sala = $this->crearObjeto($org);
        Sanctum::actingAs($u);
        $this->postJson('/api/reservas', $this->solicitud($org, [$sala]))->assertCreated();
        $this->getJson('/api/calendario?desde='.today()->toDateString().'&hasta='.$this->fecha(10))->assertForbidden();

        Sanctum::actingAs($a);
        $this->getJson('/api/calendario?desde='.today()->toDateString().'&hasta='.$this->fecha(10))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.extendedProps.estado', 'pendiente');
    }

    public function test_rnf06_una_organizacion_no_ve_los_datos_de_otra(): void
    {
        ['org' => $org1, 'usuario' => $u1] = $this->crearOrganizacion();
        ['org' => $org2, 'usuario' => $u2] = $this->crearOrganizacion(['nombre' => 'Otra']);
        $sala1 = $this->crearObjeto($org1);
        Sanctum::actingAs($u1);
        $id = $this->postJson('/api/reservas', $this->solicitud($org1, [$sala1]))->json('data.id');

        Sanctum::actingAs($u2);
        $this->getJson("/api/reservas/{$id}")->assertNotFound();
        $this->getJson("/api/objetos/{$sala1->id}")->assertNotFound();
        $this->postJson('/api/reservas', $this->solicitud($org2, [$sala1]))->assertJsonValidationErrors('objetos.0');
        $this->assertCount(0, $this->getJson('/api/objetos')->json('data'));
    }

    public function test_rnf08_cada_cambio_queda_con_fecha_y_responsable(): void
    {
        ['org' => $org, 'admin' => $a, 'usuario' => $u] = $this->crearOrganizacion();
        Sanctum::actingAs($u);
        $id = $this->postJson('/api/reservas', $this->solicitud($org, [$this->crearObjeto($org)]))->json('data.id');
        Sanctum::actingAs($a);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar', 'motivo' => 'Todo en orden']);

        $historial = $this->getJson("/api/reservas/{$id}/historial")->assertOk()->json('data');
        $this->assertSame(['creada', 'aprobada'], array_column($historial, 'accion'));
        $this->assertSame($a->name, $historial[1]['responsable']);
        $this->assertSame('Todo en orden', $historial[1]['detalle']);
        $this->assertNotNull(HistorialReserva::first()->created_at);
    }
}
