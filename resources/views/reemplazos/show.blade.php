@extends('layouts.app')

@section('titulo', 'Propuesta de reemplazo')

@section('contenido')
@php($afectada = $reemplazo->afectada)
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h2 class="h5 fw-bold mb-1">Tu reserva #{{ $afectada->id }}: {{ $afectada->motivo }}</h2>
                <div class="text-muted">{{ $afectada->objetos->pluck('nombre')->join(' + ') }} · {{ $afectada->fecha->format('d/m/Y') }} {{ $afectada->horario }} · {{ $afectada->proposito->nombre }}</div>
            </div>
            <span class="badge text-bg-{{ $reemplazo->estado === 'propuesto' ? 'warning' : 'light' }} align-self-start">{{ $reemplazo->estado_nombre }}</span>
        </div>
        <hr>
        <p class="mb-0">Una solicitud de <strong>{{ $reemplazo->prioritaria->proposito->nombre }}</strong>, que tiene mayor prioridad,
            necesita {{ $reemplazo->prioritaria->objetos->pluck('nombre')->join(' + ') }} en ese horario.
            Si el administrador la aprueba, tu reserva se cancela, salvo que elijas una alternativa.</p>
    </div>
</div>

@if ($reemplazo->estado === 'propuesto' && $afectada->user_id === auth()->id())
    <h3 class="h6 fw-bold">Alternativas equivalentes libres en tu mismo horario</h3>
    @forelse ($reemplazo->alternativas ?? [] as $i => $alt)
        <div class="alternativa mb-3 d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <div>
                @foreach ($alt['items'] as $item)
                    <div><span class="fw-semibold">{{ $item['nombre'] }}</span> <small class="text-muted">· {{ $item['categoria'] }}{{ $item['ubicacion'] ? ' · '.$item['ubicacion'] : '' }}</small>
                        @if ($item['reemplaza']) <span class="badge text-bg-warning">en lugar de {{ $item['reemplaza'] }}</span> @endif</div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('reemplazos.alternativa', $reemplazo) }}">@csrf
                <input type="hidden" name="alternativa" value="{{ $i }}">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Aceptar esta alternativa</button>
            </form>
        </div>
    @empty
        <div class="card card-body text-muted mb-3">No hay alternativas equivalentes libres en tu horario.</div>
    @endforelse

    <form method="POST" action="{{ route('reemplazos.ceder', $reemplazo) }}" onsubmit="return confirm('Tu reserva se cancelará cuando aprueben la solicitud prioritaria. ¿Continuar?')">@csrf
        <button class="btn btn-outline-secondary">Ceder mi horario sin alternativa</button>
    </form>
@endif
@endsection
