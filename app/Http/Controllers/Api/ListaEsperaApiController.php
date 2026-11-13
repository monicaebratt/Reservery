<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudReservaRequest;
use App\Http\Resources\ListaEsperaResource;
use App\Http\Resources\ReservaResource;
use App\Models\ListaEspera;
use App\Services\ListaEspera\ListaEsperaService;
use Illuminate\Http\Request;

// Por API
class ListaEsperaApiController extends Controller
{
    public function __construct(private ListaEsperaService $listaEspera)
    {
    }

    // GET /api/lista-espera
    public function index(Request $request)
    {
        return ListaEsperaResource::collection(
            ListaEspera::with('objeto')->where('user_id', $request->user()->id)->orderBy('fecha')->get()
        );
    }

    // POST /api/lista-espera — mismos datos que una reserva
    public function store(SolicitudReservaRequest $request)
    {
        $entrada = $this->listaEspera->inscribir($request->user(), $request->datos());

        return (new ListaEsperaResource($entrada->load('objeto')))->response()->setStatusCode(201);
    }

    // POST /api/lista-espera/{id}/convertir
    public function convertir(Request $request, ListaEspera $entrada)
    {
        return (new ReservaResource($this->listaEspera->convertir($entrada, $request->user())))->response()->setStatusCode(201);
    }

    // DELETE /api/lista-espera/{id}
    public function destroy(Request $request, ListaEspera $entrada)
    {
        $this->listaEspera->cancelar($entrada, $request->user());

        return response()->json(['mensaje' => 'Saliste de la lista de espera.']);
    }
}
