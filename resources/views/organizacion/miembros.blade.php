@extends('layouts.app')

@section('titulo', 'Miembros y roles')

@include('listados._datatables')

@section('contenido')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Miembros de {{ $org->nombre }}</span>
                <span class="badge text-bg-light">{{ $miembros->count() }}</span>
            </div>
            <div class="card-body">
                <table id="tablaMiembros" class="table align-middle w-100">
                    <thead><tr><th>Persona</th><th>Rol</th><th>Reservas</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @foreach ($miembros as $m)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $m->foto_url }}" class="rounded-circle" width="34" height="34" style="object-fit:cover" alt="">
                                        <div class="lh-sm"><div class="fw-semibold">{{ $m->name }}</div><small class="text-muted">{{ $m->email }}</small></div>
                                    </div>
                                </td>
                                <td><span class="badge text-bg-{{ ['organizacion' => 'dark', 'admin' => 'primary', 'usuario' => 'light'][$m->rol] }}">{{ $m->rol_nombre }}</span></td>
                                <td>{{ $m->reservas_count }}</td>
                                <td class="text-end text-nowrap">
                                    @unless ($m->esOrganizacion())
                                        {{-- Asignar o revocar el rol de administrador --}}
                                        <form method="POST" action="{{ route('miembros.rol', $m) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            @if ($m->rol === 'admin')
                                                <input type="hidden" name="rol" value="usuario">
                                                <button class="btn btn-sm btn-outline-secondary">Quitar admin</button>
                                            @else
                                                <input type="hidden" name="rol" value="admin">
                                                <button class="btn btn-sm btn-outline-primary">Hacer admin</button>
                                            @endif
                                        </form>
                                        <form method="POST" action="{{ route('miembros.destroy', $m) }}" class="d-inline" onsubmit="return confirm('¿Quitar a {{ $m->name }} de la organización?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Quitar de la organización"><i class="bi bi-person-x"></i></button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4 d-flex flex-column gap-4">
        <div class="card">
            <div class="card-header">Autorizar un correo</div>
            <div class="card-body">
                <p class="small text-muted">Si ya es miembro, su rol cambia de inmediato. Si no tiene cuenta, entrará con ese rol al registrarse o al iniciar sesión con Google.</p>
                <form method="POST" action="{{ route('invitaciones.store') }}" novalidate>
                    @csrf
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" class="form-control mb-2 @error('email') is-invalid @enderror" required>
                    @error('email') <div class="invalid-feedback mb-2">{{ $message }}</div> @enderror
                    <div class="d-flex gap-2">
                        <select name="rol" class="form-select">
                            <option value="admin">Administrador</option>
                            <option value="usuario">Usuario</option>
                        </select>
                        <button class="btn btn-primary text-nowrap">Autorizar</button>
                    </div>
                </form>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($invitaciones as $inv)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="lh-sm"><div class="small fw-semibold">{{ $inv->email }}</div><small class="text-muted">{{ $inv->rol === 'admin' ? 'Administrador' : 'Usuario' }} · pendiente</small></div>
                        <form method="POST" action="{{ route('invitaciones.destroy', $inv) }}">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger" title="Anular"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item small text-muted">No hay invitaciones pendientes.</li>
                @endforelse
            </ul>
        </div>

        <div class="card card-body text-center">
            <small class="text-muted">Código de invitación</small>
            <div class="codigo-invitacion user-select-all">{{ $org->codigo_invitacion }}</div>
            @unless ($org->configurada)
                <div class="alert alert-warning small mt-2 mb-0">El acceso aún no está habilitado. <a href="{{ route('organizacion.edit') }}">Habilítalo aquí</a>.</div>
            @endunless
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>crearTabla('#tablaMiembros', { columnDefs: [{ targets: 3, orderable: false, searchable: false }] });</script>
@endpush
