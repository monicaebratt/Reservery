<?php

namespace Tests\Feature;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizacionTest extends TestCase
{
    use RefreshDatabase;

    private function datosOrganizacion(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Colegio San José', 'tipo' => 'colegio', 'ciudad' => 'Bello',
            'correo_contacto' => 'rectoria@sanjose.edu.co',
            'cuenta_nombre' => 'Rectoría', 'cuenta_email' => 'rectoria@sanjose.edu.co',
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ];
    }

    public function test_rf01_1_una_organizacion_se_registra_y_queda_sin_habilitar(): void
    {
        $r = $this->postJson('/api/organizaciones', $this->datosOrganizacion())->assertCreated();

        $org = Organizacion::firstWhere('nombre', 'Colegio San José');
        $this->assertFalse($org->configurada);
        $this->assertSame('organizacion', User::firstWhere('email', 'rectoria@sanjose.edu.co')->rol);
        $this->assertCount(4, $org->propositos);  // Propósitos con prioridad por defecto
        $this->assertNull($r->json('token'));  // Primero debe confirmar el correo
    }

    // Registra la organización por la API, confirma el correo y entra: devuelve el token
    private function registrarYEntrar(): string
    {
        $this->postJson('/api/organizaciones', $this->datosOrganizacion())->assertCreated();
        User::firstWhere('email', 'rectoria@sanjose.edu.co')->markEmailAsVerified();

        return $this->postJson('/api/auth/login', ['email' => 'rectoria@sanjose.edu.co', 'password' => 'clave1234'])->json('token');
    }

    public function test_rf01_1_nadie_se_une_antes_de_habilitar_el_acceso(): void
    {
        $this->postJson('/api/organizaciones', $this->datosOrganizacion());
        $codigo = Organizacion::first()->codigo_invitacion;

        $this->postJson('/api/auth/registro', [
            'name' => 'Estudiante', 'email' => 'est@sanjose.edu.co', 'password' => 'clave1234',
            'password_confirmation' => 'clave1234', 'codigo' => $codigo,
        ])->assertUnprocessable()->assertJsonPath('errors.codigo.0', 'Esa organización todavía no habilita el acceso de sus miembros.');
    }

    public function test_rf01_1_configurar_y_habilitar_permite_unirse_con_codigo(): void
    {
        $token = $this->registrarYEntrar();
        $auth = ['Authorization' => "Bearer {$token}"];

        $this->putJson('/api/organizacion', [
            'nombre' => 'Colegio San José', 'tipo' => 'colegio', 'ciudad' => 'Bello', 'correo_contacto' => 'rectoria@sanjose.edu.co',
            'hora_apertura' => '06:30', 'hora_cierre' => '18:00', 'dias_operativos' => [1, 2, 3, 4, 5],
            'requiere_aprobacion' => false,
        ], $auth)->assertOk()->assertJsonPath('data.hora_apertura', '06:30');

        $this->postJson('/api/organizacion/habilitar', [], $auth)->assertOk()->assertJsonPath('data.configurada', true);

        $codigo = Organizacion::first()->codigo_invitacion;
        $this->postJson('/api/auth/registro', [
            'name' => 'Estudiante', 'email' => 'est@sanjose.edu.co', 'password' => 'clave1234',
            'password_confirmation' => 'clave1234', 'codigo' => $codigo,
        ])->assertCreated()->assertJsonPath('usuario.rol', 'usuario');
        $this->assertSame(Organizacion::first()->id, User::firstWhere('email', 'est@sanjose.edu.co')->organizacion_id);
    }

    public function test_rf01_2_la_organizacion_asigna_y_revoca_el_rol_de_administrador(): void
    {
        ['cuenta' => $cuenta, 'usuario' => $usuario] = $this->crearOrganizacion();
        Sanctum::actingAs($cuenta);

        $this->putJson("/api/organizacion/miembros/{$usuario->id}/rol", ['rol' => 'admin'])->assertOk()->assertJsonPath('data.rol', 'admin');
        $this->assertTrue($usuario->fresh()->esAdmin());

        $this->putJson("/api/organizacion/miembros/{$usuario->id}/rol", ['rol' => 'usuario'])->assertOk();
        $this->assertFalse($usuario->fresh()->esAdmin());
    }

    public function test_rf01_2_un_correo_invitado_como_admin_entra_con_ese_rol(): void
    {
        ['cuenta' => $cuenta] = $this->crearOrganizacion();
        Sanctum::actingAs($cuenta);
        $this->postJson('/api/organizacion/invitaciones', ['email' => 'jefe@prueba.co', 'rol' => 'admin'])->assertCreated();

        $this->app['auth']->forgetGuards();
        // Al registrarse aún no ha confirmado el correo: no recibe el rol todavía
        $this->postJson('/api/auth/registro', [
            'name' => 'Jefe', 'email' => 'jefe@prueba.co', 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertCreated()->assertJsonPath('usuario.rol', 'usuario');

        // Al abrir el enlace del correo, entra con el rol de la invitación
        $jefe = \App\Models\User::where('email', 'jefe@prueba.co')->first();
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $jefe->id, 'hash' => sha1($jefe->email)]));
        $this->assertSame('admin', $jefe->fresh()->rol);
        $this->assertSame($cuenta->organizacion_id, $jefe->fresh()->organizacion_id);
    }

    public function test_rf12_un_admin_no_puede_configurar_la_organizacion_ni_dar_roles(): void
    {
        ['admin' => $admin, 'usuario' => $usuario] = $this->crearOrganizacion();
        Sanctum::actingAs($admin);

        $this->putJson("/api/organizacion/miembros/{$usuario->id}/rol", ['rol' => 'admin'])->assertForbidden();
        $this->postJson('/api/organizacion/habilitar')->assertForbidden();
    }

    public function test_rf12_un_usuario_no_puede_gestionar_inventario(): void
    {
        ['usuario' => $usuario] = $this->crearOrganizacion();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/objetos', ['nombre' => 'X', 'tipo' => 'espacio', 'categoria' => 'Sala'])->assertForbidden();
        $this->getJson('/api/analitica/ocupacion')->assertForbidden();
    }

    public function test_web_registro_de_organizacion_lleva_a_configurarla(): void
    {
        $this->post('/registro-organizacion', $this->datosOrganizacion())->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->get('/organizacion')->assertRedirect(route('verification.notice'));

        // Ya confirmado, pero sin habilitar: lo manda a la configuración
        $cuenta = User::firstWhere('email', 'rectoria@sanjose.edu.co');
        $cuenta->markEmailAsVerified();
        $this->actingAs($cuenta->fresh())->get('/dashboard')->assertRedirect(route('organizacion.edit'));
    }
}
