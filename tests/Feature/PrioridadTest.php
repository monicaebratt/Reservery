<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Reemplazo;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrioridadTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(): array
    {
        $e = $this->crearOrganizacion();
        $e['lab'] = $this->crearObjeto($e['org'], ['nombre' => 'Lab 1', 'categoria' => 'Laboratorio', 'capacidad' => 30]);
        $e['lab2'] = $this->crearObjeto($e['org'], ['nombre' => 'Lab 2', 'categoria' => 'Laboratorio', 'capacidad' => 30]);

        Sanctum::actingAs($e['usuario']);
        $e['individual'] = $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Uso individual'),
        ]))->json('data.id');

        return $e;
    }

    public function test_rf17_mayor_prioridad_propone_reemplazo_y_avisa_con_alternativas(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['docente']);

        $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Evento institucional'),
        ]))->assertCreated()->assertJsonPath('data.estado', 'pendiente')->assertJsonCount(1, 'data.reemplazos_propuestos');

        $reemplazo = Reemplazo::first();
        $this->assertSame('propuesto', $reemplazo->estado);
        $this->assertSame([$e['lab2']->id], $reemplazo->alternativas[0]['objetos']);
        $this->assertTrue(Notificacion::where('user_id', $e['usuario']->id)->where('tipo', 'reemplazo')->exists());
        $this->assertSame('pendiente', Reserva::find($e['individual'])->estado);  // Aún no se confirma
    }

    public function test_rf17_igual_o_menor_prioridad_no_desplaza(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['docente']);

        $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Uso individual'),
        ]))->assertStatus(409);
        $this->assertSame(0, Reemplazo::count());
    }

    public function test_rf18_al_aprobar_la_prioritaria_se_cancela_la_afectada(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['docente']);
        $id = $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Evento institucional'),
        ]))->json('data.id');

        Sanctum::actingAs($e['admin']);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar'])->assertOk();

        $this->assertSame('cancelada', Reserva::find($e['individual'])->estado);
        $this->assertSame('confirmado', Reemplazo::first()->estado);
        $this->assertStringContainsString('mayor prioridad', Reserva::find($e['individual'])->historial->last()->detalle);
    }

    public function test_rf18_hu11_el_afectado_acepta_una_alternativa_y_conserva_su_reserva(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['docente']);
        $id = $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Evento institucional'),
        ]))->json('data.id');

        Sanctum::actingAs($e['usuario']);
        $reemplazo = Reemplazo::first();
        $this->postJson("/api/reemplazos/{$reemplazo->id}/alternativa", ['alternativa' => 0])->assertOk()->assertJsonPath('data.estado', 'alternativa_aceptada');

        Sanctum::actingAs($e['admin']);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'aprobar'])->assertOk();

        $afectada = Reserva::with('objetos')->find($e['individual']);
        $this->assertSame('pendiente', $afectada->estado);
        $this->assertSame([$e['lab2']->id], $afectada->objetos->pluck('id')->all());
    }

    public function test_si_se_rechaza_la_prioritaria_el_afectado_se_queda_con_su_horario(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['docente']);
        $id = $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Evento institucional'),
        ]))->json('data.id');

        Sanctum::actingAs($e['admin']);
        $this->patchJson("/api/reservas/{$id}/estado", ['accion' => 'rechazar'])->assertOk();

        $this->assertSame('descartado', Reemplazo::first()->estado);
        $this->assertSame('pendiente', Reserva::find($e['individual'])->estado);
    }

    public function test_hu10_el_admin_cambia_el_orden_de_prioridad(): void
    {
        $e = $this->escenario();
        Sanctum::actingAs($e['admin']);
        $individual = $e['org']->propositos()->firstWhere('nombre', 'Uso individual');

        $this->putJson("/api/propositos/{$individual->id}", ['nombre' => 'Uso individual', 'nivel_prioridad' => 9, 'color' => '#000000'])->assertOk();

        Sanctum::actingAs($e['docente']);
        // Ahora ni un evento institucional (4) puede desplazar el uso individual (9)
        $this->postJson('/api/reservas', $this->solicitud($e['org'], [$e['lab']], [
            'proposito_id' => $this->proposito($e['org'], 'Evento institucional'),
        ]))->assertStatus(409);
    }
}
