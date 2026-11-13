<?php

namespace App\Services\ListaEspera;

use App\Exceptions\TransicionInvalidaException;
use App\Models\ListaEspera;
use App\Models\ObjetoReservable;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Notificaciones\NotificacionService;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Reservas\ReservaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Lista de espera por orden de llegada
class ListaEsperaService
{
    public function __construct(
        private DisponibilidadService $disponibilidad,
        private NotificacionService $notificaciones,
    ) {
    }

    // Inscribir del diagrama de clases
    public function inscribir(User $usuario, array $datos): ListaEspera
    {
        if ($usuario->esAdmin()) {
            throw new AuthorizationException('La lista de espera es para usuarios que reservan.');
        }

        $objetos = ObjetoReservable::whereIn('id', $datos['objetos'])->get();
        $principal = $objetos->firstWhere('tipo', 'espacio') ?? $objetos->first();

        $repetida = ListaEspera::activas()
            ->where('user_id', $usuario->id)
            ->where('objeto_reservable_id', $principal->id)
            ->whereDate('fecha', $datos['fecha'])
            ->where('hora_inicio', $datos['hora_inicio'])
            ->exists();

        if ($repetida) {
            throw ValidationException::withMessages(['objetos' => 'Ya estás en la lista de espera para ese horario.']);
        }

        $entrada = ListaEspera::create([
            'organizacion_id' => $usuario->organizacion_id,
            'user_id' => $usuario->id,
            'objeto_reservable_id' => $principal->id,
            'proposito_id' => $datos['proposito_id'],
            'objetos' => $objetos->pluck('id')->all(),
            'fecha' => $datos['fecha'],
            'hora_inicio' => $datos['hora_inicio'],
            'hora_fin' => $datos['hora_fin'],
            'motivo' => $datos['motivo'],
        ]);

        $this->notificaciones->enviar($usuario, 'lista_espera', 'Te anotamos en la lista de espera',
            "Quedaste en la posición {$entrada->posicion} para {$principal->nombre} el ".$entrada->fecha->format('d/m/Y')." de ".substr($entrada->hora_inicio, 0, 5).' a '.substr($entrada->hora_fin, 0, 5).'. Te avisaremos si se libera.',
            null, url('/lista-espera'));

        return $entrada;
    }

    public function cancelar(ListaEspera $entrada, User $usuario): void
    {
        if ($entrada->user_id !== $usuario->id && ! $usuario->esAdmin()) {
            throw new AuthorizationException('Esta inscripción no es tuya.');
        }

        $estabaNotificada = $entrada->estado === 'notificada';
        $entrada->update(['estado' => 'cancelada']);

        // Si tenía el turno, se lo pasamos al siguiente
        if ($estabaNotificada) {
            $this->procesarLiberacion($entrada->objetos, $entrada->fecha->toDateString(), $entrada->hora_inicio, $entrada->hora_fin, $entrada->organizacion_id);
        }
    }

    // El usuario avisado convierte su turno en una reserva
    public function convertir(ListaEspera $entrada, User $usuario): Reserva
    {
        if ($entrada->user_id !== $usuario->id) {
            throw new AuthorizationException('Esta inscripción no es tuya.');
        }
        if (! in_array($entrada->estado, ['esperando', 'notificada'], true)) {
            throw new TransicionInvalidaException('Esta inscripción ya no está activa.');
        }

        return DB::transaction(function () use ($entrada, $usuario) {
            $reserva = app(ReservaService::class)->solicitar($usuario, [
                'proposito_id' => $entrada->proposito_id,
                'fecha' => $entrada->fecha->toDateString(),
                'hora_inicio' => substr($entrada->hora_inicio, 0, 5),
                'hora_fin' => substr($entrada->hora_fin, 0, 5),
                'motivo' => $entrada->motivo,
                'objetos' => $entrada->objetos,
            ]);

            $entrada->update(['estado' => 'atendida', 'reserva_id' => $reserva->id]);
            $reserva->registrar('desde_lista_espera', "Creada desde la lista de espera (inscripción #{$entrada->id}).", $usuario);

            return $reserva;
        });
    }

    // Avisa a la lista de espera cuando algo se libera
    public function procesarLiberacion(array $objetoIds, string $fecha, string $inicio, string $fin, ?int $organizacionId = null): int
    {
        $candidatas = ListaEspera::withoutGlobalScopes()
            ->with(['usuario', 'objeto'])
            ->when($organizacionId, fn ($q) => $q->where('organizacion_id', $organizacionId))
            ->activas()
            ->whereDate('fecha', $fecha)
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->filter(fn ($e) => array_intersect($e->objetos, $objetoIds));

        // Turnos ya avisados que aún no se han usado: tienen prelación
        $tomadas = $candidatas->where('estado', 'notificada')->values();
        $avisadas = 0;

        foreach ($candidatas->where('estado', 'esperando') as $entrada) {
            $chocaConTomada = $tomadas->contains(fn ($t) => array_intersect($t->objetos, $entrada->objetos)
                && $t->hora_inicio < $entrada->hora_fin && $t->hora_fin > $entrada->hora_inicio);
            if ($chocaConTomada) {
                continue;
            }

            $objetos = ObjetoReservable::withoutGlobalScopes()->with(['horarios', 'organizacion'])->whereIn('id', $entrada->objetos)->get();
            $libre = $this->disponibilidad->verificar($objetos, $fecha, $entrada->hora_inicio, $entrada->hora_fin)->disponible();
            if (! $libre) {
                continue;
            }

            $entrada->update(['estado' => 'notificada', 'notificada_at' => now()]);
            $tomadas->push($entrada);
            $avisadas++;

            $this->notificaciones->enviar($entrada->usuario, 'lista_espera', '¡Se liberó tu horario!',
                "{$entrada->objeto->nombre} quedó libre el ".$entrada->fecha->format('d/m/Y').' de '.substr($entrada->hora_inicio, 0, 5).' a '.substr($entrada->hora_fin, 0, 5).'. Eras el siguiente en la fila: resérvalo antes que otra persona.',
                null, url('/lista-espera'));
        }

        return $avisadas;
    }

    // Las inscripciones de días que ya pasaron quedan vencidas
    public function vencerPasadas(): int
    {
        return ListaEspera::withoutGlobalScopes()->activas()->whereDate('fecha', '<', today())->update(['estado' => 'vencida']);
    }
}
