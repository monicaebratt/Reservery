@extends('layouts.auth')

@section('titulo', 'Registrar organización')

@section('contenido')
<h1 class="titulo-pagina mb-2">Registra tu organización</h1>
<p class="text-muted mb-4">Después configuras horarios y reglas, y habilitas el acceso de tus miembros.</p>

@include('partials.google', ['texto' => 'Empezar con Google'])
<p class="small text-muted mt-2 mb-0">Con Google primero creas tu cuenta y luego llenas los datos de la organización.</p>

<div class="separador">o llena el formulario</div>

<form method="POST" action="{{ route('organizacion.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    <h2 class="h6 fw-bold text-uppercase text-muted small mb-3">1. La organización</h2>
    @include('organizacion._datos')

    <h2 class="h6 fw-bold text-uppercase text-muted small mt-4 mb-3">2. Tu cuenta como responsable</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="cuenta_nombre" class="form-label">Tu nombre</label>
            <input type="text" id="cuenta_nombre" name="cuenta_nombre" value="{{ old('cuenta_nombre') }}"
                   class="form-control @error('cuenta_nombre') is-invalid @enderror" required>
            @error('cuenta_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="cuenta_email" class="form-label">Tu correo (para iniciar sesión)</label>
            <input type="email" id="cuenta_email" name="cuenta_email" value="{{ old('cuenta_email') }}"
                   class="form-control @error('cuenta_email') is-invalid @enderror" required>
            @error('cuenta_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
        </div>
    </div>
    <button class="btn btn-primary btn-lg w-100 mt-4">Registrar organización</button>
</form>

<p class="text-center text-muted mt-4 mb-0">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
@endsection
