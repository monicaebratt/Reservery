<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAOrganizacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Inhabilita un objeto entre dos fechas/horas
class Bloqueo extends Model
{
    use PerteneceAOrganizacion;

    protected $fillable = ['organizacion_id', 'objeto_reservable_id', 'creado_por', 'fecha_inicio', 'fecha_fin', 'motivo'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];
    }

    public function objeto(): BelongsTo
    {
        return $this->belongsTo(ObjetoReservable::class, 'objeto_reservable_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Bloqueos que se cruzan con una fecha y un rango de horas
    public function scopeQueCruzan($q, string $fecha, string $inicio, string $fin)
    {
        return $q->where('fecha_inicio', '<', "{$fecha} {$fin}")
            ->where('fecha_fin', '>', "{$fecha} {$inicio}");
    }
}
