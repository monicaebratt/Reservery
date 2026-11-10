@extends('layouts.app')

@section('titulo', 'Nueva reserva')
@section('subtitulo', 'Tres pasos: qué necesitas, para qué y a qué hora.')

@section('contenido')
<form method="POST" action="{{ route('reservas.store') }}" novalidate>
    @component('reservas._form', compact('reserva', 'propositos', 'espacios', 'recursos', 'paquetes', 'preseleccion'))
        @slot('botones')
            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check-lg"></i> Solicitar reserva</button>
        @endslot
    @endcomponent
</form>
@endsection
