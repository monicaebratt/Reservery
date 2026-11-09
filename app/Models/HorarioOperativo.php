<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RF06: rango de horario operativo de un objeto para un día de la semana. */
class HorarioOperativo extends Model
{
    protected $table = 'horarios_operativos';

    public $timestamps = false;

    protected $fillable = ['objeto_reservable_id', 'dia_semana', 'hora_inicio', 'hora_fin'];

    public function objeto(): BelongsTo
    {
        return $this->belongsTo(ObjetoReservable::class, 'objeto_reservable_id');
    }

    public function cubre(string $inicio, string $fin): bool
    {
        return substr($this->hora_inicio, 0, 5) <= substr($inicio, 0, 5)
            && substr($this->hora_fin, 0, 5) >= substr($fin, 0, 5);
    }
}
