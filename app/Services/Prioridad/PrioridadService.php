<?php

namespace App\Services\Prioridad;

use App\Exceptions\NoDisponibleException;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Proposito;
use App\Models\Reemplazo;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Notificaciones\NotificacionService;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Sustitucion\SustitucionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Priorización por propósito
class PrioridadService
{
    public function __construct(
        private SustitucionService $sustitucion,
        private NotificacionService $notificaciones,
        private DisponibilidadService $disponibilidad,
    ) {
    }

    // Reservas que se pueden desplazar por prioridad
    public function desplazables(Proposito $proposito, Collection $reservaIds): Collection
    {
        $reservas = Reserva::with('proposito')->whereIn('id', $reservaIds)->get();

        $todasMenores = $reservas->isNotEmpty()
            && $reservas->every(fn (Reserva $r) => $proposito->tieneMayorPrioridadQue($r->proposito));

        return $todasMenores ? $reservas : collect();
    }

    // Registra el reemplazo y avisa a los afectados
    public function proponerReemplazos(Reserva $prioritaria, Collection $afectadas): void
    {
        foreach ($afectadas as $afectada) {
            $afectada->loadMissing(['objetos', 'proposito', 'usuario']);

            // Alternativas para el afectado en su mismo horario
            $alternativas = $this->sustitucion->buscarAlternativas(
                $afectada->objetos->pluck('id')->all(),
                $afectada->fecha->toDateString(), $afectada->hora_inicio, $afectada->hora_fin,
                ignorar: [$afectada->id],
            );

            $reemplazo = Reemplazo::create([
                'reserva_prioritaria_id' => $prioritaria->id,
                'reserva_afectada_id' => $afectada->id,
                'alternativas' => $alternativas,
            ]);

            $detalle = "Propuesto por la reserva #{$prioritaria->id} ({$prioritaria->proposito->nombre}, de mayor prioridad).";
            $afectada->registrar('reemplazo_propuesto', $detalle, $prioritaria->usuario);
            $prioritaria->registrar('reemplazo_propuesto', "Desplazaría a la reserva #{$afectada->id} ({$afectada->proposito->nombre}).", $prioritaria->usuario);

            $this->notificaciones->enviar(
                $afectada->usuario,
                'reemplazo',
                'Tu reserva podría ser reemplazada',
                "Una solicitud de mayor prioridad ({$prioritaria->proposito->nombre}) necesita el mismo horario de tu reserva #{$afectada->id}. "
                    .(count($alternativas) ? 'Te ofrecemos '.count($alternativas).' alternativa(s): elige una antes de que el administrador decida.' : 'No encontramos alternativas equivalentes libres.'),
                $afectada,
                url('/reemplazos/'.$reemplazo->id),
            );
        }

        $this->notificaciones->enviar(
            $this->notificaciones->administradoresDe($prioritaria->organizacion_id),
            'conflicto_prioridad',
            'Conflicto de prioridad por resolver',
            "La reserva #{$prioritaria->id} ({$prioritaria->proposito->nombre}) desplazaría a {$afectadas->count()} reserva(s) de menor prioridad. Al aprobarla se confirma el reemplazo.",
            $prioritaria,
        );
    }

