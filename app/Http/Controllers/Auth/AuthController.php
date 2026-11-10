<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/** Registro e inicio de sesión de usuarios y administradores (RF01, HU01, HU02). */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // HU02: el mensaje no revela si falló el correo o la contraseña
        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'El correo o la contraseña no son correctos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['codigo' => $request->query('codigo')]);
    }

    /**
     * HU01: correo y contraseña válidos, sin duplicados, y queda autenticado.
     * Se une a su organización con el código de invitación o porque su correo
     * fue invitado (RF01.2). Si no trae ninguno, elige después en la bienvenida.
     */
    public function register(Request $request, OrganizacionService $organizaciones)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'codigo' => ['nullable', 'string', 'max:12'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $usuario = DB::transaction(function () use ($datos, $organizaciones) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => mb_strtolower($datos['email']),
                'telefono' => $datos['telefono'] ?? null,
                'password' => $datos['password'],
                'rol' => 'usuario',
            ]);

            filled($datos['codigo'] ?? null)
                ? $organizaciones->unirseConCodigo($usuario, $datos['codigo'])
                : $organizaciones->unirsePorInvitacion($usuario);

            return $usuario->fresh();
        });

        $usuario->enviarVerificacion();
        Auth::login($usuario);
        $request->session()->regenerate();

        // La cuenta no se puede usar hasta confirmar el correo
        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Cerraste sesión correctamente.');
    }
}
