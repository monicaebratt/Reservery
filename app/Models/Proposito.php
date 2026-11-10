<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAOrganizacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Propósito de la reserva y su nivel de prioridad (RF16, RF17). */
class Proposito extends Model
{
    use PerteneceAOrganizacion;

    protected $fillable = ['organizacion_id', 'nombre', 'descripcion', 'nivel_prioridad', 'color'];

    protected function casts(): array
    {
        return ['nivel_prioridad' => 'integer'];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function tieneMayorPrioridadQue(Proposito $otro): bool
    {
        return $this->nivel_prioridad > $otro->nivel_prioridad;
    }
}
