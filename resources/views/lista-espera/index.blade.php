@extends('layouts.app')

@section('titulo', 'Lista de espera')

@section('contenido')
<p class="text-muted">Te avisamos por orden de llegada cuando el horario que pediste se libera. Si te llega el aviso, resérvalo pronto: el turno no queda apartado.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Pedido</th><th>Fecha y horario</th>@if (auth()->user()->esAdmin()) <th>Persona</th> @endif<th>Inscrito</th><th>Posición</th><th>Estado</th><th class="text-end"></th></tr>
            </thead>
            <tbody>
                @forelse ($entradas as $e)
                    <tr class="{{ $e->estado === 'notificada' ? 'table-success' : '' }}">
                        <td><div class="fw-semibold">{{ $e->objeto->nombre }}</div><small class="text-muted">{{ $e->motivo }}{{ count($e->objetos) > 1 ? ' · paquete de '.count($e->objetos) : '' }}</small></td>
                        <td class="text-nowrap">{{ $e->fecha->format('d/m/Y') }}<br><small>{{ substr($e->hora_inicio, 0, 5) }}–{{ substr($e->hora_fin, 0, 5) }}</small></td>
                        @if (auth()->user()->esAdmin()) <td>{{ $e->usuario->name }}</td> @endif
                        <td><small>{{ $e->created_at->format('d/m H:i') }}</small></td>
                        <td>{{ $e->posicion ? '#'.$e->posicion : '—' }}</td>
                        <td><span class="badge text-bg-{{ ['notificada' => 'success', 'esperando' => 'warning', 'atendida' => 'primary'][$e->estado] ?? 'secondary' }}">{{ $e->estado_nombre }}</span></td>
                        <td class="text-end text-nowrap">
                            @if ($e->user_id === auth()->id() && in_array($e->estado, ['esperando', 'notificada']))
                                @if ($e->estado === 'notificada')
                                    <form method="POST" action="{{ route('lista-espera.convertir', $e) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-success"><i class="bi bi-lightning-charge"></i> Reservar ahora</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('lista-espera.destroy', $e) }}" class="d-inline">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-secondary">Salir</button>
                                </form>
                            @elseif ($e->reserva_id)
                                <a href="{{ route('reservas.show', $e->reserva_id) }}" class="btn btn-sm btn-outline-primary">Ver reserva</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No hay inscripciones en la lista de espera.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
