<?php

namespace App\Http\Controllers\Organizacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizacionRequest;
use App\Models\Organizacion;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

// Registro y configuración de la organización
class OrganizacionController extends Controller
{
    public function __construct(private OrganizacionService $organizaciones)
    {
    }

    // Formulario público: datos de la organización + cuenta que la administra
    public function create()
    {
        return view('organizacion.create', ['tipos' => Organizacion::TIPOS]);
    }

    public function store(OrganizacionRequest $request)
    {
        $cuenta = $request->validate([
            'cuenta_nombre' => ['required', 'string', 'max:100'],
            'cuenta_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['cuenta_nombre' => 'nombre del responsable', 'cuenta_email' => 'correo del responsable']);

        $org = DB::transaction(function () use ($request, $cuenta) {
            $usuario = User::create([
                'name' => $cuenta['cuenta_nombre'],
                'email' => mb_strtolower($cuenta['cuenta_email']),
                'password' => $cuenta['password'],
            ]);
            $org = $this->organizaciones->registrar($this->datosConLogo($request), $usuario);
            Auth::login($usuario);

            return $org;
        });

        $request->session()->regenerate();
        $request->user()->enviarVerificacion();

        // Hasta confirmar el correo no puede configurar nada
        return redirect()->route('verification.notice')
            ->with('ok', "{$org->nombre} quedó registrada. Confirma tu correo para configurarla.");
    }

    // Quien ya tiene cuenta (por ejemplo, entró con Google) registra su organización
    public function storeParaCuenta(OrganizacionRequest $request)
    {
        $org = $this->organizaciones->registrar($this->datosConLogo($request), $request->user());

        return redirect()->route('organizacion.edit')
            ->with('ok', "{$org->nombre} quedó registrada. Revisa la configuración y habilita el acceso de tus miembros.");
    }

    public function edit(Request $request)
    {
        return view('organizacion.edit', [
            'org' => $request->user()->organizacion,
            'tipos' => Organizacion::TIPOS,
            'dias' => Organizacion::DIAS,
        ]);
    }

    public function update(OrganizacionRequest $request)
    {
        $org = $request->user()->organizacion;
        $this->organizaciones->configurar($org, $this->datosConLogo($request, $org));

        return back()->with('ok', 'Configuración guardada.');
    }

    public function habilitar(Request $request)
    {
        $org = $this->organizaciones->habilitar($request->user()->organizacion);

        return redirect()->route('miembros.index')
            ->with('ok', "¡Listo! {$org->nombre} ya puede recibir miembros con el código {$org->codigo_invitacion} o por invitación.");
    }

    public function regenerarCodigo(Request $request)
    {
        $org = $request->user()->organizacion;
        $org->update(['codigo_invitacion' => Organizacion::nuevoCodigo()]);

        return back()->with('ok', "Nuevo código de invitación: {$org->codigo_invitacion}. El anterior ya no sirve.");
    }

    private function datosConLogo(OrganizacionRequest $request, ?Organizacion $org = null): array
    {
        $datos = $request->datos();

        if ($request->hasFile('logo')) {
            if ($org?->logo) {
                Storage::disk('public')->delete($org->logo);
            }
            $datos['logo'] = $request->file('logo')->store('logos', 'public');
        }

        return $datos;
    }
}
