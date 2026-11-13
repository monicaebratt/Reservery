<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// El usuario que inició sesión edita su propia información (: solo la suya)
class PerfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('perfil.edit', ['usuario' => $request->user()->load('organizacion')]);
    }

    public function update(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $this->borrarFoto($usuario->foto);
            $datos['foto'] = $request->file('foto')->store('fotos', 'public');
        } else {
            unset($datos['foto']);
        }

        // Si cambia el correo, debe volver a confirmarlo (seguridad de las invitaciones)
        if (isset($datos['email'])) {
            $datos['email'] = mb_strtolower($datos['email']);
            if ($datos['email'] !== $usuario->email) {
                $datos['email_verified_at'] = null;
            }
        }
        $usuario->update($datos);
        $usuario->enviarVerificacion();

        return back()->with('ok', 'Tu perfil se actualizó.');
    }

    // Si entró con Google puede crear contraseña sin la actual
    public function updatePassword(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'current_password' => [$usuario->password ? 'required' : 'nullable', $usuario->password ? 'current_password' : 'nullable'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $usuario->update(['password' => $request->password]);

        return back()->with('ok', 'Tu contraseña se guardó.');
    }

    public function destroyFoto(Request $request)
    {
        $usuario = $request->user();
        $this->borrarFoto($usuario->foto);
        $usuario->update(['foto' => null]);

        return back()->with('ok', 'Foto eliminada.');
    }

    private function borrarFoto(?string $foto): void
    {
        if ($foto && ! str_starts_with($foto, 'http')) {
            Storage::disk('public')->delete($foto);
        }
    }
}
