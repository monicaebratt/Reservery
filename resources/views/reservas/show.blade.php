@extends('layouts.app')

@section('titulo', 'Reserva #'.$reserva->id)

@section('contenido')
@php
    $iconos = ['aprobar' => 'check-lg', 'rechazar' => 'x-lg', 'cancelar' => 'slash-circle', 'completar' => 'flag'];
    $colores = ['aprobar' => 'success', 'rechazar' => 'danger', 'cancelar' => 'secondary', 'completar' => 'primary'];
    $esAdmin = auth()->user()->esAdmin();
    $recibido = $reserva->reemplazosRecibidos->firstWhere('estado', 'propuesto');
@endphp

@if ($recibido && $reserva->user_id === auth()->id())
    <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span class="me-auto">Una reserva de mayor prioridad ({{ $recibido->prioritaria->proposito->nombre }}) necesita este horario.</span>
        <a href="{{ url('/reemplazos/'.$recibido->id) }}" class="btn btn-sm btn-dark">Ver alternativas</a>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8 d-flex flex-column gap-4">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1">{{ $reserva->motivo }}</h2>
                        <div class="text-muted">Solicitada por {{ $reserva->usuario->name }} · {{ $reserva->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @include('partials.estado', ['estado' => $reserva->estado])
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">{{ ucfirst($reserva->fecha->translatedFormat('l d \d\e F \d\e Y')) }}</dd>
                    <dt class="col-sm-3">Horario</dt><dd class="col-sm-9">{{ $reserva->horario }}</dd>
                    <dt class="col-sm-3">Propósito</dt>
                    <dd class="col-sm-9"><span class="proposito"><i style="background: {{ $reserva->proposito->color }}"></i>{{ $reserva->proposito->nombre }}</span> @if ($esAdmin) <small class="text-muted">prioridad {{ $reserva->proposito->nivel_prioridad }}</small> @endif</dd>
                    <dt class="col-sm-3">Tipo</dt><dd class="col-sm-9">{{ $reserva->es_compuesta ? 'Compuesta (espacio + recursos)' : 'Simple' }}</dd>
                </dl>
                <h3 class="h6 fw-bold mt-4">Incluye</h3>
                <div class="row g-2">
                    @foreach ($reserva->objetos as $o)
                        <div class="col-sm-6"><a href="{{ Route::has('objetos.show') ? route('objetos.show', $o) : '#' }}" class="d-flex gap-2 align-items-center border rounded-3 p-2 text-decoration-none text-reset">
                            <img src="{{ $o->imagen_url }}" class="miniatura" alt="">
                            <div><div class="fw-semibold">{{ $o->nombre }}</div><small class="text-muted">{{ $o->tipo_nombre }} · {{ $o->categoria }}{{ $o->ubicacion ? ' · '.$o->ubicacion : '' }}</small></div>
                        </a></div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($reserva->reemplazosPropuestos->isNotEmpty())
            {{-- Esta reserva desplaza a otras de menor prioridad --}}
            <div class="card border-warning">
                <div class="card-header">Reservas que desplaza por prioridad</div>
                <ul class="list-group list-group-flush">
                    @foreach ($reserva->reemplazosPropuestos as $rp)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                @if ($esAdmin) <a href="{{ route('reservas.show', $rp->reserva_afectada_id) }}">#{{ $rp->reserva_afectada_id }}</a> @else #{{ $rp->reserva_afectada_id }} @endif
                                · {{ $rp->afectada->usuario->name }}
                            </span>
                            <span class="badge text-bg-light">{{ $rp->estado_nombre }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($reserva->estado === 'pendiente' && $esAdmin)
                    <div class="card-footer bg-transparent small text-muted">Al aprobar esta reserva se confirma el reemplazo: quien no aceptó una alternativa quedará cancelado y notificado.</div>
                @endif
            </div>
        @endif

        <div class="card">
            <div class="card-header">Historial de cambios</div>
            <div class="card-body">
                {{-- Cada cambio con fecha, hora y responsable --}}
                <ul class="historial mb-0">
                    @foreach ($reserva->historial as $h)
                        <li>
                            <div class="fw-semibold small">{{ ucfirst(str_replace('_', ' ', $h->accion)) }}
                                @if ($h->estado_anterior && $h->estado_anterior !== $h->estado_nuevo) <span class="text-muted fw-normal">({{ $h->estado_anterior }} → {{ $h->estado_nuevo }})</span> @endif
                            </div>
                            @if ($h->detalle) <div class="small">{{ $h->detalle }}</div> @endif
                            <small class="text-muted">{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->responsable_nombre }}</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card position-sticky" style="top: 80px">
            <div class="card-header">Acciones</div>
            <div class="card-body d-grid gap-2">
                {{-- Solo aparecen las acciones que permite el estado actual (patrón State) --}}
                @forelse ($reserva->accionesPermitidas() as $accion)
                    @if ($accion === 'cancelar' || $esAdmin)
                        <form method="POST" action="{{ route('reservas.estado', [$reserva, $accion]) }}" class="d-grid gap-1"
                              @if ($accion === 'cancelar') onsubmit="return confirm('¿Cancelar la reserva? El horario quedará libre.')" @endif>
                            @csrf @method('PATCH')
                            @if (in_array($accion, ['rechazar', 'cancelar']))
                                <input type="text" name="motivo" class="form-control form-control-sm" placeholder="Motivo (opcional)">
                            @endif
                            <button class="btn btn-outline-{{ $colores[$accion] }}"><i class="bi bi-{{ $iconos[$accion] }}"></i> {{ ucfirst($accion) }}</button>
                        </form>
                    @endif
                @empty
                    <p class="text-muted small mb-0">La reserva está en un estado final: ya no admite cambios.</p>
                @endforelse
                @if ($reserva->puedeEditarse())
                    <a href="{{ route('reservas.edit', $reserva) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Editar</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
