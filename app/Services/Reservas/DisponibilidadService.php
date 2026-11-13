<?php

namespace App\Services\Reservas;

use App\Models\Bloqueo;
use App\Models\ObjetoReservable;
use App\Models\Organizacion;
use App\Models\Reserva;
use App\Services\FestivosService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Revisa disponibilidad y arma la agenda del día
class DisponibilidadService
{
    public function __construct(private FestivosService $festivos)
    {
    }

    // Revisa todos los objetos de la solicitud a la vez
    public function verificar($objetos, string $fecha, string $inicio, string $fin, array $ignorarReservas = []): ResultadoDisponibilidad
    {
        $resultado = new ResultadoDisponibilidad();
        $objetos = collect($objetos);
        $inicio = substr($inicio, 0, 5);
        $fin = substr($fin, 0, 5);
        $dia = Carbon::parse($fecha)->dayOfWeekIso;

        if ($objetos->isEmpty()) {
            return $resultado;
        }

        if (Carbon::parse("{$fecha} {$inicio}")->isPast()) {
            $resultado->agregar('pasado', 'La fecha y hora de inicio ya pasaron.');
        }

        $org = $objetos->first()->organizacion ?? Organizacion::find($objetos->first()->organizacion_id);
        if ($org?->cerrar_en_festivos && ($festivo = $this->festivos->esFestivo($fecha))) {
            $resultado->agregar('festivo', "El {$fecha} es festivo ({$festivo}) y la organización no abre.");
        }

        foreach ($objetos as $objeto) {
            if (! $objeto->disponible) {
                $resultado->agregar('inhabilitado', "{$objeto->nombre} está inhabilitado.", $objeto);
                continue;
            }

            // Horario operativo
            $horario = $objeto->horarioDelDia($dia);
            if (! $horario) {
                $resultado->agregar('fuera_de_horario', "{$objeto->nombre} no abre ese día.", $objeto);
            } elseif (! $horario->cubre($inicio, $fin)) {
                $resultado->agregar('fuera_de_horario',
                    "{$objeto->nombre} solo atiende de ".substr($horario->hora_inicio, 0, 5).' a '.substr($horario->hora_fin, 0, 5).'.', $objeto);
            }

            // Bloqueos por mantenimiento o eventos
            $bloqueo = Bloqueo::withoutGlobalScopes()
                ->where('objeto_reservable_id', $objeto->id)
                ->queCruzan($fecha, $inicio, $fin)
                ->first();
            if ($bloqueo) {
                $resultado->agregar('bloqueado', "{$objeto->nombre} está bloqueado: {$bloqueo->motivo}.", $objeto);
            }

            // Otras reservas activas en el mismo horario
            $choques = Reserva::withoutGlobalScopes()->activas()
                ->queCruzan($fecha, $inicio, $fin)
                ->whereNotIn('reservas.id', $ignorarReservas)
                ->whereHas('objetos', fn ($q) => $q->where('objetos_reservables.id', $objeto->id))
                ->pluck('id')->all();
            if ($choques) {
                $resultado->agregar('ocupado', "{$objeto->nombre} ya está reservado en ese horario.", $objeto, $choques);
            }
        }

        return $resultado;
    }

    // Agenda de un día por objeto: horario, franjas ocupadas y franjas libres
    public function agenda(?array $objetoIds, string $fecha, ?string $tipo = null, bool $verDetalle = false): array
    {
        $dia = Carbon::parse($fecha)->dayOfWeekIso;

        $objetos = ObjetoReservable::query()
            ->with(['horarios', 'organizacion'])
            ->when($objetoIds, fn ($q) => $q->whereIn('id', $objetoIds))
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('tipo')->orderBy('nombre')
            ->get();

        if ($objetos->isEmpty()) {
            return [];
        }

        $org = $objetos->first()->organizacion;
        $festivo = $org->cerrar_en_festivos ? $this->festivos->esFestivo($fecha) : null;

        $reservas = Reserva::with(['objetos:id', 'proposito', 'usuario:id,name'])
            ->activas()->whereDate('fecha', $fecha)->get();
        $bloqueos = Bloqueo::whereIn('objeto_reservable_id', $objetos->pluck('id'))
            ->where('fecha_inicio', '<', "{$fecha} 23:59:59")
            ->where('fecha_fin', '>', "{$fecha} 00:00:00")
            ->get();

        return $objetos->map(function (ObjetoReservable $o) use ($dia, $fecha, $festivo, $reservas, $bloqueos, $verDetalle) {
            $horario = $o->horarioDelDia($dia);
            $abre = $horario ? substr($horario->hora_inicio, 0, 5) : null;
            $cierra = $horario ? substr($horario->hora_fin, 0, 5) : null;

            $ocupado = $reservas->filter(fn ($r) => $r->objetos->contains('id', $o->id))
                ->map(fn ($r) => array_filter([
                    'tipo' => 'reserva',
                    'inicio' => substr($r->hora_inicio, 0, 5),
                    'fin' => substr($r->hora_fin, 0, 5),
                    'estado' => $r->estado,
                    'proposito' => $r->proposito->nombre,
                    'color' => $r->proposito->color,
                    'reserva_id' => $verDetalle ? $r->id : null,
                    'solicitante' => $verDetalle ? $r->usuario->name : null,
                ], fn ($v) => $v !== null))
                ->values();

            $ocupado = $ocupado->concat($bloqueos->where('objeto_reservable_id', $o->id)->map(fn ($b) => [
                'tipo' => 'bloqueo',
                'inicio' => $b->fecha_inicio->toDateString() < $fecha ? '00:00' : $b->fecha_inicio->format('H:i'),
                'fin' => $b->fecha_fin->toDateString() > $fecha ? '23:59' : $b->fecha_fin->format('H:i'),
                'motivo' => $b->motivo,
            ])->values())->sortBy('inicio')->values();

            $cerrado = ! $o->disponible || ! $horario || $festivo;

            return [
                'id' => $o->id,
                'nombre' => $o->nombre,
                'tipo' => $o->tipo,
                'categoria' => $o->categoria,
                'capacidad' => $o->capacidad,
                'ubicacion' => $o->ubicacion,
                'imagen_url' => $o->imagen_url,
                'disponible' => $o->disponible,
                'horario' => $horario ? ['inicio' => $abre, 'fin' => $cierra] : null,
                'festivo' => $festivo,
                'ocupado' => $ocupado->all(),
                'libre' => $cerrado ? [] : $this->franjasLibres($this->desdeAhora($fecha, $abre), $cierra, $ocupado),
            ];
        })->all();
    }

    // Si la fecha es hoy, lo que ya pasó no cuenta como libre
    private function desdeAhora(string $fecha, string $abre): string
    {
        if ($fecha !== today()->toDateString()) {
            return $abre;
        }

        return max($abre, now()->format('H:i'));
    }

    // Resta las franjas ocupadas al horario de apertura
    private function franjasLibres(string $abre, string $cierra, Collection $ocupado): array
    {
        $libres = [];
        $cursor = $abre;

        foreach ($ocupado->sortBy('inicio') as $o) {
            if ($o['inicio'] > $cursor && $cursor < $cierra) {
                $libres[] = ['inicio' => $cursor, 'fin' => min($o['inicio'], $cierra)];
            }
            $cursor = max($cursor, $o['fin']);
        }

        if ($cursor < $cierra) {
            $libres[] = ['inicio' => $cursor, 'fin' => $cierra];
        }

        return $libres;
    }
}
