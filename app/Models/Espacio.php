<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/** Espacio: hereda de ObjetoReservable (sala, auditorio, cancha, laboratorio...). */
class Espacio extends ObjetoReservable
{
    protected static function booted(): void
    {
        static::addGlobalScope('tipo', fn (Builder $q) => $q->where('tipo', 'espacio'));
        static::creating(fn (Espacio $e) => $e->tipo = 'espacio');
    }
}
