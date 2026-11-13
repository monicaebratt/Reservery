@extends('layouts.app')

@section('titulo', 'Editar reserva #'.$reserva->id)

@section('contenido')
<form method="POST" action="{{ route('reservas.update', $reserva) }}" novalidate>
    @method('PUT')
    @component('reservas._form', compact('reserva', 'propositos', 'espacios', 'recursos', 'paquetes', 'preseleccion'))
        @slot('botones')
            <button class="btn btn-primary w-100 mb-2"><i class="bi bi-save"></i> Guardar cambios</button>
            <a href="{{ route('reservas.show', $reserva) }}" class="btn btn-light w-100">Cancelar</a>
        @endslot
    @endcomponent
</form>
@endsection
