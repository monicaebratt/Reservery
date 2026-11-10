<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/** Recurso: hereda de ObjetoReservable (proyector, portátil, cámara...). */
class Recurso extends ObjetoReservable
{
    protected static function booted(): void
    {
        static::addGlobalScope('tipo', fn (Builder $q) => $q->where('tipo', 'recurso'));
        static::creating(fn (Recurso $r) => $r->tipo = 'recurso');
    }
}
