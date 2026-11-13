@extends('layouts.auth')

@section('titulo', 'Nueva contraseña')

@section('contenido')
<h1 class="titulo-pagina mb-2">Crea una nueva contraseña</h1>
<p class="text-muted mb-4">Debe tener al menos 8 caracteres.</p>

<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Nueva contraseña</label>
        <input type="password" id="password" name="password"
               class="form-control @error('password') is-invalid @enderror" required autofocus>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
    </div>
    <button class="btn btn-primary btn-lg w-100">Guardar contraseña</button>
</form>
@endsection
