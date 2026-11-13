<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaPassword;

class PasswordController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    // Envía al correo el enlace para restablecer la contraseña
    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $estado = Password::sendResetLink($request->only('email'));

        return $estado === Password::RESET_LINK_SENT
            ? back()->with('ok', 'Te enviamos un enlace a tu correo para restablecer la contraseña.')
            : back()->withErrors(['email' => __($estado)])->onlyInput('email');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaPassword::min(8)],
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $estado === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', 'Tu contraseña se cambió. Ya puedes iniciar sesión.')
            : back()->withErrors(['email' => __($estado)]);
    }
}
