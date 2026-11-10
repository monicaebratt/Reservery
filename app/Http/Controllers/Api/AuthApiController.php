<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

/** Autenticación de la API con tokens de Sanctum (RF01). */
class AuthApiController extends Controller
{
    /** POST /api/auth/registro */
    public function registro(Request $request, OrganizacionService $organizaciones)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'codigo' => ['nullable', 'string', 'max:12'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ]);

        $usuario = DB::transaction(function () use ($datos, $organizaciones) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => mb_strtolower($datos['email']),
                'password' => $datos['password'],
            ]);
            filled($datos['codigo'] ?? null)
                ? $organizaciones->unirseConCodigo($usuario, $datos['codigo'])
                : $organizaciones->unirsePorInvitacion($usuario);

            return $usuario->fresh('organizacion');
        });
        $usuario->enviarVerificacion();

        // Sin token: primero debe confirmar el correo con el enlace que le llegó
        return response()->json([
            'mensaje' => 'Cuenta creada. Te enviamos un enlace para confirmar tu correo; después inicia sesión.',
            'usuario' => new UsuarioResource($usuario),
        ], 201);
    }

    /** POST /api/auth/login → devuelve un token Bearer */
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ]);

        $usuario = User::where('email', mb_strtolower($datos['email']))->first();

        // HU02: no se dice cuál de los dos datos falló
        if (! $usuario || ! $usuario->password || ! Hash::check($datos['password'], $usuario->password)) {
            return response()->json(['mensaje' => 'Credenciales incorrectas.'], 401);
        }
        if (! $usuario->hasVerifiedEmail()) {
            return response()->json(['mensaje' => 'Confirma tu correo antes de iniciar sesión. Si no te llegó, usa POST /api/auth/reenviar-verificacion.'], 403);
        }

        return $this->conToken($usuario->load('organizacion'), $datos['dispositivo'] ?? 'api');
    }

    /**
     * POST /api/auth/google {access_token}
     * Para apps externas que ya obtuvieron un token de Google.
     */
    public function google(Request $request, OrganizacionService $organizaciones)
    {
        $datos = $request->validate([
            'access_token' => ['required', 'string'],
            'codigo' => ['nullable', 'string', 'max:12'],
        ]);

        // El token debe haberse creado para Reservery (aud) y con un correo verificado.
        // Si no, una app ajena con el token de alguien podría entrar como esa persona.
        try {
            $info = Http::timeout(5)->get('https://oauth2.googleapis.com/tokeninfo', ['access_token' => $datos['access_token']])->json();
        } catch (\Throwable) {
            $info = null;
        }
        $clienteId = config('services.google.client_id');
        if (! $clienteId || ($info['aud'] ?? null) !== $clienteId || ! in_array($info['email_verified'] ?? false, [true, 'true'], true)) {
            return response()->json(['mensaje' => 'El token de Google no es válido.'], 401);
        }

        try {
            $google = Socialite::driver('google')->stateless()->userFromToken($datos['access_token']);
        } catch (\Throwable) {
            return response()->json(['mensaje' => 'El token de Google no es válido.'], 401);
        }

        $usuario = User::where('google_id', $google->getId())->orWhere('email', mb_strtolower($google->getEmail()))->first()
            ?? User::create([
                'name' => $google->getName() ?: 'Usuario de Google',
                'email' => mb_strtolower($google->getEmail()),
                'google_id' => $google->getId(),
                'foto' => $google->getAvatar(),
                'email_verified_at' => now(),
            ]);
        $this->vincularGoogle($usuario, $google->getId());
        $organizaciones->aplicarInvitacionVerificada($usuario);

        if (! $usuario->fresh()->organizacion_id) {
            filled($datos['codigo'] ?? null)
                ? $organizaciones->unirseConCodigo($usuario, $datos['codigo'])
                : $organizaciones->unirsePorInvitacion($usuario);
        }

        return $this->conToken($usuario->fresh('organizacion'), 'google');
    }

    /** POST /api/auth/reenviar-verificacion {email}. Responde igual exista o no el correo. */
    public function reenviarVerificacion(Request $request)
    {
        $datos = $request->validate(['email' => ['required', 'email']]);
        User::where('email', mb_strtolower($datos['email']))->first()?->enviarVerificacion();

        return response()->json(['mensaje' => 'Si el correo está registrado y sin confirmar, te enviamos un nuevo enlace.']);
    }

    /** GET /api/auth/yo */
    public function yo(Request $request)
    {
        return new UsuarioResource($request->user()->load('organizacion'));
    }

    /** POST /api/auth/logout → revoca el token usado en la petición */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Token revocado.']);
    }

    private function conToken(User $usuario, string $dispositivo, int $estado = 200)
    {
        return response()->json([
            'token' => $usuario->createToken($dispositivo)->plainTextToken,
            'tipo' => 'Bearer',
            'usuario' => new UsuarioResource($usuario),
            'siguiente_paso' => $usuario->tieneOrganizacionActiva() ? null
                : ($usuario->organizacion_id ? 'La organización aún no habilita el acceso.' : 'Únete con POST /api/organizacion/unirse o registra una con POST /api/organizaciones.'),
        ], $estado);
    }

    /**
     * Si la cuenta con ese correo nunca confirmó el correo, quien la creó no
     * demostró que era suyo: Google sí lo demuestra, así que se confirma y se
     * borra esa contraseña para que nadie más pueda entrar con ella.
     */
    private function vincularGoogle(User $usuario, string $googleId): void
    {
        $datos = ['google_id' => $usuario->google_id ?? $googleId];
        if (! $usuario->hasVerifiedEmail()) {
            $datos += ['email_verified_at' => now(), 'password' => null];
        }
        $usuario->forceFill($datos)->save();
    }
}
