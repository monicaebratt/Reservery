@extends('layouts.auth')

@section('titulo', 'Bienvenida')

@section('contenido')
<h1 class="titulo-pagina mb-2">¡Hola, {{ $usuario->name }}!</h1>

@if ($usuario->organizacion_id)
    {{-- Pertenece a una organización que aún no habilita el acceso (RF01.1) --}}
    <div class="card card-body mt-3">
        <div class="paso">
            <span class="paso-icono bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <strong>{{ $usuario->organizacion->nombre }} aún está configurando Reservery.</strong>
                <p class="text-muted mb-0 small">Podrás reservar en cuanto la organización habilite el acceso. Vuelve a intentarlo más tarde.</p>
            </div>
        </div>
    </div>
@else
    <p class="text-muted mb-4">Para empezar a reservar necesitas pertenecer a una organización.</p>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold"><i class="bi bi-key me-1"></i> Tengo un código de invitación</h2>
            <form method="POST" action="{{ route('bienvenida.unirse') }}" class="d-flex gap-2 mt-2">
                @csrf
                <input type="text" name="codigo" value="{{ old('codigo') }}" maxlength="12" placeholder="Ej: DEMO2026"
                       class="form-control text-uppercase @error('codigo') is-invalid @enderror" required>
                <button class="btn btn-primary text-nowrap">Unirme</button>
            </form>
            @error('codigo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <p class="small text-muted mt-2 mb-0">Si tu organización invitó tu correo, al habilitar el acceso entrarás automáticamente.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 fw-bold"><i class="bi bi-building-add me-1"></i> Quiero registrar mi organización</h2>
            <p class="small text-muted">Tu cuenta quedará como responsable de la organización.</p>
            <form method="POST" action="{{ route('bienvenida.organizacion') }}" novalidate>
                @csrf
                @include('organizacion._datos')
                <button class="btn btn-outline-primary w-100 mt-3">Registrar organización</button>
            </form>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
    @csrf
    <button class="btn btn-link btn-sm text-muted">Cerrar sesión</button>
</form>
@endsection
