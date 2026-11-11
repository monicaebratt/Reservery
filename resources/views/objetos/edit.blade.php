@extends('layouts.app')

@section('titulo', 'Editar: '.$objeto->nombre)

@section('contenido')
<form method="POST" action="{{ route('objetos.update', $objeto) }}" enctype="multipart/form-data" novalidate>
    @method('PUT')
    @include('objetos._form')
    <div class="d-flex gap-2 mt-2">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar cambios</button>
        <a href="{{ route('objetos.show', $objeto) }}" class="btn btn-light">Cancelar</a>
    </div>
</form>
@endsection
