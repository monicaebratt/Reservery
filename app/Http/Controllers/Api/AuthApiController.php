<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        return $this->conToken($usuario, $datos['dispositivo'] ?? 'api', 201);
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
        $usuario->forceFill(['google_id' => $usuario->google_id ?? $google->getId()])->save();

        if (! $usuario->organizacion_id) {
            filled($datos['codigo'] ?? null)
                ? $organizaciones->unirseConCodigo($usuario, $datos['codigo'])
                : $organizaciones->unirsePorInvitacion($usuario);
        }

        return $this->conToken($usuario->fresh('organizacion'), 'google');
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
}
