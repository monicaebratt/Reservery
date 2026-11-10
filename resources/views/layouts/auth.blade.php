{{-- Maestra para login, registro y contraseña. Hereda de la maestra base. --}}
@extends('layouts.base')

@section('cuerpo')
<div class="auth-wrap">
    <div class="auth-lado d-none d-lg-flex">
        <a href="{{ route('inicio') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        <img src="{{ asset('img/logo-reservery.png') }}" alt="Reservery" class="logo-grande">
        <p class="frase mb-0">Tus espacios y equipos, siempre en orden.</p>
        <small class="text-muted text-center">© {{ date('Y') }} Reservery</small>
    </div>
    <div class="auth-form">
        <div class="auth-card">
            <a href="{{ route('inicio') }}" class="marca d-lg-none mb-4">
                <img src="{{ asset('img/logo-icono.png') }}" alt="" class="icono">
                <img src="{{ asset('img/logo-texto.png') }}" alt="Reservery" class="texto">
            </a>
            @include('partials.alertas')
            @yield('contenido')
        </div>
    </div>
</div>
@endsection
