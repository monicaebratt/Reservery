<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analitica\AnaliticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** RF23, RF24 y RF25 por API (solo administradores). */
class AnaliticaApiController extends Controller
{
    public function __construct(private AnaliticaService $analitica)
    {
    }

    /** GET /api/analitica/ocupacion?desde&hasta&tipo */
    public function ocupacion(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);

        return response()->json($this->analitica->ocupacion($request->user()->organizacion, $desde, $hasta, $request->query('tipo'))->toArray());
    }

    /** GET /api/analitica/subutilizados?desde&hasta */
    public function subutilizados(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);
        $reporte = $this->analitica->ocupacion($request->user()->organizacion, $desde, $hasta, $request->query('tipo'));

        return response()->json(['umbral' => $reporte->umbralSubutilizacion, 'data' => $reporte->subutilizados()]);
    }

    /** GET /api/analitica/demanda?desde&hasta&tipo&categoria */
    public function demanda(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);

        return response()->json($this->analitica->demanda($request->user()->organizacion, $desde, $hasta, $request->query('tipo'), $request->query('categoria')));
    }

    /** GET /api/analitica/reporte-pdf?desde&hasta — descarga el PDF */
    public function pdf(Request $request)
    {
        [$desde, $hasta] = $this->periodo($request);
        $usuario = $request->user();

        return $this->analitica->ocupacion($usuario->organizacion, $desde, $hasta, $request->query('tipo'))
            ->generarReporte($usuario->organizacion->nombre, $usuario->name)
            ->download("reporte-ocupacion-{$desde->toDateString()}.pdf");
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
