<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organizacion extends Model
{
    use HasFactory;

    protected $table = 'organizaciones';

    public const TIPOS = [
        'universidad' => 'Universidad o institución educativa',
        'colegio' => 'Colegio',
        'empresa' => 'Empresa',
        'conjunto' => 'Conjunto residencial',
        'club' => 'Club o centro deportivo',
        'publica' => 'Entidad pública',
        'otro' => 'Otro',
    ];

    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    // Propósitos con los que arranca toda organización; el admin los puede cambiar
    public const PROPOSITOS_INICIALES = [
        ['nombre' => 'Evento institucional', 'nivel_prioridad' => 4, 'color' => '#35265E', 'descripcion' => 'Actividades oficiales de la organización'],
        ['nombre' => 'Clase programada', 'nivel_prioridad' => 3, 'color' => '#8061C5', 'descripcion' => 'Clases o capacitaciones del calendario'],
        ['nombre' => 'Reunión de trabajo', 'nivel_prioridad' => 2, 'color' => '#B99AEF', 'descripcion' => 'Reuniones de equipos o comités'],
        ['nombre' => 'Uso individual', 'nivel_prioridad' => 1, 'color' => '#E0A458', 'descripcion' => 'Uso personal o de estudio'],
    ];

    protected $fillable = [
        'nombre', 'nit', 'tipo', 'descripcion', 'ciudad', 'direccion', 'telefono', 'correo_contacto',
        'sitio_web', 'logo', 'codigo_invitacion', 'hora_apertura', 'hora_cierre', 'dias_operativos',
        'cerrar_en_festivos', 'requiere_aprobacion', 'umbral_subutilizacion', 'configurada',
    ];

    protected $attributes = [
        'dias_operativos' => '[1,2,3,4,5]',
    ];

    protected function casts(): array
    {
        return [
            'dias_operativos' => 'array',
            'cerrar_en_festivos' => 'boolean',
            'requiere_aprobacion' => 'boolean',
            'configurada' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organizacion $org) {
            $org->codigo_invitacion ??= static::nuevoCodigo();
        });

        // Toda organización nueva arranca con sus propósitos por defecto
        static::created(function (Organizacion $org) {
            foreach (self::PROPOSITOS_INICIALES as $p) {
                $org->propositos()->create($p);
            }
        });
    }

    public static function nuevoCodigo(): string
    {
        do {
            $codigo = strtoupper(Str::random(8));
        } while (static::where('codigo_invitacion', $codigo)->exists());

        return $codigo;
    }

    // Relaciones

    public function miembros(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function propietario(): HasOne
    {
        return $this->hasOne(User::class)->where('rol', 'organizacion');
    }

    public function administradores(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('rol', ['organizacion', 'admin']);
    }

    public function invitaciones(): HasMany
    {
        return $this->hasMany(Invitacion::class);
    }

    public function propositos(): HasMany
    {
        return $this->hasMany(Proposito::class);
    }

    public function objetosReservables(): HasMany
    {
        return $this->hasMany(ObjetoReservable::class);
    }

    // Operaciones del diagrama de clases

    // asignarRolAdmin()
    public function asignarRolAdmin(User $miembro): void
    {
        abort_unless($miembro->organizacion_id === $this->id, 422, 'Ese usuario no pertenece a la organización.');
        abort_if($miembro->rol === 'organizacion', 422, 'La cuenta de la organización ya tiene todos los permisos.');
        $miembro->update(['rol' => 'admin']);
    }

    // revocarRolAdmin()
    public function revocarRolAdmin(User $miembro): void
    {
        abort_unless($miembro->organizacion_id === $this->id, 422, 'Ese usuario no pertenece a la organización.');
        abort_if($miembro->rol === 'organizacion', 422, 'No se le puede quitar el rol a la cuenta de la organización.');
        $miembro->update(['rol' => 'usuario']);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function operaEl(int $diaSemana): bool
    {
        return in_array($diaSemana, $this->dias_operativos ?? [], true);
    }
}
