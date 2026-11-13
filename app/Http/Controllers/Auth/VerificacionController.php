<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Organizaciones\OrganizacionService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

// Confirmación del correo
class VerificacionController extends Controller
{
    // Pantalla que ve quien aún no confirma su correo
    public function aviso(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verificar-correo', ['email' => $request->user()->email]);
    }

    // Enlace firmado que llega por correo. Funciona aunque no haya sesión iniciada
    public function verificar(Request $request, string $id, string $hash, OrganizacionService $organizaciones)
    {
        $usuario = User::findOrFail($id);
        abort_unless(hash_equals(sha1($usuario->getEmailForVerification()), $hash), 403);

        if (! $usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
            event(new Verified($usuario));
        }
        $org = $organizaciones->aplicarInvitacionVerificada($usuario);

        $mensaje = $org ? "Correo confirmado. Ya eres parte de {$org->nombre}." : 'Correo confirmado. ¡Gracias!';

        return $request->user()?->is($usuario)
            ? redirect()->route($usuario->fresh()->tieneOrganizacionActiva() ? 'dashboard' : 'bienvenida')->with('ok', $mensaje)
            : redirect()->route('login')->with('ok', $mensaje.' Ya puedes iniciar sesión.');
    }

    // Vuelve a enviar el correo de confirmación
    public function reenviar(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('ok', 'Tu correo ya está confirmado.');
        }
        $request->user()->enviarVerificacion();

        return back()->with('ok', 'Te enviamos de nuevo el enlace para confirmar tu correo.');
    }
}
