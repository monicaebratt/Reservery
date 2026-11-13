<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAOrganizacion;
use App\Services\Reservas\DisponibilidadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// Clase "abstracta" ObjetoReservable del diagrama
class ObjetoReservable extends Model
{
    use PerteneceAOrganizacion;

    protected $table = 'objetos_reservables';

    protected $fillable = [
        'organizacion_id', 'nombre', 'tipo', 'categoria', 'descripcion',
        'capacidad', 'ubicacion', 'zona', 'piso', 'imagen', 'disponible',
    ];

    protected function casts(): array
    {
        return ['disponible' => 'boolean', 'capacidad' => 'integer', 'piso' => 'integer'];
    }

    // Relaciones

    public function reservas(): BelongsToMany
    {
        return $this->belongsToMany(Reserva::class, 'objeto_reservable_reserva', 'objeto_reservable_id', 'reserva_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioOperativo::class, 'objeto_reservable_id')->orderBy('dia_semana');
    }

    public function bloqueos(): HasMany
    {
        return $this->hasMany(Bloqueo::class, 'objeto_reservable_id');
    }

    public function listaEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class, 'objeto_reservable_id');
    }

    // Recursos que se pueden reservar junto con este espacio
    public function recursosAsociados(): BelongsToMany
    {
        return $this->belongsToMany(ObjetoReservable::class, 'espacio_recurso', 'espacio_id', 'recurso_id');
    }

    // Espacios con los que se puede reservar este recurso
    public function espaciosAsociados(): BelongsToMany
    {
        return $this->belongsToMany(ObjetoReservable::class, 'espacio_recurso', 'recurso_id', 'espacio_id');
    }

    // Consultas

    public function scopeEspacios(Builder $q): Builder
    {
        return $q->where('tipo', 'espacio');
    }

    public function scopeRecursos(Builder $q): Builder
    {
        return $q->where('tipo', 'recurso');
    }

    public function scopeDisponibles(Builder $q): Builder
    {
        return $q->where('disponible', true);
    }

    public function esEspacio(): bool
    {
        return $this->tipo === 'espacio';
    }

    // ConsultarDisponibilidad del diagrama de clases
    public function consultarDisponibilidad(string $fecha): array
    {
        return app(DisponibilidadService::class)->agenda([$this->id], $fecha)[0] ?? [];
    }

    // Horario operativo de un día (1 = lunes ... 7 = domingo) o null si ese día no abre
    public function horarioDelDia(int $dia): ?HorarioOperativo
    {
        return $this->relationLoaded('horarios')
            ? $this->horarios->firstWhere('dia_semana', $dia)
            : $this->horarios()->where('dia_semana', $dia)->first();
    }

    // Crea el horario de lunes a domingo a partir de la configuración de la organización
    public function aplicarHorarioPorDefecto(): void
    {
        $org = $this->organizacion;
        foreach ($org->dias_operativos as $dia) {
            $this->horarios()->updateOrCreate(['dia_semana' => $dia], [
                'hora_inicio' => $org->hora_apertura,
                'hora_fin' => $org->hora_cierre,
            ]);
        }
    }

    public function getImagenUrlAttribute(): string
    {
        return $this->imagen
            ? Storage::disk('public')->url($this->imagen)
            : asset($this->tipo === 'recurso' ? 'img/recurso.svg' : 'img/espacio.svg');  // Ilustración por defecto
    }

    public function getTipoNombreAttribute(): string
    {
        return $this->tipo === 'espacio' ? 'Espacio' : 'Recurso';
    }
}
