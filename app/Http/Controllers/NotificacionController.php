<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

// Centro de notificaciones (la campana se actualiza sola)
class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        return view('notificaciones.index', [
            'notificaciones' => $request->user()->notificaciones()->latest()->paginate(15),
        ]);
    }

    // La campana del menú pregunta esto cada 20 segundos
    public function contador(Request $request)
    {
        $usuario = $request->user();

        return response()->json([
            'sin_leer' => $usuario->notificaciones()->where('leida', false)->count(),
            'ultimas' => $usuario->notificaciones()->latest()->take(5)->get(['id', 'titulo', 'mensaje', 'url', 'leida', 'created_at'])
                ->map(fn ($n) => $n->toArray() + ['hace' => $n->created_at->diffForHumans()]),
        ]);
    }

    public function leer(Request $request, Notificacion $notificacion)
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida' => true]);

        return $notificacion->url ? redirect($notificacion->url) : back();
    }

    public function leerTodas(Request $request)
    {
        $request->user()->notificaciones()->where('leida', false)->update(['leida' => true]);

        return back()->with('ok', 'Todas las notificaciones quedaron como leídas.');
    }
}
