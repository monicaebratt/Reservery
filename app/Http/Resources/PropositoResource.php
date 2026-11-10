<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropositoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $datos = $this->resource->only(['id', 'nombre', 'descripcion', 'color']);

        // El nivel de prioridad solo lo ven los administradores
        if ($request->user()?->esAdmin()) {
            $datos['nivel_prioridad'] = $this->nivel_prioridad;
        }

        return $datos;
    }
}
