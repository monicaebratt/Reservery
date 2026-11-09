@extends('layouts.app')

@section('titulo', $verTodas ? 'Solicitudes' : 'Mis reservas')
@section('subtitulo', $verTodas ? 'Todas las reservas de tu organización.' : 'Consulta y gestiona tus reservas.')

@section('acciones')
    @unless (auth()->user()->esAdmin())
        <a href="{{ route('reservas.create') }}" class="btn btn-primary d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg"></i> Nueva reserva</a>
    @endunless
@endsection

@section('contenido')
@php($pestanas = ['proximas' => ['bi-calendar-event', 'Próximas'], 'anteriores' => ['bi-clock-history', 'Anteriores'], 'canceladas' => ['bi-x-circle', 'Canceladas'], 'todas' => ['bi-list-ul', 'Todas']])

<div class="d-flex flex-wrap gap-3 align-items-center mb-4">
    <nav class="segmentado" aria-label="Filtrar reservas">
        @foreach ($pestanas as $clave => [$icono, $texto])
            <a href="{{ request()->fullUrlWithQuery(['pestana' => $clave, 'estado' => null, 'page' => null]) }}" class="{{ $pestana === $clave ? 'activo' : '' }}"><i class="bi {{ $icono }}"></i>{{ $texto }}</a>
        @endforeach
    </nav>

    <form method="GET" class="buscador ms-lg-auto flex-grow-1" style="max-width: 360px">
        <input type="hidden" name="pestana" value="{{ $pestana }}">
        @if (request('ver')) <input type="hidden" name="ver" value="{{ request('ver') }}"> @endif
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="{{ $buscar }}" class="form-control" placeholder="Buscar por espacio, motivo{{ $verTodas ? ' o persona' : '' }}…" aria-label="Buscar">
    </form>

    @if (auth()->user()->esAdmin())
        <label class="filtro-pastilla">
            <i class="bi bi-funnel"></i>
            <select onchange="location = this.value" aria-label="Estado">
                <option value="{{ request()->fullUrlWithQuery(['estado' => null, 'page' => null]) }}">Cualquier estado</option>
                @foreach (array_keys(\App\Models\Reserva::ESTADOS) as $e)
                    <option value="{{ request()->fullUrlWithQuery(['estado' => $e, 'pestana' => 'todas', 'page' => null]) }}" @selected(request('estado') === $e)>{{ ucfirst($e) }}</option>
                @endforeach
            </select>
        </label>
        <a href="{{ request()->fullUrlWithQuery(['ver' => $verTodas ? 'mias' : null, 'page' => null]) }}" class="btn btn-light btn-sm">{{ $verTodas ? 'Ver solo las mías' : 'Ver todas' }}</a>
    @endif
</div>

<div class="d-flex flex-column gap-3">
    @forelse ($reservas as $r)
        <article class="reserva-tarjeta fondo-{{ $r->estado }}">
            <img src="{{ $r->espacio?->imagen_url ?? $r->objetos->first()?->imagen_url }}" alt="" class="foto">
            <div class="cuerpo-tarjeta">
                <h3><a href="{{ route('reservas.show', $r) }}" class="text-reset text-decoration-none">{{ $r->espacio?->nombre ?? $r->objetos->pluck('nombre')->join(' + ') }}</a></h3>
                <div class="meta">
                    <span><i class="bi bi-calendar3"></i>{{ ucfirst($r->fecha->translatedFormat('l, d \d\e F Y')) }}</span>
                    <span><i class="bi bi-clock"></i>{{ $r->horario }}</span>
                    @if ($r->espacio?->ubicacion) <span><i class="bi bi-geo-alt"></i>{{ $r->espacio->ubicacion }}</span> @endif
                </div>
                <hr>
                <div class="meta">
                    <span><i class="bi bi-chat-left-text"></i>{{ $r->motivo }}</span>
                    <span class="proposito"><i style="background: {{ $r->proposito->color }}"></i>{{ $r->proposito->nombre }}</span>
                    @if ($r->espacio && $r->objetos->count() > 1)
                        <span><i class="bi bi-box-seam"></i>{{ $r->objetos->where('id', '!=', $r->espacio->id)->pluck('nombre')->join(', ') }}</span>
                    @endif
                    @if ($verTodas) <span><i class="bi bi-person"></i>{{ $r->usuario->name }}</span> @endif
                </div>
            </div>
            <div class="lado">
                @include('partials.estado', ['estado' => $r->estado])
                <div class="acciones-fila justify-content-end">
                    <a href="{{ route('reservas.show', $r) }}" class="btn btn-sm btn-light">Ver detalle</a>
                    @if ($r->puedeEditarse() && $r->user_id === auth()->id())
                        <a href="{{ route('reservas.edit', $r) }}" class="btn btn-sm btn-light" title="Editar"><i class="bi bi-pencil"></i></a>
                    @endif
                    @if (in_array('cancelar', $r->accionesPermitidas()) && $r->user_id === auth()->id())
                        <form method="POST" action="{{ route('reservas.estado', [$r, 'cancelar']) }}" onsubmit="return confirm('¿Cancelar esta reserva? El horario quedará libre.')">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="card">
            <div class="vacio">
                <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono-vacio">
                <div class="mb-3">{{ $buscar !== '' ? 'No encontramos reservas con esa búsqueda.' : 'No hay reservas en esta pestaña.' }}</div>
                @unless (auth()->user()->esAdmin()) <a href="{{ route('reservas.create') }}" class="btn btn-primary btn-sm">Hacer una reserva</a> @endunless
            </div>
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $reservas->links() }}</div>
@endsection
