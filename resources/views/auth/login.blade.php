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
        <div class="campo-icono">
            <i class="bi bi-envelope"></i>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="nombre@correo.com"
                   class="form-control form-control-lg @error('email') is-invalid @enderror" autofocus required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-baseline">
            <label for="password" class="form-label">Contraseña</label>
            <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
        </div>
        <div class="campo-icono">
            <i class="bi bi-lock"></i>
            <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Tu contraseña"
                   class="form-control form-control-lg con-ojo @error('password') is-invalid @enderror" required>
            <button type="button" class="ver-clave" data-ver="password" aria-label="Mostrar contraseña"><i class="bi bi-eye-slash"></i></button>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember">
        <label class="form-check-label" for="remember">Recordarme</label>
    </div>
    <button class="btn btn-brillo btn-lg w-100 d-flex align-items-center justify-content-center gap-2">Iniciar sesión <i class="bi bi-arrow-right"></i></button>
</form>

<p class="text-center text-muted mt-4 mb-1">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
<p class="text-center text-muted mb-0">¿Administras espacios? <a href="{{ route('organizacion.create') }}">Registra tu organización</a></p>
@endsection

@push('scripts')
<script>
    // Mostrar u ocultar la contraseña
    document.querySelectorAll('[data-ver]').forEach(b => b.addEventListener('click', () => {
        const campo = document.getElementById(b.dataset.ver);
        const ver = campo.type === 'password';
        campo.type = ver ? 'text' : 'password';
        b.querySelector('i').className = ver ? 'bi bi-eye' : 'bi bi-eye-slash';
        b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
    }));
</script>
@endpush
