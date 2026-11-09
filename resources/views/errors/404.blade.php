@extends('layouts.base')

@section('titulo', 'No encontramos esa página')

@section('cuerpo')
<div class="min-vh-100 d-grid" style="place-items:center">
    <div class="text-center p-4" style="max-width:440px">
        <a href="{{ url('/') }}" class="marca mb-4"><i class="bi bi-calendar2-check-fill"></i> Reservery</a>
        <div class="display-4 fw-bold text-primary mt-3">404</div>
        <h1 class="h4 fw-bold">No encontramos esa página</h1>
        <p class="text-muted">{{ $exception?->getMessage() ?: 'Puede que no exista o que pertenezca a otra organización.' }}</p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Volver al inicio</a>
    </div>
</div>
@endsection
