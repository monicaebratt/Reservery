@extends('layouts.app')

@section('titulo', 'Reemplazos por prioridad')

@section('contenido')
<p class="text-muted">Cuando una solicitud de mayor prioridad necesita el horario de una reserva, el afectado recibe aviso y alternativas antes de que se confirme el cambio.</p>
<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($reemplazos as $rp)
            <a href="{{ route('reemplazos.show', $rp) }}" class="list-group-item list-group-item-action py-3 d-flex flex-wrap gap-2 align-items-center">
                <div class="me-auto">
                    <div class="fw-semibold">Reserva #{{ $rp->afectada->id }} · {{ $rp->afectada->objetos->pluck('nombre')->join(' + ') }}</div>
                    <small class="text-muted">{{ $rp->afectada->fecha->format('d/m/Y') }} {{ $rp->afectada->horario }} · {{ $rp->afectada->usuario->name }} ·
                        desplazada por <strong>{{ $rp->prioritaria->proposito->nombre }}</strong> (#{{ $rp->prioritaria->id }})</small>
                </div>
                <span class="badge text-bg-{{ $rp->estado === 'propuesto' ? 'warning' : 'light' }}">{{ $rp->estado_nombre }}</span>
            </a>
        @empty
            <div class="list-group-item text-center text-muted py-5">No hay reemplazos.</div>
        @endforelse
    </div>
</div>
@endsection
