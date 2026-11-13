<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'rol' => $this->rol,
            'foto_url' => $this->foto_url,
            'con_google' => (bool) $this->google_id,
            'organizacion' => $this->whenLoaded('organizacion', fn () => $this->organizacion
                ? ['id' => $this->organizacion->id, 'nombre' => $this->organizacion->nombre, 'configurada' => $this->organizacion->configurada]
                : null),
        ];
    }
}
