<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ObjetoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'descripcion' => $this->descripcion,
            'capacidad' => $this->capacidad,
            'ubicacion' => $this->ubicacion,
            'zona' => $this->zona,
            'piso' => $this->piso,
            'disponible' => $this->disponible,
            'imagen_url' => $this->imagen_url,
            'horarios' => $this->whenLoaded('horarios', fn () => $this->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana,
                'inicio' => substr($h->hora_inicio, 0, 5),
                'fin' => substr($h->hora_fin, 0, 5),
            ])),
            'recursos_asociados' => $this->whenLoaded('recursosAsociados', fn () => $this->recursosAsociados->map->only(['id', 'nombre', 'categoria'])),
        ];
    }
}
