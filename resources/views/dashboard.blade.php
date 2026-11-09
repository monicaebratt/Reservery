@extends('layouts.app')

@section('titulo', 'Hola, '.\Illuminate\Support\Str::of($usuario->name)->before(' '))
@section('subtitulo', $esAdmin ? 'Esto es lo que necesita tu atención hoy.' : 'Aquí tienes un resumen de tus reservas.')

@section('acciones')
    @unless ($esAdmin)
        <a href="{{ route('reservas.create') }}" class="btn btn-primary d-none d-md-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg"></i> Nueva reserva</a>
    @endunless
@endsection

@php($destello = '<svg class="destello" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>')

@section('contenido')
@foreach ($reemplazos as $rp)
    <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center">
        <i class="bi bi-exclamation-circle"></i>
        <div class="me-auto">Tu reserva de {{ $rp->afectada->objetos->pluck('nombre')->join(' + ') }} choca con una solicitud más importante ({{ $rp->prioritaria->proposito->nombre }}). Elige qué hacer.</div>
        <a href="{{ url('/reemplazos/'.$rp->id) }}" class="btn btn-sm btn-primary">Ver alternativas</a>
    </div>
@endforeach

{{-- Indicadores --}}
<div class="row g-3 mb-4">
    @foreach ([
        ['bi-hourglass-split', $pendientes, $esAdmin ? 'Por aprobar' : 'Pendientes'],
        ['bi-calendar-check', $aprobadas, 'Aprobadas próximas'],
        ['bi-sun', $hoy, 'Para hoy'],
        ['bi-people', $enEspera, 'En lista de espera'],
    ] as [$icono, $valor, $etiqueta])
        <div class="col-6 col-xl-3">
            <div class="indicador">
                <span class="circulo"><i class="bi {{ $icono }}"></i></span>
                <div><div class="numero">{{ $valor }}</div><div class="etiqueta">{{ $etiqueta }}</div></div>
                {!! str_replace('class="destello"', 'class="chispa"', $destello) !!}
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8 d-flex flex-column gap-4">
        @if ($esAdmin)
            <section class="card">
                <div class="card-header">{!! $destello !!} Solicitudes por revisar
                    <a href="{{ route('reservas.index', ['estado' => 'pendiente']) }}" class="ver-todas">Ver todas <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="filas">
                    @forelse ($porRevisar as $r)
                        <div class="fila-suave">
                            <img src="{{ $r->espacio?->imagen_url ?? $r->objetos->first()?->imagen_url }}" alt="" class="foto">
                            <div class="principal">
                                <a href="{{ route('reservas.show', $r) }}" class="titulo text-reset text-decoration-none">{{ $r->objetos->pluck('nombre')->join(' + ') }}</a>
                                <div class="meta">
                                    <span><i class="bi bi-person"></i>{{ $r->usuario->name }}</span>
                                    <span><i class="bi bi-calendar3"></i>{{ $r->fecha->translatedFormat('D d M') }}</span>
                                    <span><i class="bi bi-clock"></i>{{ $r->horario }}</span>
                                    <span class="proposito"><i style="background: {{ $r->proposito->color }}"></i>{{ $r->proposito->nombre }}</span>
                                </div>
                            </div>
                            <div class="acciones-fila">
                                <form method="POST" action="{{ route('reservas.estado', [$r, 'aprobar']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Aprobar</button></form>
                                <form method="POST" action="{{ route('reservas.estado', [$r, 'rechazar']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Rechazar</button></form>
                            </div>
                        </div>
                    @empty
                        <div class="vacio"><img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono-vacio"><div>No hay solicitudes pendientes. Todo al día.</div></div>
                    @endforelse
                </div>
            </section>
        @endif

        <section class="card">
            <div class="card-header">{!! $destello !!} {{ $esAdmin ? 'Próximas reservas' : 'Tus próximas reservas' }}
                <a href="{{ route('reservas.index') }}" class="ver-todas">Ver todas <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="filas">
                @forelse ($proximas as $r)
                    <a href="{{ route('reservas.show', $r) }}" class="fila-suave fondo-{{ $r->estado }}">
                        <img src="{{ $r->espacio?->imagen_url ?? $r->objetos->first()?->imagen_url }}" alt="" class="foto">
                        <div class="principal">
                            <div class="titulo text-truncate">{{ $r->objetos->pluck('nombre')->join(' + ') }}</div>
                            <div class="meta">
                                <span><i class="bi bi-calendar3"></i>{{ ucfirst($r->fecha->translatedFormat('l d \d\e F')) }}</span>
                                <span><i class="bi bi-clock"></i>{{ $r->horario }}</span>
                            </div>
                        </div>
                        @include('partials.estado', ['estado' => $r->estado])
                    </a>
                @empty
                    <div class="vacio">
                        <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono-vacio">
                        <div class="mb-3">No hay reservas próximas.</div>
                        @unless ($esAdmin) <a href="{{ route('reservas.create') }}" class="btn btn-primary btn-sm">Hacer una reserva</a> @endunless
                    </div>
                @endforelse
            </div>
        </section>
        @unless ($esAdmin)
            <section class="tarjeta-marca">
                <h3>Encuentra el espacio perfecto</h3>
                <p>Mira qué está libre esta semana y resérvalo en un par de clics.</p>
                <a href="{{ route('disponibilidad.index') }}" class="btn btn-primary btn-sm">Ver disponibilidad</a>
                <img src="{{ asset('img/logo-icono.png') }}" alt="">
            </section>
        @endunless
    </div>

    <div class="col-xl-4 d-flex flex-column gap-4">
        <section class="card">
            <div class="card-header">{!! $destello !!} Notificaciones
                <a href="{{ url('/notificaciones') }}" class="ver-todas">Ver todas <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="filas">
                @forelse ($notificaciones as $n)
                    <div class="notif {{ $n->leida ? 'leida' : '' }}">
                        <span class="circulo"><i class="bi bi-bell"></i></span>
                        <div class="min-w-0">
                            <div class="titulo">{{ $n->titulo }}</div>
                            <div class="detalle">{{ \Illuminate\Support\Str::limit($n->mensaje, 55) }}</div>
                        </div>
                        <span class="hace">{{ $n->created_at->diffForHumans(null, true) }}</span>
                    </div>
                @empty
                    <div class="text-muted small px-1">Aún no tienes notificaciones.</div>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="card-header">{!! $destello !!} Próximos festivos</div>
            <div class="card-body pt-2">
                @forelse ($festivos as $f)
                    <div class="d-flex justify-content-between small py-1">
                        <span>{{ $f['nombre'] }}</span>
                        <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($f['fecha'])->translatedFormat('d M') }}</span>
                    </div>
                @empty
                    <div class="small text-muted">No pudimos consultar los festivos en este momento.</div>
                @endforelse
                <div class="small text-muted mt-2">{{ $usuario->organizacion->cerrar_en_festivos ? 'Tu organización no abre estos días.' : 'Datos de Nager.Date (servicio externo).' }}</div>
            </div>
        </section>

    </div>
</div>
@endsection
