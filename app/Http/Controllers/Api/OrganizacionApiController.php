<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizacionRequest;
use App\Http\Resources\OrganizacionResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Invitacion;
use App\Models\Organizacion;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

// Por API
class OrganizacionApiController extends Controller
{
    public function __construct(private OrganizacionService $organizaciones)
    {
    }

    // Registra la organización junto con su cuenta
    public function store(OrganizacionRequest $request)
    {
        $usuarioActual = auth('sanctum')->user();

        $resultado = DB::transaction(function () use ($request, $usuarioActual) {
            if ($usuarioActual) {
                return ['org' => $this->organizaciones->registrar($request->datos(), $usuarioActual), 'usuario' => $usuarioActual, 'token' => null];
            }

            $cuenta = $request->validate([
                'cuenta_nombre' => ['required', 'string', 'max:100'],
                'cuenta_email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ]);
            $usuario = User::create(['name' => $cuenta['cuenta_nombre'], 'email' => mb_strtolower($cuenta['cuenta_email']), 'password' => $cuenta['password']]);
            $org = $this->organizaciones->registrar($request->datos(), $usuario);
            $usuario->enviarVerificacion();

            // Sin token: primero debe confirmar el correo
            return ['org' => $org, 'usuario' => $usuario, 'token' => null];
        });

        $resultado['usuario']->refresh();

        return response()->json([
            'organizacion' => new OrganizacionResource($resultado['org']->fresh()),
            'usuario' => new UsuarioResource($resultado['usuario']),
            'token' => $resultado['token'],
            'siguiente_paso' => $resultado['token'] === null && ! $usuarioActual
                ? 'Confirma tu correo con el enlace que te enviamos, inicia sesión (POST /api/auth/login) y luego configura y habilita la organización.'
                : 'Revisa la configuración (PUT /api/organizacion) y habilita el acceso (POST /api/organizacion/habilitar).',
        ], 201);
    }

    // POST /api/organizacion/unirse {codigo}
    public function unirse(Request $request)
    {
        $request->validate(['codigo' => ['required', 'string', 'max:12']]);
        $org = $this->organizaciones->unirseConCodigo($request->user(), $request->codigo);

        return response()->json(['mensaje' => "Te uniste a {$org->nombre}.", 'organizacion' => new OrganizacionResource($org)]);
    }

    // GET /api/organizacion
    public function show(Request $request)
    {
        $org = $request->user()->organizacion;
        abort_unless($org, 404, 'No perteneces a ninguna organización.');

        return new OrganizacionResource($org);
    }

    // PUT /api/organizacion — configurarDatosBasicos()
    public function update(OrganizacionRequest $request)
    {
        $org = $request->user()->organizacion;
        $datos = $request->datos();
        if ($request->hasFile('logo')) {
            $datos['logo'] = $request->file('logo')->store('logos', 'public');
        }

        return new OrganizacionResource($this->organizaciones->configurar($org, $datos));
    }

    // POST /api/organizacion/habilitar
    public function habilitar(Request $request)
    {
        return new OrganizacionResource($this->organizaciones->habilitar($request->user()->organizacion));
    }

    // POST /api/organizacion/codigo — genera un código de invitación nuevo
    public function regenerarCodigo(Request $request)
    {
        $org = $request->user()->organizacion;
        $org->update(['codigo_invitacion' => Organizacion::nuevoCodigo()]);

        return response()->json(['codigo_invitacion' => $org->codigo_invitacion]);
    }

    // GET /api/organizacion/miembros
    public function miembros(Request $request)
    {
        return UsuarioResource::collection($request->user()->organizacion->miembros()->orderBy('name')->get());
    }

    // Cambia el rol de un miembro (admin o usuario)
    public function cambiarRol(Request $request, User $miembro)
    {
        $request->validate(['rol' => ['required', 'in:admin,usuario']]);
        $org = $request->user()->organizacion;
        $request->rol === 'admin' ? $org->asignarRolAdmin($miembro) : $org->revocarRolAdmin($miembro);

        return new UsuarioResource($miembro->fresh());
    }

    // DELETE /api/organizacion/miembros/{id}
    public function quitarMiembro(Request $request, User $miembro)
    {
        abort_unless($miembro->organizacion_id === $request->user()->organizacion_id, 404);
        abort_if($miembro->esOrganizacion(), 422, 'No se puede quitar la cuenta de la organización.');
        $miembro->tokens()->delete();
        $miembro->update(['organizacion_id' => null, 'rol' => 'usuario']);

        return response()->json(['mensaje' => "{$miembro->name} ya no pertenece a la organización."]);
    }

    // GET /api/organizacion/invitaciones
    public function invitaciones(Request $request)
    {
        return response()->json(['data' => $request->user()->organizacion->invitaciones()->whereNull('aceptada_at')->latest()->get(['id', 'email', 'rol', 'created_at'])]);
    }

    // POST /api/organizacion/invitaciones {email, rol}
    public function invitar(Request $request)
    {
        $datos = $request->validate(['email' => ['required', 'email'], 'rol' => ['required', 'in:admin,usuario']]);
        $r = $this->organizaciones->invitar($request->user()->organizacion, $datos['email'], $datos['rol'], $request->user());

        return response()->json([
            'estado' => $r['estado'],
            'usuario' => isset($r['usuario']) ? new UsuarioResource($r['usuario']) : null,
            'invitacion' => $r['invitacion'] ?? null,
        ], $r['estado'] === 'invitado' ? 201 : 200);
    }

    // DELETE /api/organizacion/invitaciones/{id}
    public function quitarInvitacion(Invitacion $invitacion)
    {
        $invitacion->delete();

        return response()->json(['mensaje' => 'Invitación anulada.']);
    }
}
