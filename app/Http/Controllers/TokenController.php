<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Tokens de la API y consola para probarla
class TokenController extends Controller
{
    public function __construct()
    {
        // Solo existe si se activa en el .env
        abort_unless(config('reservery.consola_api'), 404);
    }

    public function index(Request $request)
    {
        return view('api.consola', [
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:60']]);

        $token = $request->user()->createToken($datos['nombre']);

        // El token completo solo se puede ver una vez
        return back()->with('token_nuevo', $token->plainTextToken)
            ->with('ok', 'Token creado. Cópialo ahora: no se volverá a mostrar.');
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return back()->with('ok', 'Token revocado.');
    }
}
