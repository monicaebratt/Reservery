<?php

namespace App\Listeners;

use App\Events\ReservaCambioDeEstado;
use App\Services\ListaEspera\ListaEsperaService;

// Al cancelar o rechazar, el horario se libera y se avisa a la lista de espera
class ProcesarListaEspera
{
    public function __construct(private ListaEsperaService $listaEspera)
    {
    }

    public function handle(ReservaCambioDeEstado $e): void
    {
        if (! in_array($e->nuevo, ['cancelada', 'rechazada'], true)) {
            return;
        }

        $r = $e->reserva->loadMissing('objetos');
        $this->listaEspera->procesarLiberacion(
            $r->objetos->pluck('id')->all(), $r->fecha->toDateString(), $r->hora_inicio, $r->hora_fin, $r->organizacion_id,
        );
    }
}
