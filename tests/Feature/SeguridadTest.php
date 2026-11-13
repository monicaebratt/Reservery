<?php

namespace Tests\Feature;

use App\Models\Invitacion;
use App\Models\Reemplazo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Tests\TestCase;

// Pruebas de seguridad
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private function enlaceVerificacion(User $u): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $u->id, 'hash' => sha1($u->email)]);
    }

    public function test_nadie_se_queda_con_una_invitacion_de_admin_sin_confirmar_el_correo(): void
    {
        ['org' => $org] = $this->crearOrganizacion();
        Invitacion::create(['organizacion_id' => $org->id, 'email' => 'ana@prueba.co', 'rol' => 'admin']);

        $this->post('/registro', [
            'name' => 'Intrusa', 'email' => 'ana@prueba.co', 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ]);
        $ana = User::firstWhere('email', 'ana@prueba.co');
        $this->assertNull($ana->organizacion_id);
        $this->assertNotSame('admin', $ana->rol);

        // Solo quien abre el enlace del correo recibe el rol
        $this->get($this->enlaceVerificacion($ana))->assertRedirect();
        $this->assertSame('admin', $ana->fresh()->rol);
        $this->assertSame($org->id, $ana->fresh()->organizacion_id);
    }

    public function test_el_enlace_de_verificacion_sin_firma_no_sirve(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();
        $u->forceFill(['email_verified_at' => null])->save();

        $this->get("/correo/verificar/{$u->id}/".sha1($u->email))->assertForbidden();
        $this->assertNull($u->fresh()->email_verified_at);
    }

    public function test_entrar_con_el_codigo_no_da_el_rol_invitado_hasta_confirmar(): void
    {
        ['org' => $org] = $this->crearOrganizacion();
        Invitacion::create(['organizacion_id' => $org->id, 'email' => 'luis@prueba.co', 'rol' => 'admin']);

        $this->postJson('/api/auth/registro', [
            'name' => 'Luis', 'email' => 'luis@prueba.co', 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
            'codigo' => $org->codigo_invitacion,
        ])->assertCreated()->assertJsonPath('usuario.rol', 'usuario');

        $luis = User::firstWhere('email', 'luis@prueba.co');
        $this->get($this->enlaceVerificacion($luis));
        $this->assertSame('admin', $luis->fresh()->rol);
    }

    public function test_cambiar_el_correo_en_el_perfil_pide_confirmarlo_de_nuevo(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();
        Sanctum::actingAs($u);

        $this->putJson('/api/perfil', ['email' => 'nuevo@prueba.co'])->assertOk();
        $this->assertNull($u->fresh()->email_verified_at);
    }

    public function test_google_por_api_rechaza_tokens_de_otra_app(): void
    {
        config(['services.google.client_id' => 'reservery-id', 'services.google.client_secret' => 'secreto']);
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['aud' => 'otra-app', 'email' => 'x@gmail.com', 'email_verified' => 'true'])]);

        $this->postJson('/api/auth/google', ['access_token' => 'token-ajeno'])->assertStatus(401);
        $this->assertSame(0, User::count());
    }

    public function test_google_por_api_acepta_tokens_de_reservery(): void
    {
        config(['services.google.client_id' => 'reservery-id', 'services.google.client_secret' => 'secreto']);
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['aud' => 'reservery-id', 'email' => 'sol@gmail.com', 'email_verified' => 'true'])]);
        $google = (new UsuarioGoogle())->map(['id' => 'g-7', 'name' => 'Sol', 'email' => 'sol@gmail.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->stateless->userFromToken')->andReturn($google);

        $this->postJson('/api/auth/google', ['access_token' => 'token-bueno'])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_google_le_quita_la_cuenta_a_quien_se_registro_con_un_correo_ajeno(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secreto']);
        // Alguien creó la cuenta con el correo de Sofía sin confirmarlo
        $falsa = User::create(['name' => 'Falsa', 'email' => 'sofia@gmail.com', 'password' => 'clave1234', 'rol' => 'usuario']);

        $google = (new UsuarioGoogle())->map(['id' => 'g-5', 'name' => 'Sofía', 'email' => 'sofia@gmail.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);
        $this->get('/auth/google/callback');

        $this->assertNull($falsa->fresh()->password);
        $this->assertNotNull($falsa->fresh()->email_verified_at);
    }

    public function test_un_reemplazo_de_otra_organizacion_responde_404(): void
    {
        $a = $this->crearOrganizacion();
        $lab = $this->crearObjeto($a['org'], ['nombre' => 'Lab', 'categoria' => 'Laboratorio']);
        $this->crearObjeto($a['org'], ['nombre' => 'Lab 2', 'categoria' => 'Laboratorio']);
        Sanctum::actingAs($a['usuario']);
        $this->postJson('/api/reservas', $this->solicitud($a['org'], [$lab], ['proposito_id' => $this->proposito($a['org'], 'Uso individual')]))->assertCreated();
        Sanctum::actingAs($a['docente']);
        $this->postJson('/api/reservas', $this->solicitud($a['org'], [$lab], ['proposito_id' => $this->proposito($a['org'], 'Evento institucional')]))->assertCreated();
        $reemplazo = Reemplazo::firstOrFail();

        $b = $this->crearOrganizacion(['nombre' => 'Otra organización']);
        $this->actingAs($b['usuario'])->get("/reemplazos/{$reemplazo->id}")->assertNotFound();
        Sanctum::actingAs($b['usuario']);
        $this->postJson("/api/reemplazos/{$reemplazo->id}/ceder")->assertNotFound();
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $u->email, 'password' => 'mala'.$i])->assertRedirect();
        }
        // El sexto intento no entra, ni siquiera con la contraseña correcta
        $this->post('/login', ['email' => $u->email, 'password' => 'clave1234'])->assertSessionHasErrors('email');
        $this->assertGuest();

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'mala'.$i])->assertStatus(401);
        }
        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'otra'])->assertStatus(429);
    }

    public function test_entrar_con_google_no_confirma_un_correo_cambiado_en_el_perfil(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secreto']);
        ['org' => $org] = $this->crearOrganizacion();
        Invitacion::create(['organizacion_id' => $org->id, 'email' => 'ana@prueba.co', 'rol' => 'admin']);

        // Un usuario que entra con Google cambia su correo al de la invitación
        $juan = User::create(['organizacion_id' => $org->id, 'name' => 'Juan', 'email' => 'juan@gmail.com', 'google_id' => 'g-juan', 'rol' => 'usuario', 'email_verified_at' => now()]);
        Sanctum::actingAs($juan);
        $this->putJson('/api/perfil', ['email' => 'ana@prueba.co'])->assertOk();
        $this->assertNull($juan->fresh()->email_verified_at);

        // Vuelve a entrar con su Google (juan@gmail.com): ni se confirma ni se vuelve admin
        $google = (new UsuarioGoogle())->map(['id' => 'g-juan', 'name' => 'Juan', 'email' => 'juan@gmail.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);
        $this->get('/auth/google/callback');

        $this->assertNull($juan->fresh()->email_verified_at);
        $this->assertNotSame('admin', $juan->fresh()->rol);
    }

    public function test_el_limite_de_intentos_es_por_correo_y_no_bloquea_a_otros(): void
    {
        ['usuario' => $u, 'admin' => $a] = $this->crearOrganizacion();

        foreach (range(1, 6) as $i) {
            $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'mala'.$i]);
        }
        // Otra persona desde la misma IP puede entrar sin problema
        $this->postJson('/api/auth/login', ['email' => $a->email, 'password' => 'clave1234'])->assertOk();
    }
}
