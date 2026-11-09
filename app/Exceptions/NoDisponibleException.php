<?php

namespace App\Exceptions;

use App\Services\Reservas\ResultadoDisponibilidad;
use RuntimeException;

/** Se lanza cuando una solicitud no se puede reservar (RF04, RF15). */
class NoDisponibleException extends RuntimeException
{
    public function __construct(public ResultadoDisponibilidad $resultado, public array $solicitud = [])
    {
        parent::__construct($resultado->resumen());
    }
}
