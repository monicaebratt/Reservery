@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('contenido')
<h1 class="titulo-pagina mb-2">Bienvenido de nuevo</h1>
<p class="text-muted mb-4">Ingresa para ver y gestionar tus reservas.</p>

@include('partials.google')

<div class="separador">o con tu correo</div>

<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email"
               class="form-control form-control-lg @error('email') is-invalid @enderror" autofocus required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between">
            <label for="password" class="form-label">Contraseña</label>
            <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
        </div>
        <input type="password" id="password" name="password" autocomplete="current-password"
               class="form-control form-control-lg @error('password') is-invalid @enderror" required>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember">
        <label class="form-check-label" for="remember">Recordarme</label>
    </div>
    <button class="btn btn-primary btn-lg w-100">Iniciar sesión</button>
</form>

<p class="text-center text-muted mt-4 mb-1">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
<p class="text-center text-muted mb-0 small">¿Administras espacios? <a href="{{ route('organizacion.create') }}">Registra tu organización</a></p>
@endsection
