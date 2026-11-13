<?php

namespace App\Services\Analitica;

use App\Models\Bloqueo;
use App\Models\ObjetoReservable;
use App\Models\Organizacion;
use App\Models\Reserva;
use App\Reportes\ReporteDeOcupacion;
use App\Services\FestivosService;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

/**
 * Analítica para administradores (RF23, RF24, RF25). Se calcula al momento
 * con los datos actuales de la base de datos (RNF09).
 */
class AnaliticaService
{
    public function __construct(private FestivosService $festivos)
    {
    }

    /** RF23 y RF24: ocupación por espacio o recurso en un periodo. */
    public function ocupacion(Organizacion $org, Carbon $desde, Carbon $hasta, ?string $tipo = null): ReporteDeOcupacion
    {
        $desde = $desde->copy()->startOfDay();
        $hasta = $hasta->copy()->startOfDay();
        $dias = CarbonPeriod::create($desde, $hasta);

        $festivos = [];
        if ($org->cerrar_en_festivos) {
            foreach (range($desde->year, $hasta->year) as $anio) {
                $festivos = array_merge($festivos, array_column($this->festivos->delAnio($anio), 'fecha'));
            }
        }

        $objetos = ObjetoReservable::where('organizacion_id', $org->id)
            ->with(['horarios'])
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('tipo')->orderBy('nombre')
            ->get();

        $reservas = Reserva::where('organizacion_id', $org->id)
            ->with('objetos:id')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        $bloqueos = Bloqueo::where('organizacion_id', $org->id)
            ->where('fecha_inicio', '<', $hasta->copy()->endOfDay())
            ->where('fecha_fin', '>', $desde)
            ->get();

        $filas = $objetos->map(function (ObjetoReservable $o) use ($dias, $festivos, $reservas, $bloqueos, $org) {
            $operativas = 0.0;
            foreach ($dias as $dia) {
                $h = $o->horarios->firstWhere('dia_semana', $dia->dayOfWeekIso);
                if (! $h || in_array($dia->toDateString(), $festivos, true)) {
                    continue;
                }
                $abre = Carbon::parse($dia->toDateString().' '.$h->hora_inicio);
                $cierra = Carbon::parse($dia->toDateString().' '.$h->hora_fin);
                $horas = $abre->diffInMinutes($cierra) / 60;

                // Las horas bloqueadas no se pueden reservar: no cuentan como operativas
                foreach ($bloqueos->where('objeto_reservable_id', $o->id) as $b) {
                    $ini = max($abre, $b->fecha_inicio);
                    $fin = min($cierra, $b->fecha_fin);
                    if ($fin > $ini) {
                        $horas -= $ini->diffInMinutes($fin) / 60;
                    }
                }
                $operativas += max($horas, 0);
            }

            $propias = $reservas->filter(fn ($r) => $r->objetos->contains('id', $o->id));
            $ocupadas = $propias->whereIn('estado', ['aprobada', 'completada']);
            $horasReservadas = $ocupadas->sum(fn ($r) => Carbon::parse($r->hora_inicio)->diffInMinutes(Carbon::parse($r->hora_fin)) / 60);
            $tasa = $operativas > 0 ? round($horasReservadas / $operativas * 100, 1) : 0.0;

            return [
                'id' => $o->id,
                'nombre' => $o->nombre,
                'tipo' => $o->tipo,
                'categoria' => $o->categoria,
                'solicitudes' => $propias->count(),
                'aprobadas' => $ocupadas->count(),
                'canceladas' => $propias->whereIn('estado', ['cancelada', 'rechazada'])->count(),
                'horas_operativas' => round($operativas, 1),
                'horas_reservadas' => round($horasReservadas, 1),
                'tasa' => $tasa,
                'subutilizado' => $operativas > 0 && $tasa < $org->umbral_subutilizacion,
            ];
        });

        return new ReporteDeOcupacion($desde, $hasta, $filas, $org->umbral_subutilizacion);
    }

    /**
     * RF25: solicitudes por día de la semana y hora (mapa de calor) para un
     * tipo o categoría. Cuenta todas las solicitudes, se aprueben o no.
     */
    public function demanda(Organizacion $org, Carbon $desde, Carbon $hasta, ?string $tipo = null, ?string $categoria = null): array
    {
        $reservas = Reserva::where('organizacion_id', $org->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($tipo || $categoria, fn ($q) => $q->whereHas('objetos', fn ($o) => $o
                ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
                ->when($categoria, fn ($q) => $q->where('categoria', $categoria))))
            ->get(['fecha', 'hora_inicio', 'hora_fin']);

        $horas = range(6, 21);
        $matriz = [];
        foreach (range(1, 7) as $d) {
            $matriz[$d] = array_fill_keys($horas, 0);
        }

        foreach ($reservas as $r) {
            $dia = $r->fecha->dayOfWeekIso;
            $ini = (int) substr($r->hora_inicio, 0, 2);
            $fin = (int) substr($r->hora_fin, 0, 2) + (substr($r->hora_fin, 3, 2) !== '00' ? 1 : 0);
            for ($h = $ini; $h < max($fin, $ini + 1); $h++) {
                if (isset($matriz[$dia][$h])) {
                    $matriz[$dia][$h]++;
                }
            }
        }

        $franjas = collect($matriz)->flatMap(fn ($fila, $dia) => collect($fila)
            ->map(fn ($total, $hora) => ['dia' => $dia, 'dia_nombre' => Organizacion::DIAS[$dia], 'hora' => sprintf('%02d:00', $hora), 'total' => $total]))
            ->filter(fn ($f) => $f['total'] > 0)
            ->sortByDesc('total')->values();

        return [
            'horas' => array_map(fn ($h) => sprintf('%02d:00', $h), $horas),
            'dias' => Organizacion::DIAS,
            'matriz' => $matriz,
            'por_dia' => collect($matriz)->map(fn ($f) => array_sum($f))->all(),
            'por_hora' => collect($horas)->mapWithKeys(fn ($h) => [sprintf('%02d:00', $h) => collect($matriz)->sum(fn ($f) => $f[$h])])->all(),
            'picos' => $franjas->take(5)->all(),
            'total_solicitudes' => $reservas->count(),
        ];
    }

    /** Totales para los gráficos del panel. */
    public function resumen(Organizacion $org, Carbon $desde, Carbon $hasta): array
    {
        $base = Reserva::where('organizacion_id', $org->id)->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()]);

        return [
            'por_estado' => collect(array_keys(Reserva::ESTADOS))->mapWithKeys(fn ($e) => [$e => (clone $base)->where('estado', $e)->count()])->all(),
            'por_proposito' => (clone $base)->with('proposito')->get()->groupBy('proposito.nombre')->map->count()->sortDesc()->all(),
        ];
    }
}
