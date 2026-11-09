<?php

namespace App\Http\Controllers;

use App\Models\ObjetoReservable;
use App\Services\Reservas\DisponibilidadService;
use App\Services\Reservas\ReservaService;
use Illuminate\Http\Request;

/** RF02 / HU03: disponibilidad real antes de reservar, sin recargar la página. */
class DisponibilidadController extends Controller
{
    public function index(Request $request)
    {
        return view('disponibilidad.index', [
            'fecha' => $request->query('fecha', today()->toDateString()),
            'categorias' => ObjetoReservable::query()->distinct()->orderBy('categoria')->pluck('categoria'),
        ]);
    }

    /** JSON que la página consulta cada pocos segundos. */
    public function agenda(Request $request, DisponibilidadService $disponibilidad)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
            'categoria' => ['nullable', 'string'],
            'objetos' => ['nullable', 'array'],
        ]);

        $agenda = collect($disponibilidad->agenda($datos['objetos'] ?? null, $datos['fecha'], $datos['tipo'] ?? null, $request->user()->esAdmin()))
            ->when($datos['categoria'] ?? null, fn ($c, $cat) => $c->where('categoria', $cat))
            ->values();

        return response()->json(['fecha' => $datos['fecha'], 'actualizado' => now()->format('H:i:s'), 'objetos' => $agenda]);
    }

    /** Vista semanal: la agenda de los 7 días de la semana de la fecha dada (lunes a domingo). */
    public function semana(Request $request, DisponibilidadService $disponibilidad)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
            'categoria' => ['nullable', 'string'],
        ]);

        $lunes = \Illuminate\Support\Carbon::parse($datos['fecha'])->startOfWeek();
        $dias = collect(range(0, 6))->map(fn ($i) => $lunes->copy()->addDays($i));
        $verDetalle = $request->user()->esAdmin();

        $objetos = [];
        foreach ($dias as $dia) {
            $agenda = collect($disponibilidad->agenda(null, $dia->toDateString(), $datos['tipo'] ?? null, $verDetalle))
                ->when($datos['categoria'] ?? null, fn ($c, $cat) => $c->where('categoria', $cat));
            foreach ($agenda as $o) {
                $objetos[$o['id']] ??= collect($o)->only(['id', 'nombre', 'tipo', 'categoria', 'capacidad', 'ubicacion', 'imagen_url'])->all() + ['dias' => []];
                $objetos[$o['id']]['dias'][$dia->toDateString()] = collect($o)->only(['disponible', 'horario', 'festivo', 'ocupado', 'libre'])->all();
            }
        }

        return response()->json([
            'dias' => $dias->map(fn ($d) => [
                'fecha' => $d->toDateString(),
                'nombre' => ucfirst($d->translatedFormat('D')),
                'numero' => $d->format('d'),
                'mes' => $d->translatedFormat('M'),
                'pasado' => $d->lt(today()),
                'hoy' => $d->isToday(),
            ]),
            'rango' => $lunes->translatedFormat('d M').' – '.$lunes->copy()->addDays(6)->translatedFormat('d M Y'),
            'actualizado' => now()->format('H:i:s'),
            'objetos' => array_values($objetos),
        ]);
    }

    /** Revisa en vivo una solicitud mientras el usuario la arma (RF04, RF15). */
    public function verificar(Request $request, DisponibilidadService $disponibilidad, ReservaService $reservas)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'objetos' => ['required', 'array', 'min:1'],
            'ignorar' => ['nullable', 'integer'],
        ]);

        $objetos = $reservas->cargarObjetos($datos['objetos']);
        $resultado = $disponibilidad->verificar($objetos, $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], array_filter([$datos['ignorar'] ?? null]));

        return response()->json($resultado->toArray() + [
            'objetos' => $objetos->map(fn ($o) => [
                'id' => $o->id,
                'nombre' => $o->nombre,
                'libre' => ! collect($resultado->problemas)->contains('objeto_id', $o->id),
            ])->values(),
        ]);
    }
}
