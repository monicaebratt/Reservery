<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\ObjetoReservable;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ListaEspera\ListaEsperaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Gestión del inventario (RF10), horario operativo (RF06), paquetes
 * espacio + recursos (RF14) y bloqueos (RF07).
 */
class InventarioService
{
    public function __construct(private ListaEsperaService $listaEspera)
    {
    }

    public function guardar(?ObjetoReservable $objeto, array $datos, ?array $horarios, ?array $recursosAsociados, ?UploadedFile $imagen): ObjetoReservable
    {
        return DB::transaction(function () use ($objeto, $datos, $horarios, $recursosAsociados, $imagen) {
            if ($imagen) {
                if ($objeto?->imagen) {
                    Storage::disk('public')->delete($objeto->imagen);
                }
                $datos['imagen'] = $imagen->store('objetos', 'public');
            }

            $nuevo = ! $objeto;
            $objeto ??= new ObjetoReservable();
            $objeto->fill($datos)->save();

            if ($horarios !== null) {
                $this->guardarHorarios($objeto, $horarios);
            } elseif ($nuevo) {
                $objeto->aplicarHorarioPorDefecto();
            }

            if ($objeto->esEspacio()) {
                if ($recursosAsociados !== null) {
                    $objeto->recursosAsociados()->sync($recursosAsociados);
                }
            } else {
                $objeto->recursosAsociados()->detach();
            }

            return $objeto->fresh(['horarios', 'recursosAsociados']);
        });
    }

    /** @param array<int, array{abre?:bool, inicio?:string, fin?:string}> $horarios indexado por día 1..7 */
    public function guardarHorarios(ObjetoReservable $objeto, array $horarios): void
    {
        foreach (range(1, 7) as $dia) {
            $h = $horarios[$dia] ?? null;
            if ($h && ! empty($h['abre']) && ! empty($h['inicio']) && ! empty($h['fin'])) {
                $objeto->horarios()->updateOrCreate(['dia_semana' => $dia], [
                    'hora_inicio' => $h['inicio'],
                    'hora_fin' => $h['fin'],
                ]);
            } else {
                $objeto->horarios()->where('dia_semana', $dia)->delete();
            }
        }
    }

    public function eliminar(ObjetoReservable $objeto): void
    {
        if ($objeto->imagen) {
            Storage::disk('public')->delete($objeto->imagen);
        }
        $objeto->delete();
    }

    /** Reservas activas que quedarían dentro de un bloqueo. */
    public function reservasAfectadas(array $objetoIds, Carbon $inicio, Carbon $fin)
    {
        return Reserva::with(['objetos', 'usuario'])->activas()
            ->whereHas('objetos', fn ($q) => $q->whereIn('objetos_reservables.id', $objetoIds))
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get()
            ->filter(fn ($r) => Carbon::parse($r->fecha->toDateString().' '.$r->hora_inicio) < $fin
                && Carbon::parse($r->fecha->toDateString().' '.$r->hora_fin) > $inicio)
            ->values();
    }

    /**
     * RF07: crea un bloqueo por cada objeto. Si se pide, cancela las reservas
     * que caen dentro (la cancelación pasa por el patrón State y notifica).
     */
    public function bloquear(array $objetoIds, Carbon $inicio, Carbon $fin, string $motivo, User $por, bool $cancelarAfectadas): array
    {
        return DB::transaction(function () use ($objetoIds, $inicio, $fin, $motivo, $por, $cancelarAfectadas) {
            $bloqueos = collect($objetoIds)->map(fn ($id) => Bloqueo::create([
                'organizacion_id' => $por->organizacion_id,
                'objeto_reservable_id' => $id,
                'creado_por' => $por->id,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'motivo' => $motivo,
            ]));

            $canceladas = 0;
            if ($cancelarAfectadas) {
                foreach ($this->reservasAfectadas($objetoIds, $inicio, $fin) as $reserva) {
                    $reserva->cancelar($por, "Bloqueo del horario: {$motivo}.");
                    $canceladas++;
                }
            }

            return ['bloqueos' => $bloqueos, 'canceladas' => $canceladas];
        });
    }

    /** Quitar un bloqueo libera ese horario: se revisa la lista de espera. */
    public function desbloquear(Bloqueo $bloqueo): void
    {
        $bloqueo->delete();

        $dia = $bloqueo->fecha_inicio->copy()->startOfDay();
        while ($dia <= $bloqueo->fecha_fin) {
            $this->listaEspera->procesarLiberacion([$bloqueo->objeto_reservable_id], $dia->toDateString(), '00:00', '23:59', $bloqueo->organizacion_id);
            $dia->addDay();
        }
    }
}
