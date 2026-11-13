<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificacionResource;
use App\Models\Notificacion;
use Illuminate\Http\Request;

// Por API
class NotificacionApiController extends Controller
{
    // GET /api/notificaciones?sin_leer=1
    public function index(Request $request)
    {
        return NotificacionResource::collection(
            $request->user()->notificaciones()
                ->when($request->boolean('sin_leer'), fn ($q) => $q->where('leida', false))
                ->latest()->paginate(20)
        )->additional(['sin_leer' => $request->user()->notificaciones()->where('leida', false)->count()]);
    }

    // PATCH /api/notificaciones/{id}/leer
    public function leer(Request $request, Notificacion $notificacion)
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida' => true]);

        return new NotificacionResource($notificacion);
    }

    // POST /api/notificaciones/leer-todas
    public function leerTodas(Request $request)
    {
        $n = $request->user()->notificaciones()->where('leida', false)->update(['leida' => true]);

        return response()->json(['mensaje' => "{$n} notificación(es) marcadas como leídas."]);
    }
}
