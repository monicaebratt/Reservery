<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RNF08: cada cambio de una reserva con fecha, hora y responsable. */
class HistorialReserva extends Model
{
    protected $table = 'historial_reservas';

    public const UPDATED_AT = null;

    protected $fillable = ['reserva_id', 'accion', 'estado_anterior', 'estado_nuevo', 'detalle', 'responsable_id'];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function getResponsableNombreAttribute(): string
    {
        return $this->responsable?->name ?? 'Sistema';
    }
}
