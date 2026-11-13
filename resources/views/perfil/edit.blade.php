@extends('layouts.app')

@section('titulo', 'Editar perfil')

@section('contenido')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">Mi información @if ($usuario->google_id) <span class="badge text-bg-light"><i class="bi bi-google"></i> Vinculada con Google</span> @endif</div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
                        <img id="vistaFoto" src="{{ $usuario->foto_url }}" class="rounded-circle" width="88" height="88" style="object-fit:cover" alt="Foto de perfil">
                        <div class="flex-grow-1">
                            <label for="foto" class="form-label">Foto de perfil <span class="text-muted">(máx. 2 MB)</span></label>
                            <input type="file" id="foto" name="foto" accept="image/*" class="form-control @error('foto') is-invalid @enderror">
                            @error('foto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $usuario->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}"
                                   class="form-control @error('email') is-invalid @enderror" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}"
                                   class="form-control @error('telefono') is-invalid @enderror">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Organización</label>
                            <input type="text" class="form-control" value="{{ $usuario->organizacion->nombre }} · {{ $usuario->rol_nombre }}" disabled>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4"><i class="bi bi-save"></i> Guardar cambios</button>
                </form>
                @if ($usuario->foto)
                    <form method="POST" action="{{ route('perfil.foto.destroy') }}" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="btn btn-link btn-sm text-danger p-0">Quitar foto actual</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">{{ $usuario->password ? 'Cambiar contraseña' : 'Crear contraseña' }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('perfil.password') }}">
                    @csrf @method('PUT')
                    @if (! $usuario->password)
                        <p class="small text-muted">Entraste con Google y aún no tienes contraseña. Crea una para entrar también con tu correo.</p>
                    @else
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endif
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                    </div>
                    <button class="btn btn-outline-primary w-100">Guardar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('foto').addEventListener('change', (e) => {
        if (e.target.files[0]) document.getElementById('vistaFoto').src = URL.createObjectURL(e.target.files[0]);
    });
</script>
@endpush
