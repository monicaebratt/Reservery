@extends('layouts.auth')

@section('titulo', 'Olvidé mi contraseña')

@section('contenido')
<h1 class="titulo-pagina mb-2">¿Olvidaste tu contraseña?</h1>
<p class="text-muted mb-4">Escribe tu correo y te enviaremos un enlace para crear una nueva.</p>

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               class="form-control form-control-lg @error('email') is-invalid @enderror" required autofocus>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <button class="btn btn-primary btn-lg w-100">Enviar enlace</button>
</form>

<p class="text-center mt-4 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a></p>
@endsection
