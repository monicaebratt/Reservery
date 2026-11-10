@extends('layouts.auth')

@section('titulo', 'Confirma tu correo')

@section('contenido')
<h1 class="titulo-pagina mb-2">Confirma tu correo</h1>
<p class="text-muted mb-4">
    Te enviamos un enlace a <strong>{{ $email }}</strong>. Ábrelo para activar tu cuenta.
    Hasta entonces no puedes usar Reservery: así nadie puede registrarse con un correo que no es suyo.
</p>

<form method="POST" action="{{ route('verification.send') }}" class="mb-3">
    @csrf
    <button class="btn btn-primary btn-lg w-100"><i class="bi bi-envelope"></i> Reenviar el enlace</button>
</form>
<p class="small text-muted">Revisa también la carpeta de spam. Si escribiste mal el correo, cierra sesión y regístrate de nuevo.</p>

<form method="POST" action="{{ route('logout') }}" class="text-center mt-4">
    @csrf
    <button class="btn btn-link"><i class="bi bi-box-arrow-left"></i> Cerrar sesión</button>
</form>
@endsection
