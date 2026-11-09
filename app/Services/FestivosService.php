<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Consume un servicio web externo (API pública Nager.Date) para conocer los
 * festivos de Colombia: la organización puede cerrar esos días y el panel
 * muestra los próximos.
 */
class FestivosService
{
    private const URL = 'https://date.nager.at/api/v3/PublicHolidays';

    /** @return array<int, array{fecha: string, nombre: string}> */
    public function delAnio(int $anio): array
    {
        $clave = "festivos_co_{$anio}";

        if (Cache::has($clave)) {
            return Cache::get($clave);
        }

        try {
            $respuesta = Http::withHeaders(['Accept' => 'application/json'])
                ->timeout(5)
                ->get(self::URL."/{$anio}/CO");

            if ($respuesta->failed()) {
                return $this->sinServicio($clave);
            }

            $festivos = collect($respuesta->json())
                ->map(fn ($f) => ['fecha' => $f['date'], 'nombre' => $f['localName']])
                ->all();

            // Solo se guarda en caché cuando la consulta salió bien
            Cache::put($clave, $festivos, now()->addDay());

            return $festivos;
        } catch (\Throwable) {
            return $this->sinServicio($clave); // Sin internet: la app sigue funcionando
        }
    }

    /** Si el servicio no responde, se recuerda por 10 minutos para no esperar en cada petición. */
    private function sinServicio(string $clave): array
    {
        Cache::put($clave, [], now()->addMinutes(10));

        return [];
    }

    public function proximos(int $cantidad = 4): array
    {
        $hoy = now()->toDateString();

        return collect($this->delAnio(now()->year))
            ->merge($this->delAnio(now()->year + 1))
            ->filter(fn ($f) => $f['fecha'] >= $hoy)
            ->take($cantidad)
            ->values()
            ->all();
    }

    public function esFestivo(string $fecha): ?string
    {
        $anio = Carbon::parse($fecha)->year;

        return collect($this->delAnio($anio))->firstWhere('fecha', $fecha)['nombre'] ?? null;
    }
}
