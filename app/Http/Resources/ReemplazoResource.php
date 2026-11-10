<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReemplazoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'reserva_afectada' => new ReservaResource($this->whenLoaded('afectada')),
            'reserva_prioritaria' => $this->whenLoaded('prioritaria', fn () => [
                'id' => $this->prioritaria->id,
                'estado' => $this->prioritaria->estado,
                'proposito' => $this->prioritaria->proposito?->only(['nombre', 'nivel_prioridad']),
            ]),
            // Cada alternativa se acepta enviando su índice: POST /api/reemplazos/{id}/alternativa {"alternativa": 0}
            'alternativas' => $this->alternativas,
            'alternativa_elegida' => $this->alternativa_elegida,
            'respondido_at' => $this->respondido_at?->toIso8601String(),
        ];
    }
}
