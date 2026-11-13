<?php

namespace Tests\Feature;

use App\Models\Invitacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Mockery;
use Tests\TestCase;

// + inicio de sesión con Google
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hu01_rechaza_correos_duplicados(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();

        $this->postJson('/api/auth/registro', [
            'name' => 'Otra', 'email' => $u->email, 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_hu01_queda_autenticado_tras_registrarse_y_debe_confirmar_el_correo(): void
    {
        ['org' => $org] = $this->crearOrganizacion();

        $this->post('/registro', [
            'name' => 'Nueva', 'email' => 'nueva@prueba.co', 'password' => 'clave1234',
            'password_confirmation' => 'clave1234', 'codigo' => $org->codigo_invitacion,
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();
        $nueva = User::firstWhere('email', 'nueva@prueba.co');
        $this->assertSame($org->id, $nueva->organizacion_id);

        // Sin confirmar el correo no puede entrar al panel
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get('/correo/confirmar')->assertOk()->assertSee('Confirma tu correo');

        // Con el enlace del correo, ya entra
        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $nueva->id, 'hash' => sha1($nueva->email)]))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($nueva->fresh())->get('/dashboard')->assertOk();
    }

    public function test_por_la_api_no_hay_token_hasta_confirmar_el_correo(): void
    {
        $this->postJson('/api/auth/registro', [
            'name' => 'Api', 'email' => 'api@prueba.co', 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertCreated()->assertJsonMissingPath('token');

        $this->postJson('/api/auth/login', ['email' => 'api@prueba.co', 'password' => 'clave1234'])->assertForbidden();

        User::firstWhere('email', 'api@prueba.co')->markEmailAsVerified();
        $this->postJson('/api/auth/login', ['email' => 'api@prueba.co', 'password' => 'clave1234'])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_hu02_el_error_de_login_no_revela_que_dato_fallo(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();

        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'mala'])
            ->assertStatus(401)->assertJsonPath('mensaje', 'Credenciales incorrectas.');
        $this->postJson('/api/auth/login', ['email' => 'noexiste@x.co', 'password' => 'mala'])
            ->assertStatus(401)->assertJsonPath('mensaje', 'Credenciales incorrectas.');
    }

    public function test_hu02_login_correcto_entrega_token(): void
    {
        ['usuario' => $u] = $this->crearOrganizacion();

        $token = $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'clave1234'])->assertOk()->json('token');
        $this->getJson('/api/auth/yo', ['Authorization' => "Bearer {$token}"])->assertOk()->assertJsonPath('data.email', $u->email);
    }

    public function test_google_crea_la_cuenta_y_aplica_la_invitacion(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secreto']);
        ['org' => $org] = $this->crearOrganizacion();
        Invitacion::create(['organizacion_id' => $org->id, 'email' => 'gmail@prueba.co', 'rol' => 'admin']);

        $google = (new UsuarioGoogle())->map(['id' => 'g-123', 'name' => 'Persona Google', 'email' => 'gmail@prueba.co', 'avatar' => 'https://foto']);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $usuario = User::firstWhere('email', 'gmail@prueba.co');
        $this->assertAuthenticatedAs($usuario);
        $this->assertSame('g-123', $usuario->google_id);
        $this->assertSame('admin', $usuario->rol);
        $this->assertSame($org->id, $usuario->organizacion_id);
    }

    public function test_google_vincula_una_cuenta_que_ya_existia(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secreto']);
        ['usuario' => $u] = $this->crearOrganizacion();

        $google = (new UsuarioGoogle())->map(['id' => 'g-999', 'name' => $u->name, 'email' => $u->email, 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($u);
        $this->assertSame('g-999', $u->fresh()->google_id);
        $this->assertSame(1, User::where('email', $u->email)->count());
    }

    public function test_google_sin_configurar_avisa_en_vez_de_fallar(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google')->assertRedirect(route('login'))->assertSessionHas('error');
    }

    public function test_quien_entra_con_google_sin_organizacion_va_a_la_bienvenida(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secreto']);
        $google = (new UsuarioGoogle())->map(['id' => 'g-1', 'name' => 'Sola', 'email' => 'sola@prueba.co', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback');
        $this->get('/dashboard')->assertRedirect(route('bienvenida'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
