<?php

namespace App\Services\Sustitucion;

use App\Models\ObjetoReservable;
use App\Services\Reservas\DisponibilidadService;
use Illuminate\Support\Collection;

/**
 * Sustitución automática de recursos (RF19, RF20, RF21).
 *
 * Equivalente = mismo tipo y misma categoría, capacidad igual o mayor y
 * ubicación cercana (primero la misma zona, luego el piso más cercano).
 * En una reserva compuesta, cada alternativa es un PAQUETE completo que
 * respeta los recursos asociados al espacio y está libre en conjunto.
 */
class SustitucionService
{
    public function __construct(private DisponibilidadService $disponibilidad)
    {
    }

    /**
     * @param  array<int>  $objetoIds  lo que el usuario pidió
     * @param  array<int>  $ignorar  reservas que no cuentan como conflicto
     * @return array<int, array{objetos: array<int>, items: array, descripcion: string, puntaje: int}>
     */
    public function buscarAlternativas(array $objetoIds, string $fecha, string $inicio, string $fin, array $ignorar = [], int $limite = 5): array
    {
        $originales = ObjetoReservable::with(['horarios', 'organizacion', 'recursosAsociados'])->whereIn('id', $objetoIds)->get();
        if ($originales->isEmpty()) {
            return [];
        }

        $espacio = $originales->firstWhere('tipo', 'espacio');
        $recursos = $originales->where('tipo', 'recurso')->values();
        $libre = fn (ObjetoReservable $o) => $this->disponibilidad->verificar([$o], $fecha, $inicio, $fin, $ignorar)->disponible();

        // Espacios candidatos: el mismo (por si solo fallan los recursos) y sus equivalentes
        $candidatosEspacio = $espacio
            ? collect([$espacio])->concat($this->equivalentes($espacio))
            : collect([null]);

        $alternativas = [];

        foreach ($candidatosEspacio as $candidato) {
            if ($candidato && ! $libre($candidato)) {
                continue;
            }

            $paquete = $candidato ? collect([$candidato]) : collect();
            $items = $candidato ? [$this->item($candidato, $espacio)] : [];
            $puntaje = $candidato ? $this->distancia($espacio, $candidato) : 0;
            $completo = true;

            foreach ($recursos as $recurso) {
                $permitidos = $candidato?->recursosAsociados->pluck('id');

                $elegido = collect([$recurso])->concat($this->equivalentes($recurso))
                    ->first(fn ($opcion) => (! $permitidos || $permitidos->contains($opcion->id))
                        && ! $paquete->contains('id', $opcion->id)
                        && $libre($opcion));

                if (! $elegido) {
                    $completo = false;
                    break;
                }

                $paquete->push($elegido);
                $items[] = $this->item($elegido, $recurso);
                $puntaje += $this->distancia($recurso, $elegido);
            }

            $ids = $paquete->pluck('id')->sort()->values()->all();
            $pedidos = collect($objetoIds)->sort()->values()->all();

            // RF21: el paquete completo debe estar libre al mismo tiempo
            if ($completo && $ids !== $pedidos
                && $this->disponibilidad->verificar($paquete, $fecha, $inicio, $fin, $ignorar)->disponible()) {
                $alternativas[] = [
                    'objetos' => $paquete->pluck('id')->all(),
                    'items' => $items,
                    'descripcion' => collect($items)->map(fn ($i) => $i['reemplaza'] ? "{$i['nombre']} en lugar de {$i['reemplaza']}" : $i['nombre'])->join(' + '),
                    'puntaje' => $puntaje,
                ];
            }
        }

        return collect($alternativas)->sortBy('puntaje')->take($limite)->values()->all();
    }

    /** Objetos equivalentes, ordenados del más cercano al más lejano. */
    public function equivalentes(ObjetoReservable $objeto, int $maximo = 10): Collection
    {
        return ObjetoReservable::with(['horarios', 'organizacion', 'recursosAsociados'])
            ->where('organizacion_id', $objeto->organizacion_id)
            ->where('tipo', $objeto->tipo)
            ->where('categoria', $objeto->categoria)
            ->where('disponible', true)
            ->where('id', '!=', $objeto->id)
            ->when($objeto->capacidad, fn ($q) => $q->where('capacidad', '>=', $objeto->capacidad))
            ->get()
            ->sortBy(fn ($o) => $this->distancia($objeto, $o))
            ->take($maximo)
            ->values();
    }

    /** Menor es mejor: misma zona, piso cercano y capacidad parecida. */
    private function distancia(ObjetoReservable $original, ObjetoReservable $candidato): int
    {
        if ($original->id === $candidato->id) {
            return 0;
        }

        $zona = ($original->zona && $original->zona === $candidato->zona) ? 0 : 100;
        $piso = abs(($original->piso ?? 0) - ($candidato->piso ?? 0)) * 10;
        $capacidad = min(abs(($candidato->capacidad ?? 0) - ($original->capacidad ?? 0)), 50);

        return 1 + $zona + $piso + $capacidad;
    }

    private function item(ObjetoReservable $elegido, ?ObjetoReservable $original): array
    {
        return [
            'id' => $elegido->id,
            'nombre' => $elegido->nombre,
            'tipo' => $elegido->tipo,
            'categoria' => $elegido->categoria,
            'capacidad' => $elegido->capacidad,
            'ubicacion' => $elegido->ubicacion,
            'zona' => $elegido->zona,
            'reemplaza' => $original && $original->id !== $elegido->id ? $original->nombre : null,
        ];
    }
}
