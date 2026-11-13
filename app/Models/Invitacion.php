<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAOrganizacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Correo autorizado por la organización con el rol que tendrá al unirse
class Invitacion extends Model
{
    use PerteneceAOrganizacion;

    protected $table = 'invitaciones';

    protected $fillable = ['organizacion_id', 'email', 'rol', 'invitado_por', 'aceptada_at'];

    protected function casts(): array
    {
        return ['aceptada_at' => 'datetime'];
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    public function setEmailAttribute(string $valor): void
    {
        $this->attributes['email'] = mb_strtolower(trim($valor));
    }
}
