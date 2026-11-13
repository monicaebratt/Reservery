<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

// Debe confirmar su correo para que se le apliquen invitaciones con rol (seguridad)
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = ['organizacion' => 'Organización', 'admin' => 'Administrador', 'usuario' => 'Usuario'];

    protected $fillable = [
        'organizacion_id', 'name', 'email', 'password', 'rol', 'telefono', 'foto', 'google_id', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    public function listaEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class);
    }

    // Roles

    // Cuenta que registró la organización
    public function esOrganizacion(): bool
    {
        return $this->rol === 'organizacion';
    }

    // Admin (la cuenta de la organización también cuenta)
    public function esAdmin(): bool
    {
        return in_array($this->rol, ['organizacion', 'admin'], true);
    }

    public function tieneOrganizacionActiva(): bool
    {
        return $this->organizacion_id && $this->organizacion?->configurada;
    }

    public function getRolNombreAttribute(): string
    {
        return self::ROLES[$this->rol] ?? $this->rol;
    }

    public function getFotoUrlAttribute(): string
    {
        return $this->foto
            ? (str_starts_with($this->foto, 'http') ? $this->foto : Storage::disk('public')->url($this->foto))
            : 'https://ui-avatars.com/api/?background=EDE5FF&color=8061C5&bold=true&name='.urlencode(trim(preg_replace('/[^\p{L}\s]/u', '', $this->name)) ?: 'R');
    }

    // Envía el correo de verificación sin romper el flujo si el correo falla
    public function enviarVerificacion(): void
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }
        try {
            $this->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
