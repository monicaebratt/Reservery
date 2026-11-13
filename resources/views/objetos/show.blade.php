@extends('layouts.app')

@section('titulo', $objeto->nombre)

@section('contenido')
<div class="row g-4">
    <div class="col-lg-5 d-flex flex-column gap-4">
        <div class="card">
            <img src="{{ $objeto->imagen_url }}" class="objeto-img" alt="{{ $objeto->nombre }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge text-bg-{{ $objeto->esEspacio() ? 'primary' : 'info' }}">{{ $objeto->tipo_nombre }} · {{ $objeto->categoria }}</span>
                    <span class="badge text-bg-{{ $objeto->disponible ? 'success' : 'secondary' }}">{{ $objeto->disponible ? 'Habilitado' : 'Inhabilitado' }}</span>
                </div>
                <p>{{ $objeto->descripcion ?: 'Sin descripción.' }}</p>
                <dl class="row small mb-0">
                    <dt class="col-5">Ubicación</dt><dd class="col-7">{{ $objeto->ubicacion ?? '—' }}</dd>
                    <dt class="col-5">Zona / piso</dt><dd class="col-7">{{ $objeto->zona ?? '—' }}{{ $objeto->piso !== null ? ' · piso '.$objeto->piso : '' }}</dd>
                    <dt class="col-5">Capacidad</dt><dd class="col-7">{{ $objeto->capacidad ? $objeto->capacidad.' personas' : '—' }}</dd>
                </dl>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                @unless (auth()->user()->esAdmin())
                    <a href="{{ route('reservas.create', ['objeto' => $objeto->id]) }}" class="btn btn-primary btn-sm">Reservar</a>
                @endunless
                <a href="{{ route('disponibilidad.index') }}" class="btn btn-outline-primary btn-sm">Ver disponibilidad</a>
                @if (auth()->user()->esAdmin())
                    <a href="{{ route('objetos.edit', $objeto) }}" class="btn btn-outline-secondary btn-sm ms-auto">Editar</a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">Horario operativo</div>
            <ul class="list-group list-group-flush">
                @foreach ($dias as $n => $dia)
                    @php($h = $objeto->horarios->firstWhere('dia_semana', $n))
                    <li class="list-group-item d-flex justify-content-between small">
                        <span>{{ $dia }}</span>
                        <span class="{{ $h ? '' : 'text-muted' }}">{{ $h ? substr($h->hora_inicio, 0, 5).' – '.substr($h->hora_fin, 0, 5) : 'Cerrado' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-lg-7 d-flex flex-column gap-4">
        @if ($objeto->esEspacio())
            <div class="card">
                <div class="card-header">Recursos que se pueden reservar con este espacio</div>
                <div class="card-body">
                    @forelse ($objeto->recursosAsociados as $r)
                        <a href="{{ route('objetos.show', $r) }}" class="badge text-bg-light text-decoration-none fs-6 fw-normal me-1 mb-1">{{ $r->nombre }}</a>
                    @empty
                        <span class="text-muted small">Ninguno.</span>
                    @endforelse
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header">Se puede reservar junto con</div>
                <div class="card-body">
                    @forelse ($objeto->espaciosAsociados as $e)
                        <a href="{{ route('objetos.show', $e) }}" class="badge text-bg-light text-decoration-none fs-6 fw-normal me-1 mb-1">{{ $e->nombre }}</a>
                    @empty
                        <span class="text-muted small">Solo se reserva de forma independiente.</span>
                    @endforelse
                </div>
            </div>
        @endif

        @if ($objeto->bloqueos->isNotEmpty())
            <div class="card border-dark">
                <div class="card-header">Bloqueos programados</div>
                <ul class="list-group list-group-flush">
                    @foreach ($objeto->bloqueos as $b)
                        <li class="list-group-item small"><i class="bi bi-slash-circle"></i> {{ $b->fecha_inicio->format('d/m/Y H:i') }} – {{ $b->fecha_fin->format('d/m/Y H:i') }} · {{ $b->motivo }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">Próximas reservas</div>
            <ul class="list-group list-group-flush">
                @forelse ($objeto->reservas as $r)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><div class="fw-semibold">{{ auth()->user()->esAdmin() || $r->user_id === auth()->id() ? $r->motivo : 'Reservado' }}</div>
                            <small class="text-muted">{{ $r->fecha->format('d/m/Y') }} · {{ $r->horario }} · {{ $r->proposito->nombre }}</small></div>
                        @include('partials.estado', ['estado' => $r->estado])
                    </li>
                @empty
                    <li class="list-group-item text-muted">No tiene reservas próximas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
