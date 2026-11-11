@extends('layouts.app')

@section('titulo', 'Nuevo espacio o recurso')

@section('contenido')
<form method="POST" action="{{ route('objetos.store') }}" enctype="multipart/form-data" novalidate>
    @include('objetos._form')
    <div class="d-flex gap-2 mt-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('objetos.index') }}" class="btn btn-light">Cancelar</a>
    </div>
</form>
@endsection
