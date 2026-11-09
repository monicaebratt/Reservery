<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Inicio de sesión y registro con Google (Laravel Socialite).
 * Si el correo ya existe, se vincula; si no, se crea la cuenta.
 */
class GoogleController extends Controller
{
    public function redirect(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()->route('login')->with('error', 'El inicio con Google aún no está configurado (faltan GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el .env).');
        }

        // Para registrarse con el código de invitación de una organización
        session(['google.codigo' => $request->query('codigo')]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, OrganizacionService $organizaciones)
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'No pudimos iniciar sesión con Google. Inténtalo de nuevo.');
        }

        $usuario = User::where('google_id', $google->getId())
            ->orWhere('email', mb_strtolower($google->getEmail()))
            ->first();

        if ($usuario) {
            $usuario->forceFill([
                'google_id' => $usuario->google_id ?? $google->getId(),
                'foto' => $usuario->foto ?? $google->getAvatar(),
                'email_verified_at' => $usuario->email_verified_at ?? now(),
            ])->save();
        } else {
            $usuario = User::create([
                'name' => $google->getName() ?: $google->getNickname() ?: 'Usuario de Google',
                'email' => mb_strtolower($google->getEmail()),
                'google_id' => $google->getId(),
                'foto' => $google->getAvatar(),
                'email_verified_at' => now(),
                'rol' => 'usuario',
            ]);
        }

        // Unirse a su organización si trae código o si su correo fue invitado
        $codigo = session()->pull('google.codigo');
        if (! $usuario->organizacion_id) {
            try {
                filled($codigo)
                    ? $organizaciones->unirseConCodigo($usuario, $codigo)
                    : $organizaciones->unirsePorInvitacion($usuario);
            } catch (ValidationException $e) {
                session()->flash('error', collect($e->errors())->flatten()->first());
            }
        }

        Auth::login($usuario->fresh(), remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
