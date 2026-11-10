@extends('layouts.auth')

@section('titulo', 'Crear cuenta')

@section('contenido')
<h1 class="titulo-pagina mb-2">Crea tu cuenta</h1>
<p class="text-muted mb-4">Usa el código que te dio tu organización. Si tu correo fue invitado, no lo necesitas.</p>

<form method="POST" action="{{ route('register') }}" novalidate id="formRegistro">
    @csrf
    <div class="mb-3">
        <label for="codigo" class="form-label">Código de invitación <span class="text-muted">(opcional)</span></label>
        <input type="text" id="codigo" name="codigo" value="{{ old('codigo', $codigo) }}" maxlength="12"
               class="form-control text-uppercase @error('codigo') is-invalid @enderror" placeholder="Ej: DEMO2026">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div id="zonaGoogle">@include('partials.google', ['texto' => 'Registrarme con Google'])</div>

    <div class="separador">o con tu correo</div>

    <div class="mb-3">
        <label for="name" class="form-label">Nombre completo</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="row g-3 mb-3">
        <div class="col-sm-7">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email"
                   class="form-control @error('email') is-invalid @enderror" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-5">
            <label for="telefono" class="form-label">Teléfono <span class="text-muted">(opcional)</span></label>
            <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}" class="form-control">
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
        </div>
        <div class="col-sm-6">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
        </div>
    </div>
    <button class="btn btn-primary btn-lg w-100">Crear cuenta</button>
</form>

<p class="text-center text-muted mt-4 mb-1">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
<p class="text-center text-muted mb-0 small">¿Eres el responsable de los espacios? <a href="{{ route('organizacion.create') }}">Registra tu organización</a></p>
@endsection

@push('scripts')
<script>
    // El botón de Google lleva el código escrito para unirte a esa organización
    const codigo = document.getElementById('codigo');
    const google = document.querySelector('#zonaGoogle a');
    const base = '{{ route('google.redirect') }}';
    codigo.addEventListener('input', () => {
        const valor = codigo.value.trim();
        google.href = valor ? `${base}?codigo=${encodeURIComponent(valor)}` : base;
    });
</script>
@endpush
