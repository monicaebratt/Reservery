<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAOrganizacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** ListaDeEspera del diagrama (RF22): turno por orden de llegada. */
class ListaEspera extends Model
{
    use PerteneceAOrganizacion;

    protected $table = 'lista_espera';

    protected $fillable = [
        'organizacion_id', 'user_id', 'objeto_reservable_id', 'proposito_id', 'objetos',
        'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado', 'notificada_at', 'reserva_id',
    ];

    protected $attributes = ['estado' => 'esperando'];

    protected function casts(): array
    {
        return ['objetos' => 'array', 'fecha' => 'date:Y-m-d', 'notificada_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function objeto(): BelongsTo
    {
        return $this->belongsTo(ObjetoReservable::class, 'objeto_reservable_id');
    }

    public function proposito(): BelongsTo
    {
        return $this->belongsTo(Proposito::class);
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function scopeActivas($q)
    {
        return $q->whereIn('estado', ['esperando', 'notificada']);
    }

    public function getFechaInscripcionAttribute()
    {
        return $this->created_at;
    }

    /** Posición en la fila: cuántos se inscribieron antes para el mismo objeto, día y horario cruzado. */
    public function getPosicionAttribute(): ?int
    {
        if (! in_array($this->estado, ['esperando', 'notificada'], true)) {
            return null;
        }

        return static::withoutGlobalScopes()->activas()
            ->where('objeto_reservable_id', $this->objeto_reservable_id)
            ->whereDate('fecha', $this->fecha)
            ->where('hora_inicio', '<', $this->hora_fin)
            ->where('hora_fin', '>', $this->hora_inicio)
            ->where(fn ($q) => $q->where('created_at', '<', $this->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $this->created_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }

    public function getEstadoNombreAttribute(): string
    {
        return [
            'esperando' => 'En espera',
            'notificada' => '¡Se liberó! Resérvalo ya',
            'atendida' => 'Convertida en reserva',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
        ][$this->estado] ?? $this->estado;
    }
}
