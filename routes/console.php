<?php

use App\Models\Reserva;
use App\Services\ListaEspera\ListaEsperaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Mantenimiento diario de Reservery:
|  - completa las reservas aprobadas que ya terminaron (pasa por el patrón State)
|  - vence las inscripciones de lista de espera de días que ya pasaron
| Ejecutar a mano: php artisan reservery:mantenimiento
*/
Artisan::command('reservery:mantenimiento', function (ListaEsperaService $listaEspera) {
    $completadas = 0;

    Reserva::withoutGlobalScopes()->where('estado', 'aprobada')
        ->where(fn ($q) => $q->whereDate('fecha', '<', today())
            ->orWhere(fn ($q) => $q->whereDate('fecha', today())->where('hora_fin', '<=', now()->format('H:i:s'))))
        ->each(function (Reserva $r) use (&$completadas) {
            $r->completar(null, 'Completada automáticamente al terminar el horario.');
            $completadas++;
        });

    $vencidas = $listaEspera->vencerPasadas();

    $this->info("Reservas completadas: {$completadas}. Inscripciones vencidas: {$vencidas}.");
})->purpose('Completa reservas terminadas y vence listas de espera pasadas');

Schedule::command('reservery:mantenimiento')->hourly();
