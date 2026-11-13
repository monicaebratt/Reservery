<?php

namespace App\Services\Reservas;

use Illuminate\Support\Collection;

// Resultado de validar una solicitud contra la agenda
class ResultadoDisponibilidad
{
    /** @var array<int, array{objeto_id:?int, objeto:?string, tipo:string, detalle:string, reservas:array}> */
    public array $problemas = [];

    public function agregar(string $tipo, string $detalle, ?object $objeto = null, array $reservas = []): void
    {
        $this->problemas[] = [
            'objeto_id' => $objeto?->id,
            'objeto' => $objeto?->nombre,
            'tipo' => $tipo,
            'detalle' => $detalle,
            'reservas' => $reservas,
        ];
    }

    public function disponible(): bool
    {
        return $this->problemas === [];
    }

    // True si solo la ocupan otras reservas
    public function soloChocaConReservas(): bool
    {
        return ! $this->disponible()
            && collect($this->problemas)->every(fn ($p) => $p['tipo'] === 'ocupado');
    }

    // Ids de las reservas con las que choca
    public function reservasEnConflicto(): Collection
    {
        return collect($this->problemas)->pluck('reservas')->flatten()->unique()->values();
    }

    public function resumen(): string
    {
        return $this->disponible()
            ? 'Disponible.'
            : collect($this->problemas)->pluck('detalle')->unique()->implode(' ');
    }

    public function toArray(): array
    {
        return [
            'disponible' => $this->disponible(),
            'problemas' => collect($this->problemas)->map(fn ($p) => collect($p)->except('reservas'))->all(),
        ];
    }
}
