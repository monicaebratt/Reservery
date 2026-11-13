<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Propuesta de reemplazo de una reserva por otra de mayor prioridad
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

    // Si el reemplazo es de otra organización responde 404
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
