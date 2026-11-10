<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha->format('Y-m-d'),
            'hora_inicio' => substr($this->hora_inicio, 0, 5),
            'hora_fin' => substr($this->hora_fin, 0, 5),
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'acciones_permitidas' => $this->accionesPermitidas(),
            'puede_editarse' => $this->puedeEditarse(),
            'proposito' => $this->whenLoaded('proposito', fn () => new PropositoResource($this->proposito)),
            'usuario' => $this->whenLoaded('usuario', fn () => ['id' => $this->usuario->id, 'nombre' => $this->usuario->name]),
            'objetos' => ObjetoResource::collection($this->whenLoaded('objetos')),
            'compuesta' => $this->relationLoaded('objetos') ? $this->objetos->count() > 1 : null,
            'reemplazos_propuestos' => $this->whenLoaded('reemplazosPropuestos', fn () => $this->reemplazosPropuestos->map->only(['id', 'reserva_afectada_id', 'estado'])),
            'creada' => $this->created_at?->toIso8601String(),
        ];
    }
}
