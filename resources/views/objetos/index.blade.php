@extends('layouts.app')

@section('titulo', 'Espacios y recursos')

@section('contenido')
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
        <input type="search" name="buscar" value="{{ request('buscar') }}" class="form-control" placeholder="Buscar por nombre" style="max-width:220px">
        <select name="tipo" class="form-select" style="max-width:150px" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="espacio" @selected(request('tipo') === 'espacio')>Espacios</option>
            <option value="recurso" @selected(request('tipo') === 'recurso')>Recursos</option>
        </select>
        <select name="categoria" class="form-select" style="max-width:200px" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $c) <option @selected(request('categoria') === $c)>{{ $c }}</option> @endforeach
        </select>
        <button class="btn btn-outline-secondary" aria-label="Buscar"><i class="bi bi-search"></i></button>
    </form>
    @if (auth()->user()->esAdmin())
        <a href="{{ route('objetos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo</a>
    @endif
</div>

<div class="row g-3">
    @forelse ($objetos as $o)
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100">
                <img src="{{ $o->imagen_url }}" class="objeto-img" alt="{{ $o->nombre }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <h3 class="h6 fw-bold mb-0">{{ $o->nombre }}</h3>
                        <span class="badge text-bg-{{ $o->esEspacio() ? 'primary' : 'info' }}">{{ $o->tipo_nombre }}</span>
                    </div>
                    <small class="text-muted d-block">{{ $o->categoria }}</small>
                    <small class="text-muted d-block mb-2"><i class="bi bi-geo-alt"></i> {{ $o->ubicacion ?? 'Sin ubicación' }}
                        @if ($o->capacidad) · <i class="bi bi-people"></i> {{ $o->capacidad }} @endif</small>
                    @if ($o->esEspacio() && $o->recursos_asociados_count) <span class="badge text-bg-light">{{ $o->recursos_asociados_count }} recursos asociados</span> @endif
                    @unless ($o->disponible) <span class="badge text-bg-secondary">Inhabilitado</span> @endunless
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <a href="{{ route('objetos.show', $o) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                    @unless (auth()->user()->esAdmin())
                        <a href="{{ route('reservas.create', ['objeto' => $o->id]) }}" class="btn btn-sm btn-primary">Reservar</a>
                    @endunless
                    @if (auth()->user()->esAdmin())
                        <a href="{{ route('objetos.edit', $o) }}" class="btn btn-sm btn-outline-secondary ms-auto">Editar</a>
                        <form action="{{ route('objetos.destroy', $o) }}" method="POST" onsubmit="return confirm('¿Eliminar {{ $o->nombre }}? También se borrarán sus reservas.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card card-body text-center text-muted py-5">No se encontraron espacios ni recursos.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $objetos->links() }}</div>
@endsection
