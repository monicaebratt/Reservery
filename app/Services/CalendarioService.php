<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\Reserva;

/** RF26: calendario centralizado con todas las reservas activas y los bloqueos. */
class CalendarioService
{
    public function eventos(string $desde, string $hasta, ?int $objetoId = null, ?string $tipo = null, bool $incluirFinalizadas = false): array
    {
        $reservas = Reserva::with(['objetos', 'proposito', 'usuario'])
            ->whereIn('estado', $incluirFinalizadas ? ['pendiente', 'aprobada', 'completada'] : Reserva::ACTIVOS)
            ->whereBetween('fecha', [substr($desde, 0, 10), substr($hasta, 0, 10)])
            ->when($objetoId || $tipo, fn ($q) => $q->whereHas('objetos', fn ($o) => $o
                ->when($objetoId, fn ($q) => $q->where('objetos_reservables.id', $objetoId))
                ->when($tipo, fn ($q) => $q->where('tipo', $tipo))))
            ->get()
            ->map(fn (Reserva $r) => [
                'id' => 'r'.$r->id,
                'title' => $r->objetos->pluck('nombre')->join(' + ').' · '.$r->motivo,
                'start' => $r->fecha->toDateString().'T'.$r->hora_inicio,
                'end' => $r->fecha->toDateString().'T'.$r->hora_fin,
                'color' => $r->proposito->color,
                'textColor' => $this->textoLegible($r->proposito->color),
                'classNames' => ['estado-'.$r->estado],
                'url' => route('reservas.show', $r->id),
                'extendedProps' => [
                    'tipo' => 'reserva',
                    'estado' => $r->estado,
                    'proposito' => $r->proposito->nombre,
                    'solicitante' => $r->usuario->name,
                ],
            ]);

        $bloqueos = Bloqueo::with('objeto')
            ->where('fecha_inicio', '<', $hasta)
            ->where('fecha_fin', '>', $desde)
            ->when($objetoId, fn ($q) => $q->where('objeto_reservable_id', $objetoId))
            ->when($tipo, fn ($q) => $q->whereHas('objeto', fn ($o) => $o->where('tipo', $tipo)))
            ->get()
            ->map(fn (Bloqueo $b) => [
                'id' => 'b'.$b->id,
                'title' => 'Bloqueado: '.$b->objeto->nombre.' · '.$b->motivo,
                'start' => $b->fecha_inicio->toIso8601String(),
                'end' => $b->fecha_fin->toIso8601String(),
                'color' => '#35265E',
                'extendedProps' => ['tipo' => 'bloqueo'],
            ]);

        return $reservas->concat($bloqueos)->values()->all();
    }

    /** Texto oscuro sobre colores claros y blanco sobre colores oscuros. */
    private function textoLegible(string $hex): string
    {
        [$r, $g, $b] = sscanf(ltrim($hex, '#'), '%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#35265E' : '#FFFFFF';
    }
}
