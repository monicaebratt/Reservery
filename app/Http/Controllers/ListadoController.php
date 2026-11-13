<?php

namespace App\Http\Controllers;

use App\Models\ObjetoReservable;
use App\Models\Reserva;
use Illuminate\Http\Request;

// Listados con DataTables (búsqueda, orden y paginación en el navegador)
class ListadoController extends Controller
{
    public function reservas(Request $request)
    {
        $usuario = $request->user();

        return view('listados.reservas', [
            'reservas' => Reserva::with(['usuario', 'objetos', 'proposito'])
                ->when(! $usuario->esAdmin(), fn ($q) => $q->where('user_id', $usuario->id))
                ->orderByDesc('fecha')->get(),
        ]);
    }

    public function objetos()
    {
        return view('listados.objetos', [
            'objetos' => ObjetoReservable::withCount(['reservas', 'recursosAsociados'])->orderBy('nombre')->get(),
        ]);
    }
}
