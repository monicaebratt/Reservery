<?php

namespace App\Reportes;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// Reporte de ocupación de un periodo
class ReporteDeOcupacion
{
    public function __construct(
        public Carbon $desde,
        public Carbon $hasta,
        public Collection $filas,
        public int $umbralSubutilizacion,
    ) {
    }

    public function periodo(): string
    {
        return $this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y');
    }

    // Horas reservadas sobre horas operativas de todos los objetos
    public function tasaOcupacion(): float
    {
        $operativas = $this->filas->sum('horas_operativas');

        return $operativas > 0 ? round($this->filas->sum('horas_reservadas') / $operativas * 100, 1) : 0.0;
    }

    // Objetos por debajo del umbral configurado por la organización
    public function subutilizados(): Collection
    {
        return $this->filas->where('subutilizado', true)->values();
    }

    // GenerarReporte: el PDF descargable
    public function generarReporte(string $organizacion, string $generadoPor)
    {
        return Pdf::loadView('reportes.ocupacion', [
            'reporte' => $this,
            'organizacion' => $organizacion,
            'generadoPor' => $generadoPor,
        ])->setPaper('letter', 'landscape');
    }

    public function toArray(): array
    {
        return [
            'periodo' => ['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()],
            'tasa_ocupacion' => $this->tasaOcupacion(),
            'umbral_subutilizacion' => $this->umbralSubutilizacion,
            'objetos' => $this->filas->values()->all(),
        ];
    }
}
