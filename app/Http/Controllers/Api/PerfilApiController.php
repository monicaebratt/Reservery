<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Editar el perfil propio por API
class PerfilApiController extends Controller
{
    // GET /api/perfil
    public function show(Request $request)
    {
        return new UsuarioResource($request->user()->load('organizacion'));
    }

    // PUT /api/perfil (o POST con _method=PUT si trae foto)
    public function update(Request $request)
    {
        $usuario = $request->user();
        $datos = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('users')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            if ($usuario->foto && ! str_starts_with($usuario->foto, 'http')) {
                Storage::disk('public')->delete($usuario->foto);
            }
            $datos['foto'] = $request->file('foto')->store('fotos', 'public');
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

        return new UsuarioResource($usuario->fresh('organizacion'));
    }
}
