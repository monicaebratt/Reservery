<?php

namespace App\States;

/** Estado final: no permite ninguna transición. */
class Completada extends EstadoBase
{
    public function nombre(): string
    {
        return 'completada';
    }
}
