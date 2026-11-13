<?php

namespace App\Http\Controllers;

use App\Models\ObjetoReservable;
use App\Services\Analitica\AnaliticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** RF23, RF24, RF25 / HU15–HU17: analítica para administradores. */
class AnaliticaController extends Controller
{
    public function __construct(private AnaliticaService $analitica)
    {
    }

    public function index(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);
        $org = $request->user()->organizacion;
        $tipo = $request->query('tipo');

        return view('analitica.index', [
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $tipo,
            'categoria' => $request->query('categoria'),
            'categorias' => ObjetoReservable::query()->distinct()->orderBy('categoria')->pluck('categoria'),
            'reporte' => $this->analitica->ocupacion($org, $desde, $hasta, $tipo),
            'demanda' => $this->analitica->demanda($org, $desde, $hasta, $tipo, $request->query('categoria')),
            'resumen' => $this->analitica->resumen($org, $desde, $hasta),
        ]);
    }

    /** RF23: el reporte de ocupación en PDF. */
    public function pdf(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);
        $usuario = $request->user();

        $reporte = $this->analitica->ocupacion($usuario->organizacion, $desde, $hasta, $request->query('tipo'));

        return $reporte->generarReporte($usuario->organizacion->nombre, $usuario->name)
            ->download('reporte-ocupacion-'.$desde->format('Y-m-d').'-a-'.$hasta->format('Y-m-d').'.pdf');
    }

    private function periodo(Request $request): array
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'tipo' => ['nullable', 'in:espacio,recurso'],
        ]);

        return [
            Carbon::parse($request->query('desde', today()->subDays(29)->toDateString())),
            Carbon::parse($request->query('hasta', today()->toDateString())),
        ];
    }
}
