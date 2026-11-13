<?php

namespace App\Services\Organizaciones;

use App\Models\Invitacion;
use App\Models\Organizacion;
use App\Models\User;
use App\Notifications\InvitacionOrganizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

// Registro y configuración de la organización y gestión de roles de sus miembros
class OrganizacionService
{
    // RegistrarOrganizacion: crea la organización y su cuenta
    public function registrar(array $datosOrganizacion, User $cuenta): Organizacion
    {
        if ($cuenta->organizacion_id) {
            throw ValidationException::withMessages(['nombre' => 'Tu cuenta ya pertenece a una organización.']);
        }

        return DB::transaction(function () use ($datosOrganizacion, $cuenta) {
            $org = Organizacion::create($datosOrganizacion + ['configurada' => false]);
            $cuenta->update(['organizacion_id' => $org->id, 'rol' => 'organizacion']);

            return $org;
        });
    }

    // configurarDatosBasicos()
    public function configurar(Organizacion $org, array $datos): Organizacion
    {
        $org->update($datos);

        return $org->fresh();
    }

    // Habilita el acceso de usuarios y administradores
    public function habilitar(Organizacion $org): Organizacion
    {
        $faltan = collect([
            'nombre' => $org->nombre,
            'tipo' => $org->tipo,
            'ciudad' => $org->ciudad,
            'correo de contacto' => $org->correo_contacto,
            'días operativos' => $org->dias_operativos,
        ])->filter(fn ($v) => blank($v))->keys();

        if ($faltan->isNotEmpty()) {
            throw ValidationException::withMessages([
                'configurada' => 'Antes de habilitar el acceso completa: '.$faltan->join(', ').'.',
            ]);
        }

        $org->update(['configurada' => true]);

        // Las invitaciones de usuarios que ya tenían cuenta se aplican ahora
        foreach ($org->invitaciones()->whereNull('aceptada_at')->get() as $inv) {
            if ($u = User::where('email', $inv->email)->whereNull('organizacion_id')->whereNotNull('email_verified_at')->first()) {
                $this->aplicarInvitacion($u, $inv);
            }
        }

        return $org->fresh();
    }

    // Un usuario sin organización se une con el código de invitación
    public function unirseConCodigo(User $usuario, string $codigo): Organizacion
    {
        if ($usuario->organizacion_id) {
            throw ValidationException::withMessages(['codigo' => 'Ya perteneces a una organización.']);
        }

        $org = Organizacion::where('codigo_invitacion', strtoupper(trim($codigo)))->first();

        if (! $org) {
            throw ValidationException::withMessages(['codigo' => 'Ese código de invitación no existe.']);
        }
        if (! $org->configurada) {
            throw ValidationException::withMessages(['codigo' => 'Esa organización todavía no habilita el acceso de sus miembros.']);
        }

        // El rol de la invitación solo se aplica con el correo confirmado
        $invitacion = $org->invitaciones()->where('email', mb_strtolower($usuario->email))->whereNull('aceptada_at')->first();
        if ($invitacion && $usuario->hasVerifiedEmail()) {
            $this->aplicarInvitacion($usuario, $invitacion);
        } else {
            $usuario->update(['organizacion_id' => $org->id, 'rol' => 'usuario']);
        }

        return $org;
    }

    // Si su correo tiene una invitación pendiente, lo une
    public function unirsePorInvitacion(User $usuario): ?Organizacion
    {
        // Sin correo confirmado, cualquiera podría registrarse con un correo ajeno
        if ($usuario->organizacion_id || ! $usuario->hasVerifiedEmail()) {
            return null;
        }

        $invitacion = Invitacion::withoutGlobalScopes()
            ->where('email', mb_strtolower($usuario->email))
            ->whereNull('aceptada_at')
            ->whereHas('organizacion', fn ($q) => $q->where('configurada', true))
            ->oldest()
            ->first();

        return $invitacion ? $this->aplicarInvitacion($usuario, $invitacion) : null;
    }

    // Asigna un rol a un correo
    public function invitar(Organizacion $org, string $email, string $rol, User $por): array
    {
        $email = mb_strtolower(trim($email));
        $usuario = User::where('email', $email)->first();

        if ($usuario && $usuario->organizacion_id === $org->id) {
            $rol === 'admin' ? $org->asignarRolAdmin($usuario) : $org->revocarRolAdmin($usuario);

            return ['estado' => 'actualizado', 'usuario' => $usuario->fresh()];
        }

        if ($usuario && $usuario->organizacion_id) {
            throw ValidationException::withMessages(['email' => 'Ese correo ya pertenece a otra organización.']);
        }

        $invitacion = Invitacion::withoutGlobalScopes()->updateOrCreate(
            ['organizacion_id' => $org->id, 'email' => $email],
            ['rol' => $rol, 'invitado_por' => $por->id, 'aceptada_at' => null],
        );

        // Si ya tenía cuenta, entra de una vez
        if ($usuario && $org->configurada && $usuario->hasVerifiedEmail()) {
            $this->aplicarInvitacion($usuario, $invitacion);

            return ['estado' => 'unido', 'usuario' => $usuario->fresh()];
        }

        try {
            Notification::route('mail', $email)->notify(new InvitacionOrganizacion($org, $rol));
        } catch (\Throwable $e) {
            report($e);
        }

        return ['estado' => 'invitado', 'invitacion' => $invitacion];
    }

    // Al confirmar el correo: aplica la invitación pendiente de ese correo
    public function aplicarInvitacionVerificada(User $usuario): ?Organizacion
    {
        if (! $usuario->hasVerifiedEmail()) {
            return null;
        }

        $invitacion = Invitacion::withoutGlobalScopes()
            ->where('email', mb_strtolower($usuario->email))
            ->whereNull('aceptada_at')
            ->whereHas('organizacion', fn ($q) => $q->where('configurada', true))
            ->when($usuario->organizacion_id, fn ($q) => $q->where('organizacion_id', $usuario->organizacion_id))
            ->oldest()
            ->first();

        return $invitacion ? $this->aplicarInvitacion($usuario, $invitacion) : null;
    }

    private function aplicarInvitacion(User $usuario, Invitacion $invitacion): Organizacion
    {
        $usuario->update(['organizacion_id' => $invitacion->organizacion_id, 'rol' => $invitacion->rol]);
        $invitacion->update(['aceptada_at' => now()]);

        return $invitacion->organizacion;
    }
}
