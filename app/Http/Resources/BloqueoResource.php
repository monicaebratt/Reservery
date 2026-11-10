<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BloqueoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'objeto' => $this->whenLoaded('objeto', fn () => $this->objeto->only(['id', 'nombre', 'tipo'])),
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d H:i'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d H:i'),
            'motivo' => $this->motivo,
            'creado_por' => $this->whenLoaded('creador', fn () => $this->creador?->name),
        ];
    }
}
