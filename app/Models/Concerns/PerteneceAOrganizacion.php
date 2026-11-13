<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Models\Organizacion;

// Cada organización solo ve sus propios datos
trait PerteneceAOrganizacion
{
    protected static function bootPerteneceAOrganizacion(): void
    {
        static::addGlobalScope('organizacion', function (Builder $query) {
            $usuario = Auth::user();
            if ($usuario && $usuario->organizacion_id) {
                $query->where($query->getModel()->getTable().'.organizacion_id', $usuario->organizacion_id);
            }
        });

        static::creating(function ($modelo) {
            if (! $modelo->organizacion_id && Auth::user()?->organizacion_id) {
                $modelo->organizacion_id = Auth::user()->organizacion_id;
            }
        });
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }
}
