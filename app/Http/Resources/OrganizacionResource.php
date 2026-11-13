<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $esDueña = $request->user()?->esOrganizacion() && $request->user()->organizacion_id === $this->id;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nit' => $this->nit,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'ciudad' => $this->ciudad,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo_contacto' => $this->correo_contacto,
            'sitio_web' => $this->sitio_web,
            'logo_url' => $this->logo_url,
            'hora_apertura' => substr($this->hora_apertura, 0, 5),
            'hora_cierre' => substr($this->hora_cierre, 0, 5),
            'dias_operativos' => $this->dias_operativos,
            'cerrar_en_festivos' => $this->cerrar_en_festivos,
            'requiere_aprobacion' => $this->requiere_aprobacion,
            'umbral_subutilizacion' => $this->umbral_subutilizacion,
            'configurada' => $this->configurada,
            // Solo la cuenta de la organización ve el código para invitar
            'codigo_invitacion' => $this->when($esDueña, $this->codigo_invitacion),
        ];
    }
}
