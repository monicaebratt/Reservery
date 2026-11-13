<?php

namespace Tests;

use App\Models\ObjetoReservable;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El servicio externo de festivos no se consulta en las pruebas
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
        config(['reservery.notificar_por_correo' => false]);
    }

    // Organización habilitada con su cuenta, un admin y un usuario
    protected function crearOrganizacion(array $datos = []): array
    {
        $org = Organizacion::create($datos + [
            'nombre' => 'Org de prueba', 'tipo' => 'universidad', 'ciudad' => 'Medellín',
            'correo_contacto' => 'contacto@prueba.co', 'dias_operativos' => [1, 2, 3, 4, 5, 6, 7],
            'hora_apertura' => '07:00', 'hora_cierre' => '21:00', 'cerrar_en_festivos' => false, 'configurada' => true,
        ]);

        return [
            'org' => $org,
            'cuenta' => $this->crearUsuario($org, 'organizacion'),
            'admin' => $this->crearUsuario($org, 'admin'),
            'usuario' => $this->crearUsuario($org, 'usuario'),
            'docente' => $this->crearUsuario($org, 'usuario'),  // Segundo usuario: los admins no reservan
        ];
    }

    protected function crearUsuario(?Organizacion $org, string $rol = 'usuario', array $datos = []): User
    {
        static $n = 0;
        $n++;

        return User::create($datos + [
            'organizacion_id' => $org?->id,
            'name' => "Persona {$n}",
            'email' => "persona{$n}@prueba.co",
            'password' => 'clave1234',
            'rol' => $rol,
            'email_verified_at' => now(),
        ]);
    }

    protected function crearObjeto(Organizacion $org, array $datos = []): ObjetoReservable
    {
        $objeto = ObjetoReservable::create($datos + [
            'organizacion_id' => $org->id,
            'nombre' => 'Objeto '.uniqid(),
            'tipo' => 'espacio',
            'categoria' => 'Sala',
            'capacidad' => 10,
            'zona' => 'Bloque A',
            'piso' => 1,
        ]);
        $objeto->aplicarHorarioPorDefecto();

        return $objeto;
    }

    // Pasado mañana (siempre en el futuro)
    protected function fecha(int $dias = 2): string
    {
        return Carbon::today()->addDays($dias)->toDateString();
    }

    protected function proposito(Organizacion $org, string $nombre): int
    {
        return $org->propositos()->where('nombre', $nombre)->value('id');
    }

    // Datos de una solicitud por API
    protected function solicitud(Organizacion $org, array $objetos, array $extra = []): array
    {
        return $extra + [
            'proposito_id' => $this->proposito($org, 'Reunión de trabajo'),
            'fecha' => $this->fecha(),
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'motivo' => 'Prueba',
            'objetos' => collect($objetos)->map(fn ($o) => is_object($o) ? $o->id : $o)->all(),
        ];
    }
}
