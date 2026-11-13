<?php

namespace App\Models;

use App\Events\ReservaCambioDeEstado;
use App\Models\Concerns\PerteneceAOrganizacion;
use App\States\Aprobada;
use App\States\Cancelada;
use App\States\Completada;
use App\States\EstadoReserva;
use App\States\Pendiente;
use App\States\Rechazada;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Contexto del patrón State
class Reserva extends Model
{
    use PerteneceAOrganizacion;

    protected $fillable = [
        'organizacion_id', 'user_id', 'proposito_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado',
    ];

    protected $attributes = ['estado' => 'pendiente'];

    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d'];
    }

    public const ESTADOS = [
        'pendiente' => Pendiente::class,
        'aprobada' => Aprobada::class,
        'rechazada' => Rechazada::class,
        'cancelada' => Cancelada::class,
        'completada' => Completada::class,
    ];

    // Estados que ocupan la agenda
    public const ACTIVOS = ['pendiente', 'aprobada'];

    public const ACCIONES = ['aprobar', 'rechazar', 'cancelar', 'completar'];

    // Quién hace el cambio y por qué; lo usa cambiarEstado para la trazabilidad
    private ?User $responsable = null;

    private ?string $motivoCambio = null;

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proposito(): BelongsTo
    {
        return $this->belongsTo(Proposito::class);
    }

    public function objetos(): BelongsToMany
    {
        return $this->belongsToMany(ObjetoReservable::class, 'objeto_reservable_reserva', 'reserva_id', 'objeto_reservable_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialReserva::class)->orderBy('created_at')->orderBy('id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    // Reemplazos que esta reserva (de mayor prioridad) propone a otras
    public function reemplazosPropuestos(): HasMany
    {
        return $this->hasMany(Reemplazo::class, 'reserva_prioritaria_id');
    }

    // Reemplazos que otras reservas le proponen a esta
    public function reemplazosRecibidos(): HasMany
    {
        return $this->hasMany(Reemplazo::class, 'reserva_afectada_id');
    }

    // Consultas

    public function scopeActivas(Builder $q): Builder
    {
        return $q->whereIn('estado', self::ACTIVOS);
    }

    // Reservas que se cruzan con una fecha y un rango de horas
    public function scopeQueCruzan(Builder $q, string $fecha, string $inicio, string $fin): Builder
    {
        return $q->whereDate('fecha', $fecha)
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio);
    }

    // Patrón State

    public function estadoActual(): EstadoReserva
    {
        $clase = self::ESTADOS[$this->estado] ?? Pendiente::class;

        return new $clase();
    }

    // Lo llaman los estados concretos para hacer la transición
    public function cambiarEstado(EstadoReserva $nuevo): void
    {
        $anterior = $this->estado;
        $this->estado = $nuevo->nombre();
        $this->save();

        $this->registrar($this->estado, $this->motivoCambio, $this->responsable, $anterior);

        // Avisa al resto del sistema: notificaciones, lista de espera y reemplazos
        event(new ReservaCambioDeEstado($this, $anterior, $this->estado, $this->responsable, $this->motivoCambio));

        $this->responsable = null;
        $this->motivoCambio = null;
    }

    public function aprobar(?User $por = null, ?string $motivo = null): void
    {
        $this->prepararCambio($por, $motivo)->estadoActual()->aprobar($this);
    }

    public function rechazar(?User $por = null, ?string $motivo = null): void
    {
        $this->prepararCambio($por, $motivo)->estadoActual()->rechazar($this);
    }

    public function cancelar(?User $por = null, ?string $motivo = null): void
    {
        $this->prepararCambio($por, $motivo)->estadoActual()->cancelar($this);
    }

    public function completar(?User $por = null, ?string $motivo = null): void
    {
        $this->prepararCambio($por, $motivo)->estadoActual()->completar($this);
    }

    public function accionesPermitidas(): array
    {
        return $this->estadoActual()->accionesPermitidas();
    }

    public function puedeEditarse(): bool
    {
        return $this->estadoActual()->puedeEditarse();
    }

    private function prepararCambio(?User $por, ?string $motivo): static
    {
        $this->responsable = $por;
        $this->motivoCambio = $motivo;

        return $this;
    }

    // Trazabilidad

    public function registrar(string $accion, ?string $detalle = null, ?User $responsable = null, ?string $estadoAnterior = null): HistorialReserva
    {
        return $this->historial()->create([
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $this->estado,
            'detalle' => $detalle,
            'responsable_id' => $responsable?->id,
        ]);
    }

    // Presentación

    public function getEspacioAttribute(): ?ObjetoReservable
    {
        return $this->objetos->firstWhere('tipo', 'espacio');
    }

    public function getEsCompuestaAttribute(): bool
    {
        return $this->objetos->count() > 1;
    }

    public function getHorarioAttribute(): string
    {
        return substr($this->hora_inicio, 0, 5).' – '.substr($this->hora_fin, 0, 5);
    }

    public function getColorEstadoAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'aprobada' => 'success',
            'rechazada' => 'danger',
            'cancelada' => 'secondary',
            'completada' => 'primary',
            default => 'light',
        };
    }
}
