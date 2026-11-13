@extends('layouts.base')

@section('cuerpo')
<div class="escenario">
    @include('partials.escenario')
    <a href="{{ route('inicio') }}" class="acceso-volver"><i class="bi bi-arrow-left"></i> Volver al inicio</a>

    <div class="acceso">
        <div class="acceso-marca d-none d-lg-block">
            <img src="{{ asset('img/logo-reservery.png') }}" alt="Reservery" class="logo-grande">
            <div class="divisor-destello"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg></div>
            <p class="frase">Tus espacios y equipos, siempre en orden.</p>
            <div class="flota-suave"><img src="{{ asset('img/calendario-3d.webp') }}" alt="Calendario de Reservery con una reserva destacada y una solicitud aprobada" class="calendario-3d" width="1200" height="515"></div>
        </div>

        <div class="acceso-tarjeta vidrio">
            <a href="{{ route('inicio') }}" class="marca d-flex d-lg-none justify-content-center mb-4">
                <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
                <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
            </a>
            @include('partials.alertas')
            @yield('contenido')
        </div>
    </div>
</div>
@endsection
