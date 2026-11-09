@extends('layouts.base')

@section('titulo', 'Algo salió mal')

@section('cuerpo')
<div class="min-vh-100 d-grid" style="place-items:center">
    <div class="text-center p-4" style="max-width:440px">
        <a href="{{ url('/') }}" class="marca mb-4"><i class="bi bi-calendar2-check-fill"></i> Reservery</a>
        <div class="display-4 fw-bold text-primary mt-3">500</div>
        <h1 class="h4 fw-bold">Algo salió mal</h1>
        <p class="text-muted">Ocurrió un error inesperado. Intenta de nuevo en un momento.</p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Volver al inicio</a>
    </div>
</div>
@endsection
