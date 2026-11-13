<?php

namespace App\Services\Reservas;

use App\Exceptions\NoDisponibleException;
use App\Exceptions\TransicionInvalidaException;
use App\Models\ObjetoReservable;
use App\Models\Proposito;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Notificaciones\NotificacionService;
use App\Services\Prioridad\PrioridadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Casos de uso de la reserva. Lo usan por igual los controladores web y los de la API
class ReservaService
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private PrioridadService $prioridad,
        private NotificacionService $notificaciones,
    ) {
    }

    // Crea una solicitud de reserva simple o compuesta
    public function solicitar(User $usuario, array $datos): Reserva
    {
        // Solo los usuarios reservan, el admin solo gestiona
        if ($usuario->esAdmin()) {
            throw new AuthorizationException('Los administradores gestionan las reservas pero no las realizan. Usa una cuenta de usuario.');
        }

        $reserva = DB::transaction(function () use ($usuario, $datos) {
            // Bloquea las filas para que dos personas no reserven lo mismo a la vez
            $objetos = $this->cargarObjetos($datos['objetos'], bloquear: true);
            $this->validarPaquete($objetos);
            $proposito = Proposito::findOrFail($datos['proposito_id']);

            $resultado = $this->disponibilidad->verificar($objetos, $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin']);

            $afectadas = collect();
            if (! $resultado->disponible()) {
                // ¿solo choca con reservas de menor prioridad? Entonces puede desplazarlas
                $afectadas = $resultado->soloChocaConReservas()
                    ? $this->prioridad->desplazables($proposito, $resultado->reservasEnConflicto())
                    : null;

                if (! $afectadas || $afectadas->isEmpty()) {
                    throw new NoDisponibleException($resultado, $datos);
                }
            }

            $reserva = Reserva::create([
                'organizacion_id' => $usuario->organizacion_id,
                'user_id' => $usuario->id,
                'proposito_id' => $proposito->id,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'hora_fin' => $datos['hora_fin'],
                'motivo' => $datos['motivo'],
            ]);
            $reserva->objetos()->sync($objetos->pluck('id'));
            $reserva->registrar('creada', 'Solicitud para: '.$objetos->pluck('nombre')->join(', ').'.', $usuario);

            // Propone el reemplazo y avisa a los afectados con alternativas
            if ($afectadas->isNotEmpty()) {
                $this->prioridad->proponerReemplazos($reserva->load('proposito'), $afectadas);
            }

            return $reserva;
        });

        $reserva->load(['objetos', 'proposito', 'usuario', 'organizacion']);

        $this->notificaciones->enviar(
            $this->notificaciones->administradoresDe($reserva->organizacion_id, $usuario),
            'nueva_solicitud',
            'Nueva solicitud de reserva',
            "{$usuario->name} pidió {$reserva->objetos->pluck('nombre')->join(', ')} el {$reserva->fecha->format('d/m/Y')} de {$reserva->horario}.",
            $reserva,
        );

        // Si no se exige aprobación, se aprueba sola
        if (! $reserva->organizacion->requiere_aprobacion && $reserva->reemplazosPropuestos()->doesntExist()) {
            $reserva->aprobar(null, 'Aprobación automática: la organización no exige revisión.');
        }

        return $reserva->fresh(['objetos', 'proposito', 'usuario']);
    }

    // Edita una reserva pendiente volviendo a validar la agenda
    public function actualizar(Reserva $reserva, User $usuario, array $datos): Reserva
    {
        $this->autorizarDueñoOAdmin($reserva, $usuario);

        if (! $reserva->puedeEditarse()) {
            throw new TransicionInvalidaException("Una reserva {$reserva->estado} ya no se puede editar.");
        }

        $antes = $reserva->load('objetos')->objetos->pluck('nombre')->join(', ').' · '.$reserva->fecha->format('d/m/Y').' '.$reserva->horario;
        $objetosAntes = $reserva->objetos->pluck('id')->all();
        $slotAntes = [$reserva->fecha->toDateString(), $reserva->hora_inicio, $reserva->hora_fin];

        DB::transaction(function () use ($reserva, $usuario, $datos, $antes) {
            $objetos = $this->cargarObjetos($datos['objetos'], bloquear: true);
            $this->validarPaquete($objetos);

            $resultado = $this->disponibilidad->verificar($objetos, $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], [$reserva->id]);
            if (! $resultado->disponible()) {
                throw new NoDisponibleException($resultado, $datos);
            }

            $reserva->update(collect($datos)->only(['proposito_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'])->all());
            $reserva->objetos()->sync($objetos->pluck('id'));
            $reserva->load('objetos');
            $despues = $reserva->objetos->pluck('nombre')->join(', ').' · '.$reserva->fecha->format('d/m/Y').' '.$reserva->horario;
            $reserva->registrar('editada', "Antes: {$antes}. Ahora: {$despues}.", $usuario);
        });

        // El horario que dejó libre puede servirle a alguien en lista de espera
        app(\App\Services\ListaEspera\ListaEsperaService::class)->procesarLiberacion($objetosAntes, ...$slotAntes);

        return $reserva->fresh(['objetos', 'proposito', 'usuario']);
    }

    // Aplica una acción del ciclo de vida
    public function cambiarEstado(Reserva $reserva, string $accion, User $usuario, ?string $motivo = null): Reserva
    {
        if (! in_array($accion, Reserva::ACCIONES, true)) {
            throw new TransicionInvalidaException("La acción {$accion} no existe.");
        }

        if ($accion === 'cancelar') {
            $this->autorizarDueñoOAdmin($reserva, $usuario);
        } elseif (! $usuario->esAdmin()) {
            throw new AuthorizationException("Solo un administrador puede {$accion} reservas.");
        }

        DB::transaction(fn () => $reserva->{$accion}($usuario, $motivo));

        return $reserva->fresh(['objetos', 'proposito', 'usuario']);
    }

    // Elimina una reserva. Si estaba activa, primero se cancela para liberar el horario
    public function eliminar(Reserva $reserva, User $usuario): void
    {
        $this->autorizarDueñoOAdmin($reserva, $usuario);

        if (in_array($reserva->estado, Reserva::ACTIVOS, true)) {
            $reserva->cancelar($usuario, 'Cancelada al eliminar la reserva.');
        }

        $reserva->delete();
    }

    // Reglas de la reserva compuesta

    // Máximo un espacio, y sus recursos deben ir con él
    public function validarPaquete(Collection $objetos): void
    {
        $espacios = $objetos->where('tipo', 'espacio');

        if ($espacios->count() > 1) {
            throw ValidationException::withMessages(['espacio_id' => 'Una reserva solo puede incluir un espacio.']);
        }

        if ($espacio = $espacios->first()) {
            $permitidos = $espacio->recursosAsociados()->pluck('objetos_reservables.id');
            $invalidos = $objetos->where('tipo', 'recurso')->reject(fn ($r) => $permitidos->contains($r->id));

            if ($invalidos->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'recursos' => $invalidos->pluck('nombre')->join(', ')." no se puede reservar con {$espacio->nombre}.",
                ]);
            }
        }
    }

    public function cargarObjetos(array $ids, bool $bloquear = false): Collection
    {
        $objetos = ObjetoReservable::with(['horarios', 'organizacion'])
            ->whereIn('id', array_unique($ids))
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->get();

        if ($objetos->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['objetos' => 'Algún espacio o recurso no existe en tu organización.']);
        }

        return $objetos;
    }

    private function autorizarDueñoOAdmin(Reserva $reserva, User $usuario): void
    {
        if (! $usuario->esAdmin() && $reserva->user_id !== $usuario->id) {
            throw new AuthorizationException('Esta reserva no es tuya.');
        }
    }
}
