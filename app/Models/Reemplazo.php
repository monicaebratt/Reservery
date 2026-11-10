<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RF18: propuesta de reemplazo de una reserva por otra de mayor prioridad. */
class Reemplazo extends Model
{
    protected $fillable = [
        'reserva_prioritaria_id', 'reserva_afectada_id', 'estado',
        'alternativas', 'alternativa_elegida', 'respondido_at',
    ];

    protected function casts(): array
    {
        return ['alternativas' => 'array', 'alternativa_elegida' => 'array', 'respondido_at' => 'datetime'];
    }

    public function prioritaria(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_prioritaria_id');
    }

    public function afectada(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_afectada_id');
    }

    /**
     * Al buscar un reemplazo por la URL ({reemplazo}), solo se encuentran los
     * de la organización del usuario: la reserva afectada tiene ese filtro (RNF06).
     * Si es de otra organización responde 404 en la web y en la API.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->whereHas('afectada')->firstOrFail();
    }

    public function estaAbierto(): bool
    {
        return in_array($this->estado, ['propuesto', 'alternativa_aceptada', 'sin_alternativa'], true);
    }

    public function getEstadoNombreAttribute(): string
    {
        return [
            'propuesto' => 'Esperando tu respuesta',
            'alternativa_aceptada' => 'Aceptaste una alternativa',
            'sin_alternativa' => 'Aceptaste ceder el horario',
            'confirmado' => 'Reemplazo confirmado',
            'descartado' => 'Descartado: tu reserva se mantiene',
        ][$this->estado] ?? $this->estado;
    }
}
