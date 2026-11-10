<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListaEsperaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'posicion' => $this->posicion,
            'fecha_inscripcion' => $this->created_at->toIso8601String(),
            'objeto' => $this->whenLoaded('objeto', fn () => $this->objeto->only(['id', 'nombre', 'tipo'])),
            'objetos' => $this->objetos,
            'fecha' => $this->fecha->format('Y-m-d'),
            'hora_inicio' => substr($this->hora_inicio, 0, 5),
            'hora_fin' => substr($this->hora_fin, 0, 5),
            'motivo' => $this->motivo,
            'notificada_at' => $this->notificada_at?->toIso8601String(),
            'reserva_id' => $this->reserva_id,
        ];
    }
}
