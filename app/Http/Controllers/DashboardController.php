<?php

namespace App\Http\Controllers;

use App\Models\Reemplazo;
use App\Models\Reserva;
use App\Services\FestivosService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, FestivosService $festivos)
    {
        $usuario = $request->user()->load('organizacion');
        $esAdmin = $usuario->esAdmin();
        $base = fn () => Reserva::query()->when(! $esAdmin, fn ($q) => $q->where('user_id', $usuario->id));

        return view('dashboard', [
            'usuario' => $usuario,
            'esAdmin' => $esAdmin,
            'pendientes' => $base()->where('estado', 'pendiente')->count(),
            'aprobadas' => $base()->where('estado', 'aprobada')->whereDate('fecha', '>=', today())->count(),
            'hoy' => $base()->activas()->whereDate('fecha', today())->count(),
            'enEspera' => $usuario->listaEspera()->activas()->count(),
            'proximas' => $base()->with(['objetos', 'proposito'])->activas()
                ->whereDate('fecha', '>=', today())->orderBy('fecha')->orderBy('hora_inicio')->take(6)->get(),
            // Para el admin: solicitudes por revisar (HU19)
            'porRevisar' => $esAdmin ? Reserva::with(['objetos', 'usuario', 'proposito'])->where('estado', 'pendiente')
                ->orderBy('fecha')->orderBy('hora_inicio')->take(5)->get() : collect(),
            // Para el usuario: propuestas de reemplazo sin responder (HU11)
            'reemplazos' => Reemplazo::with(['prioritaria.proposito', 'afectada.objetos'])->where('estado', 'propuesto')
                ->whereHas('afectada', fn ($q) => $q->where('user_id', $usuario->id))->get(),
            'notificaciones' => $usuario->notificaciones()->latest()->take(4)->get(),
            'festivos' => $festivos->proximos(),
        ]);
    }
}