    // El afectado elige una alternativa y su reserva cambia de objeto
    public function aceptarAlternativa(Reemplazo $reemplazo, User $usuario, int $indice): Reemplazo
    {
        $afectada = $reemplazo->afectada()->with('objetos')->firstOrFail();
        $this->autorizarAfectado($reemplazo, $usuario);

        $alternativa = $reemplazo->alternativas[$indice] ?? null;
        if (! $alternativa) {
            throw new TransicionInvalidaException('Esa alternativa no existe.');
        }

        DB::transaction(function () use ($reemplazo, $afectada, $usuario, $alternativa) {
            $objetos = \App\Models\ObjetoReservable::with('horarios', 'organizacion')
                ->whereIn('id', $alternativa['objetos'])->lockForUpdate()->get();

            // El paquete alternativo debe seguir libre completo
            $resultado = $this->disponibilidad->verificar($objetos, $afectada->fecha->toDateString(), $afectada->hora_inicio, $afectada->hora_fin, [$afectada->id]);
            if (! $resultado->disponible()) {
                throw new NoDisponibleException($resultado);
            }

            $antes = $afectada->objetos->pluck('nombre')->join(', ');
            $afectada->objetos()->sync($objetos->pluck('id'));
            $afectada->registrar('sustituida', "Cambió {$antes} por ".$objetos->pluck('nombre')->join(', ').' para ceder el horario a una reserva prioritaria.', $usuario);

            $reemplazo->update([
                'estado' => 'alternativa_aceptada',
                'alternativa_elegida' => $objetos->pluck('id')->all(),
                'respondido_at' => now(),
            ]);
        });

        $this->notificaciones->enviar(
            $this->notificaciones->administradoresDe($afectada->organizacion_id),
            'sustitucion',
            'Alternativa aceptada',
            "{$usuario->name} aceptó una alternativa para su reserva #{$afectada->id}; el conflicto con la #{$reemplazo->reserva_prioritaria_id} quedó resuelto.",
            $reemplazo->prioritaria,
        );

        return $reemplazo->fresh();
    }

    // El afectado acepta ceder su horario sin alternativa
    public function aceptarSinAlternativa(Reemplazo $reemplazo, User $usuario): Reemplazo
    {
        $this->autorizarAfectado($reemplazo, $usuario);
        $reemplazo->update(['estado' => 'sin_alternativa', 'respondido_at' => now()]);
        $reemplazo->afectada->registrar('reemplazo_aceptado', 'El usuario aceptó ceder su horario sin alternativa.', $usuario);

        return $reemplazo->fresh();
    }

    // Al aprobarse la reserva prioritaria: se confirman los reemplazos
    public function confirmar(Reserva $prioritaria, ?User $responsable): void
    {
        foreach ($prioritaria->reemplazosPropuestos()->with('afectada')->get() as $reemplazo) {
            if (! $reemplazo->estaAbierto()) {
                continue;
            }

            $afectada = $reemplazo->afectada;
            if ($reemplazo->estado !== 'alternativa_aceptada' && in_array($afectada->estado, Reserva::ACTIVOS, true)) {
                // La cancelación pasa por el patrón State y notifica al afectado
                $afectada->cancelar($responsable, "Reemplazada por la reserva #{$prioritaria->id} de mayor prioridad ({$prioritaria->proposito->nombre}).");
            }

            $reemplazo->update(['estado' => 'confirmado']);
        }
    }

    // Si la prioritaria se rechaza o cancela: los afectados conservan su reserva
    public function descartar(Reserva $prioritaria): void
    {
        foreach ($prioritaria->reemplazosPropuestos()->with('afectada.usuario')->get() as $reemplazo) {
            if (! $reemplazo->estaAbierto()) {
                continue;
            }

            $reemplazo->update(['estado' => 'descartado']);
            $afectada = $reemplazo->afectada;
            $afectada->registrar('reemplazo_descartado', "La reserva #{$prioritaria->id} ya no la desplaza.");

            $this->notificaciones->enviar(
                $afectada->usuario,
                'reemplazo',
                'Tu reserva se mantiene',
                "La solicitud prioritaria que chocaba con tu reserva #{$afectada->id} fue {$prioritaria->estado}.",
                $afectada,
            );
        }
    }

    private function autorizarAfectado(Reemplazo $reemplazo, User $usuario): void
    {
        if ($reemplazo->afectada->user_id !== $usuario->id) {
            throw new AuthorizationException('Este reemplazo no afecta a tus reservas.');
        }

        if ($reemplazo->estado !== 'propuesto') {
            throw new TransicionInvalidaException('Ya respondiste esta propuesta o ya se resolvió.');
        }
    }
}
